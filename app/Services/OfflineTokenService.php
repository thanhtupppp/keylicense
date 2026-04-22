<?php

namespace App\Services;

use App\Exceptions\InvalidTokenException;
use App\Models\Activation;
use App\Models\OfflineTokenJti;
use App\Models\Product;
use App\Models\License;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\ValidAt;

class OfflineTokenService
{
    /**
     * Issue an offline token (JWT RS256) for an activation.
     *
     * @param Activation $activation
     * @param Product $product
     * @return string The JWT token
     */
    public function issue(Activation $activation, Product $product): string
    {
        $license = $activation->license;
        $now = Carbon::now();
        $nowImmutable = \DateTimeImmutable::createFromMutable($now);
        $ttlSeconds = $product->offline_token_ttl_hours * 3600;
        $expiresAt = $nowImmutable->modify("+{$ttlSeconds} seconds");

        // Generate unique JTI
        $jti = Str::uuid()->toString();

        // Get private key
        $privateKey = $this->getPrivateKey();

        // Create JWT configuration
        $config = Configuration::forAsymmetricSigner(
            new Sha256(),
            $privateKey,
            $this->getPublicKey()
        );

        // Build token
        $token = $config->builder()
            ->issuedBy(config('jwt.issuer'))
            ->permittedFor($product->slug)
            ->identifiedBy($jti)
            ->issuedAt($nowImmutable)
            ->canOnlyBeUsedAfter($nowImmutable)
            ->expiresAt($expiresAt)
            ->relatedTo(hash('sha256', $license->key_hash))
            ->withClaim('device_fp_hash', $activation->device_fp_hash ?? '')
            ->withClaim('license_model', $license->license_model)
            ->withClaim('license_expiry', $license->expiry_date?->toIso8601String() ?? null)
            ->getToken($config->signer(), $config->signingKey());

        // Save JTI to database
        OfflineTokenJti::create([
            'license_id' => $license->id,
            'jti' => $jti,
            'expires_at' => Carbon::instance($expiresAt),
            'is_revoked' => false,
        ]);

        return $token->toString();
    }

    /**
     * Verify an offline token (JWT RS256).
     *
     * Validates:
     * - Signature with RS256 (hardcoded, not from header)
     * - exp, nbf, iss, aud claims
     * - JTI not revoked
     * - exp - iat <= product.offline_token_ttl_hours * 3600
     * - nbf - iat <= 300 seconds
     *
     * @param string $token The JWT token string
     * @param Product $product
     * @return array The decoded token claims
     * @throws InvalidTokenException
     */
    public function verify(string $token, Product $product): array
    {
        try {
            // Get public key
            $publicKey = $this->getPublicKey();

            // Create JWT configuration
            $config = Configuration::forAsymmetricSigner(
                new Sha256(),
                $this->getPrivateKey(),
                $publicKey
            );

            // Parse token
            $parser = new Parser(new \Lcobucci\JWT\Encoding\JoseEncoder());
            /** @var \Lcobucci\JWT\Token\Plain $parsedToken */
            $parsedToken = $parser->parse($token);

            // Verify signature with RS256 hardcoded
            if (!$config->validator()->validate($parsedToken, new SignedWith($config->signer(), $config->verificationKey()))) {
                throw new InvalidTokenException('Invalid token signature');
            }

            // Verify issuer
            if (!$config->validator()->validate($parsedToken, new IssuedBy(config('jwt.issuer')))) {
                throw new InvalidTokenException('Invalid token issuer');
            }

            // Verify audience (product slug)
            if (!$config->validator()->validate($parsedToken, new PermittedFor($product->slug))) {
                throw new InvalidTokenException('Invalid token audience');
            }

            // Verify exp and nbf
            $clock = SystemClock::fromUTC();
            if (!$config->validator()->validate($parsedToken, new ValidAt($clock))) {
                throw new InvalidTokenException('Token expired or not yet valid');
            }

            // Get claims
            $claims = $parsedToken->claims();
            $iat = $claims->get('iat')->getTimestamp();
            $exp = $claims->get('exp')->getTimestamp();
            $nbf = $claims->get('nbf')->getTimestamp();
            $jti = $claims->get('jti');

            // Check exp - iat <= product.offline_token_ttl_hours * 3600
            $maxTtlSeconds = $product->offline_token_ttl_hours * 3600;
            $actualTtl = $exp - $iat;
            if ($actualTtl > $maxTtlSeconds) {
                throw new InvalidTokenException('Token TTL exceeds product maximum');
            }

            // Check nbf - iat <= 300 seconds
            $nbfSkew = $nbf - $iat;
            if ($nbfSkew > config('jwt.nbf_tolerance_seconds')) {
                throw new InvalidTokenException('Token nbf claim too far in future');
            }

            // Check JTI not revoked and not expired
            $jtiRecord = OfflineTokenJti::where('jti', $jti)->first();
            if (!$jtiRecord || $jtiRecord->is_revoked) {
                throw new InvalidTokenException('Token has been revoked');
            }

            // Check if JTI has expired (expires_at in the past)
            if ($jtiRecord->expires_at && $jtiRecord->expires_at->isPast()) {
                throw new InvalidTokenException('Token has expired');
            }

            // Return decoded claims as array
            $aud = $claims->get('aud');
            $audString = is_array($aud) ? (count($aud) > 0 ? (string) $aud[0] : '') : (string) $aud;

            return [
                'jti' => (string) $jti,
                'iss' => (string) $claims->get('iss'),
                'aud' => $audString,
                'sub' => (string) $claims->get('sub'),
                'iat' => $iat,
                'nbf' => $nbf,
                'exp' => $exp,
                'device_fp_hash' => (string) $claims->get('device_fp_hash'),
                'license_model' => (string) $claims->get('license_model'),
                'license_expiry' => $claims->get('license_expiry') ? (string) $claims->get('license_expiry') : null,
            ];
        } catch (InvalidTokenException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new InvalidTokenException('Invalid token: ' . $e->getMessage());
        }
    }

    /**
     * Revoke all offline tokens for a license.
     *
     * @param License $license
     * @return void
     */
    public function revokeAllForLicense(License $license): void
    {
        OfflineTokenJti::where('license_id', $license->id)
            ->update(['is_revoked' => true]);
    }

    /**
     * Get the private key for signing tokens.
     *
     * @return \Lcobucci\JWT\Signer\Key\InMemory
     */
    private function getPrivateKey(): \Lcobucci\JWT\Signer\Key\InMemory
    {
        $privateKeyPath = config('jwt.private_key');

        if (!$privateKeyPath) {
            throw new \Exception('JWT_PRIVATE_KEY not configured');
        }

        // If it's a file path, read it
        if (file_exists($privateKeyPath)) {
            $privateKeyContent = File::get($privateKeyPath);
        } else {
            // Otherwise treat it as the key content directly
            $privateKeyContent = $privateKeyPath;
        }

        return \Lcobucci\JWT\Signer\Key\InMemory::plainText($privateKeyContent);
    }

    /**
     * Get the public key for verifying tokens.
     *
     * @return \Lcobucci\JWT\Signer\Key\InMemory
     */
    private function getPublicKey(): \Lcobucci\JWT\Signer\Key\InMemory
    {
        $publicKeyPath = config('jwt.public_key_path');

        if (!$publicKeyPath || !file_exists($publicKeyPath)) {
            throw new \Exception('JWT public key not found at ' . $publicKeyPath);
        }

        $publicKeyContent = File::get($publicKeyPath);

        return \Lcobucci\JWT\Signer\Key\InMemory::plainText($publicKeyContent);
    }
}

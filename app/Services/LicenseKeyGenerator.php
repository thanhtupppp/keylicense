<?php

namespace App\Services;

use App\Models\License;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LicenseKeyGenerator
{
    /**
     * Characters allowed in license key (uppercase alphanumeric).
     */
    private const ALLOWED_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    /**
     * Length of each segment in the license key format XXXX-XXXX-XXXX-XXXX.
     */
    private const SEGMENT_LENGTH = 4;

    /**
     * Number of segments in the license key format.
     */
    private const SEGMENT_COUNT = 4;

    /**
     * Maximum batch size for generation.
     */
    private const MAX_BATCH_SIZE = 100;

    /**
     * Minimum batch size for generation.
     */
    private const MIN_BATCH_SIZE = 1;

    /**
     * Generate a single license key.
     *
     * @return string The plaintext license key in format XXXX-XXXX-XXXX-XXXX
     */
    public function generate(): string
    {
        do {
            $key = $this->generateRandomKey();
        } while ($this->keyHashExists($key));

        return $key;
    }

    /**
     * Generate a batch of license keys.
     *
     * @param int $count Number of keys to generate (1-100)
     * @return array<int, string> Array of plaintext license keys
     * @throws \InvalidArgumentException If count is outside valid range
     */
    public function generateBatch(int $count): array
    {
        if ($count < self::MIN_BATCH_SIZE || $count > self::MAX_BATCH_SIZE) {
            throw new \InvalidArgumentException(
                "Batch size must be between {$count} and " . self::MAX_BATCH_SIZE
            );
        }

        $keys = [];
        $attempts = 0;
        $maxAttempts = $count * 100; // Prevent infinite loops

        while (count($keys) < $count && $attempts < $maxAttempts) {
            $key = $this->generateRandomKey();

            // Check if key is unique (not in database and not in current batch)
            if (!$this->keyHashExists($key) && !in_array($key, $keys)) {
                $keys[] = $key;
            }

            $attempts++;
        }

        if (count($keys) < $count) {
            throw new \RuntimeException(
                "Failed to generate {$count} unique license keys after {$maxAttempts} attempts"
            );
        }

        return $keys;
    }

    /**
     * Generate a random license key in format XXXX-XXXX-XXXX-XXXX.
     *
     * @return string The plaintext license key
     */
    private function generateRandomKey(): string
    {
        $segments = [];

        for ($i = 0; $i < self::SEGMENT_COUNT; $i++) {
            $segment = '';
            for ($j = 0; $j < self::SEGMENT_LENGTH; $j++) {
                $segment .= self::ALLOWED_CHARS[random_int(0, strlen(self::ALLOWED_CHARS) - 1)];
            }
            $segments[] = $segment;
        }

        return implode('-', $segments);
    }

    /**
     * Check if a license key hash already exists in the database.
     *
     * @param string $plaintextKey The plaintext license key
     * @return bool True if the key hash exists, false otherwise
     */
    private function keyHashExists(string $plaintextKey): bool
    {
        $keyHash = $this->hashKey($plaintextKey);

        return License::where('key_hash', $keyHash)
            ->withoutTrashed() // Include soft-deleted licenses
            ->exists();
    }

    /**
     * Hash a license key using SHA-256.
     *
     * @param string $plaintextKey The plaintext license key
     * @return string The SHA-256 hash (64 characters)
     */
    public function hashKey(string $plaintextKey): string
    {
        return hash('sha256', $plaintextKey);
    }

    /**
     * Extract the last 4 characters of a license key.
     *
     * @param string $plaintextKey The plaintext license key
     * @return string The last 4 characters
     */
    public function getKeyLast4(string $plaintextKey): string
    {
        return substr($plaintextKey, -4);
    }
}

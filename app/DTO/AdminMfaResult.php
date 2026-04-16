<?php

namespace App\DTO;

final class AdminMfaResult
{
    /**
     * @param array<int, string>|null $backupCodes
     */
    public function __construct(
        public readonly bool $valid,
        public readonly ?string $secret = null,
        public readonly ?string $otpauthUri = null,
        public readonly ?bool $mfaEnabled = null,
        public readonly ?bool $locked = null,
        public readonly ?string $method = null,
        public readonly ?array $backupCodes = null,
    ) {
    }

    /**
     * @param array<int, string> $backupCodes
     */
    public static function setup(
        string $secret,
        string $otpauthUri,
        array $backupCodes,
        bool $mfaEnabled,
    ): self {
        return new self(
            valid: true,
            secret: $secret,
            otpauthUri: $otpauthUri,
            mfaEnabled: $mfaEnabled,
            backupCodes: $backupCodes,
        );
    }

    public static function success(
        ?string $method = null,
        ?bool $locked = null,
        ?array $backupCodes = null,
    ): self {
        return new self(
            valid: true,
            method: $method,
            locked: $locked,
            backupCodes: $backupCodes,
        );
    }

    public static function failure(?bool $locked = null): self
    {
        return new self(valid: false, locked: $locked);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = ['valid' => $this->valid];

        if ($this->secret !== null) {
            $data['secret'] = $this->secret;
        }

        if ($this->otpauthUri !== null) {
            $data['otpauth_uri'] = $this->otpauthUri;
        }

        if ($this->mfaEnabled !== null) {
            $data['mfa_enabled'] = $this->mfaEnabled;
        }

        if ($this->locked !== null) {
            $data['locked'] = $this->locked;
        }

        if ($this->method !== null) {
            $data['method'] = $this->method;
        }

        if ($this->backupCodes !== null) {
            $data['backup_codes'] = $this->backupCodes;
        }

        return $data;
    }
}

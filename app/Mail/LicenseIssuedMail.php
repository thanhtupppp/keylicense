<?php

namespace App\Mail;

use App\Models\Entitlement;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LicenseIssuedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $licenseKey,
        public readonly string $keyDisplay,
        public readonly Entitlement $entitlement,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your license key is ready',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.license-issued',
        );
    }
}

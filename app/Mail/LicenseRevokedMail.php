<?php

namespace App\Mail;

use App\Models\LicenseKey;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LicenseRevokedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly LicenseKey $license,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your license has been revoked',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.license-revoked',
        );
    }
}

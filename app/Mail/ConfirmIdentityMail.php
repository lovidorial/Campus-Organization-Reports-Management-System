<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Mime\Part\DataPart;

class ConfirmIdentityMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $verificationUrl,
        public int $expiryMinutes = 60,
        public ?string $previewLogoUrl = null,
    ) {
        if ($this->previewLogoUrl === null) {
            $this->withSymfonyMessage(function ($message): void {
                $logo = new DataPart(
                    file_get_contents(public_path('images/osdw.logo.jpg')),
                    'osdw-logo.jpg',
                    'image/jpeg',
                );
                $logo->asInline()->setContentId('osdw-logo@orgtrack');
                $message->addPart($logo);
            });
        }
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Confirm your identity | OrgTrack');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.confirm-identity',
            with: [
                'userName' => $this->user->name,
                'verificationUrl' => $this->verificationUrl,
                'expiryMinutes' => $this->expiryMinutes,
                'logoUrl' => $this->previewLogoUrl ?? 'cid:osdw-logo@orgtrack',
            ],
        );
    }
}

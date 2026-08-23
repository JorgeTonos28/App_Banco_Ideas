<?php

namespace App\Mail;

use App\Models\UserInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public UserInvitation $invitation,
        public string $invitationUrl,
    ) {
        $this->invitation->loadMissing('regional');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Invitación a INNOVATEP Ideas',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invitations.user',
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}

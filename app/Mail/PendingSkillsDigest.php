<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class PendingSkillsDigest extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Collection $skills) {}

    public function envelope(): Envelope
    {
        $count = $this->skills->count();
        $skillWord = $count === 1 ? 'skill' : 'skills';

        return new Envelope(
            subject: "SkillsDB: {$count} pending {$skillWord} to review",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.pending-skills-digest',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class TeamMemberEnrolled extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Collection $courses,
        public User $enrolledBy,
    ) {}

    public function envelope(): Envelope
    {
        $courseCount = $this->courses->count();
        $subject = $courseCount === 1
            ? "Training enrolled: {$this->courses->first()->name}"
            : "Training enrolled: {$courseCount} courses";

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.team-member-enrolled',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

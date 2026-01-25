<?php

namespace App\Mail;

use App\Models\TrainingCourse;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrainingRequestRejected extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public TrainingCourse $course,
        public User $rejector,
        public ?string $reason = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Training request declined: {$this->course->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.training-request-rejected',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

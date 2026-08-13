<?php

namespace App\Mail;

use App\Models\TrainingRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrainingRequestConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public TrainingRequest $trainingRequest)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your Training Request Has Been Received — {$this->trainingRequest->reference_number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.training-request-confirmation',
        );
    }
}

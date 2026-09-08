<?php

namespace App\Mail;

use App\Models\PendingRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountPendingApproval extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public PendingRegistration $registration) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Account Awaiting Approval — '.$this->registration->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.account-pending-approval',
        );
    }
}

<?php

namespace App\Mail;

use App\Models\CcContact;
use App\Models\Requisition;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * FYI email to a contact CC'd on a requisition.
 */
class RequisitionCcMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public const SUBMITTED = 'submitted';

    public const APPROVED = 'approved';

    public const DENIED = 'denied';

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Requisition $requisition,
        public CcContact $contact,
        public string $event,
        public ?User $actor = null,
        public ?string $remarks = null,
    ) {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $label = match ($this->event) {
            self::APPROVED => 'Approved',
            self::DENIED => 'Denied',
            default => 'Submitted',
        };

        return new Envelope(
            subject: "FYI: Requisition [{$this->requisition->requisition_number}] {$label}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.requisitions.cc',
        );
    }
}

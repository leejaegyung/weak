<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WeeklyReportMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param array $data {subject, body_intro, body_main, body_outro, week, week_start, week_end, list_url, reports[{name, position}]}
     */
    public function __construct(public readonly array $data) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->data['subject']);
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.weekly_report',
            with: ['data' => $this->data],
        );
    }
}

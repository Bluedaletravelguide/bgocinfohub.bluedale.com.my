<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DailyTaskSummary extends Mailable
{
    use Queueable, SerializesModels;

    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function envelope(): Envelope
    {
        $total = count($this->data['pending']) + count($this->data['expired']) + count($this->data['completed']);
        return new Envelope(
            subject: "Daily Task Summary - {$total} task(s)"
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.daily-task-summary',
        );
    }
}

<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BulkEmail extends Mailable
{
    use Queueable, SerializesModels;

    public string $content;

    public function __construct(public string $subjectLine, string $content, public ?User $recipient = null)
    {
        $this->content = $content;
    }

    public function build()
    {
        return $this->subject($this->subjectLine)
            ->view('emails.bulk')
            ->with([
                'content' => $this->content,
                'recipient' => $this->recipient,
            ]);
    }
}

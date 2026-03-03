<?php

namespace Horsefly\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class RandomEmail extends Mailable
{
    use Queueable, SerializesModels;
    public $mailData=array();
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($mailData)
    {
        $this->mailData = $mailData;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from($address = 'customerservice@kingsburypersonnel.com', $name = 'Kingsbury Personnel Ltd')
            ->subject($this->mailData['subject'])
            ->markdown('Email.random_email')
            ->with('mailData', $this->mailData);
    }
}

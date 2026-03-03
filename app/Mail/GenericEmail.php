<?php

namespace Horsefly\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class GenericEmail extends Mailable
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
        return $this->from($address = 'info@kingsburypersonnel.com', $name = 'Kingsbury Personnel Ltd')
        ->subject("New Job Vacancy From Kingsburypersonnel")
        ->markdown('Email.generic_email')
        ->with('mailData', $this->mailData);
    }
}

<?php

namespace Horsefly\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Horsefly\SentEmail;
use Horsefly\Mail\RandomEmail;
use Horsefly\Mail\GenericEmail;
use Illuminate\Support\Facades\Log;


class SendUnsentEmails extends Command
{
    protected $signature = 'emails:send';

    protected $description = 'Send unsent emails from the database';

     public function handle()
    {
        // Get unsent emails in batches of 100
        $unsentEmails = SentEmail::where('status', '0')->where('action_name', 'Random Email')->take(150)->get();
		Logger($unsentEmails);
        if (count($unsentEmails) > 0) {
            foreach ($unsentEmails as $emailRecord) {
                $mailData = [
                    'subject' => $emailRecord->subject,
                    'body' => $emailRecord->template
                ];

                // Validate the email address
                if (!$this->isValidEmail($emailRecord->sent_to)) {
                    Log::error('Invalid email address: ' . $emailRecord->sent_to);
                    $emailRecord->status = '2'; // Assuming '2' indicates a failed status
                    $emailRecord->save();
                    continue;
                }

                try {
                    if ($emailRecord->action_name == 'Random Email') {
                        Mail::to($emailRecord->sent_to)->send(new RandomEmail($mailData)); // Send the email
                    } else {
                        Mail::to($emailRecord->sent_to)->send(new GenericEmail($mailData)); // Send the email
                    }

                    // Update the status to 1 (sent)
                    $emailRecord->status = '1';
                    $emailRecord->save();

                    // Log successful email
                    Log::info('Email sent successfully to: ' . $emailRecord->sent_to);
                } catch (Exception $e) {
                    // Log the error
                    Log::error('Failed to send email to: ' . $emailRecord->sent_to . ' Error: ' . $e->getMessage());
                    // Mark the email record as failed
                    $emailRecord->status = '2'; // Assuming '2' indicates a failed status
                    $emailRecord->save();
                }

                // Additional processing if needed
            }

            $this->info('Unsent emails processed successfully.');
        } else {
            $this->info('There is no data.');
        }
    }

    private function isValidEmail($email)
    {
        // Regular expression to validate email according to RFC 2822
        $regex = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';
        return preg_match($regex, $email);
    }
}

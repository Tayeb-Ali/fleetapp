<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Mail test (legacy mailtest 186): php artisan mail:test {email}
 */
class SendTestNotificationMail extends Command
{
    protected $signature = 'mail:test {email : Address to send the test email to}';

    protected $description = 'Send a test fleet notification email';

    public function handle(): int
    {
        $email = (string) $this->argument('email');

        Mail::raw(
            'Email notifications are working.',
            function ($message) use ($email) {
                $message->to($email)->subject('[Fleet Tracker] Test email');
            }
        );

        $this->info("Test email sent to {$email}.");

        return self::SUCCESS;
    }
}

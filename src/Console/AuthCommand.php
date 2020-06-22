<?php


namespace Ushahidi\Gmail\Console;

use Ushahidi\Gmail\Facades\Gmail;
use Illuminate\Console\Command;

class AuthCommand extends Command
{
    protected $name = 'gmail:auth';

    protected $signature = 'gmail:auth {--email=} {--force}';

    protected $description = 'Gmail Authentication';

    public function handle()
    {
        $this->info('Authenticate Gmail');

        $email = $this->option('email') ?: $this->ask(
            'Enter an email account for authentication'
        );

        Gmail::setUser($email);

        $authUrl = Gmail::login();

        $this->info("{$authUrl}");

        $authCode = $this->ask('Enter Authentication Code');

        Gmail::authenticate($authCode);

        $this->info('Authentication Successful');
    }
}
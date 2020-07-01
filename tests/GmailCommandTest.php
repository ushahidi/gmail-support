<?php


namespace Ushahidi\Gmail\Tests;

use Illuminate\Support\Facades\Artisan;

class GmailCommandTest extends TestCase
{
    /** @test */
    function test_the_gmail_auth_command()
    {
        Artisan::call('gmail:auth');
    }
}
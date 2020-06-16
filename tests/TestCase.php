<?php


namespace Ushahidi\Gmail\Tests;

use Ushahidi\Gmail\GmailServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        // additional setup
    }

    protected function getPackageProviders($app)
    {
        return [
            GmailServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $config = [
            'client_id' => 'abcdefgh',
            'client_secret' => 'ijklmnopqrstuvwxyz',
            'redirect_uri' => 'urn:ietf:wg:oauth:2.0:oob',
        ];

        $app['config']->set('services.gmail', $config);
    }
}
<?php

namespace Ushahidi\Gmail\Tests;

use Mockery as M;
use Illuminate\Container\Container;
use Ushahidi\Gmail\Gmail;
use Ushahidi\Gmail\Contracts\TokenStorage;
use Ushahidi\Gmail\TokenDiskStorage;

class GmailTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $config = [
            'client_id' => '',
            'client_secret' => '',
            'redirect_uri' => '',
        ];

        $app['config']->set('services.gmail', $config);
    }

    public function testTokenStorage()
    {
      $gmail = $this->app->make('gmail');

      $this->assertInstanceOf(TokenStorage::class, $gmail->getStorage());
    }

    public function testAddAccessToken()
    {
        $token = [
            "access_token" => "ya29.a0AfH62niBTQPMJVN8586Y2C7Vh3tvy28wCrvlOaIsDJid8J6MX09ZD-ODTHnBRNkxgklFvAH787Wd7TxBIRAV--ZZq_Y7jgsFrG5AwI_2vfqmGlZ-gSGwL3bpUnIsB3DbQy3AvUH2THCu3xwyEKEtJL2eGwKaDPzSRB",
            "expires_in" => 3599,
            "refresh_token" => "1\/\/05JP6nP59JONHCgYIARAAGAMSNwF-L9IrF-ilOpqejACiSKbctYSi9922W4SkOTUQDMheo6spquWnv18B2SMA5GcIVL1PcnJ2jcRs",
            "scope" => "https:\/\/mail.google.com\/",
            "token_type" => "Bearer",
            "created" => 1589303472
        ];

        $gmail = $this->app->make(Gmail::class);
        $gmail->addAccessToken($token);

        $this->assertTrue($gmail->hasToken());
        $this->assertEquals($token, $gmail->getAccessToken());
    }

    public function testAuthentication()
    {
        $mockGmail = M::mock(Gmail::class);

        app()->bind('gmail', function() use ($mockGmail) {
           return $mockGmail;
        });

        $mockGmail->shouldReceive('storage')
            ->once()
            ->andReturn(app(TokenDiskStorage::class));

        $mockGmail->shouldReceive('fetchAccessTokenWithAuthCode')
            ->andReturn([
                "access_token" => "ya29.a0AfH62niBTQPMJVN8586Y2C7Vh3tvy28wCrvlOaIsDJid8J6MX09ZD-ODTHnBRNkxgklFvAH787Wd7TxBIRAV--ZZq_Y7jgsFrG5AwI_2vfqmGlZ-gSGwL3bpUnIsB3DbQy3AvUH2THCu3xwyEKEtJL2eGwKaDPzSRB",
                "expires_in" => 3599,
                "refresh_token" => "1\/\/05JP6nP59JONHCgYIARAAGAMSNwF-L9IrF-ilOpqejACiSKbctYSi9922W4SkOTUQDMheo6spquWnv18B2SMA5GcIVL1PcnJ2jcRs",
                "scope" => "https:\/\/mail.google.com\/",
                "token_type" => "Bearer",
                "created" => 1589303472
            ]);

        $mockGmail->shouldReceive('getProfile')
            ->andReturn([
                'emailAddress' => 'test@gmail.com'
            ]);

        $gmail = $this->app->make('gmail');
        
        $gmail->authenticate('test_auth_token');

        $this->assertTrue($gmail->hasToken());

    }
}
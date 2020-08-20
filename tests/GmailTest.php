<?php

namespace Ushahidi\Gmail\Tests;

use Ushahidi\Gmail\Gmail;
use Ushahidi\Gmail\Contracts\TokenStorage;

class GmailTest extends TestCase
{
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

        $this->assertEquals($token, $gmail->getAccessToken());
    }
}
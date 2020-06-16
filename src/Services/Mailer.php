<?php

namespace Ushahidi\Gmail\Services;

use Google_Service_Gmail;
use Ushahidi\Gmail\Gmail;

class Mailer
{
    protected $service;


    public function __construct(Gmail $client, $params = [])
    {
        $this->service = new Google_Service_Gmail($client);
    }
}

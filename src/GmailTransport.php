<?php

namespace Ushahidi\Gmail;

use Illuminate\Mail\Transport\Transport;

class GmailTransport extends Transport
{
    protected $client;

    public function __construct($client, $key)
    {
        $this->key = $key;
        $this->client = $client;
    }

}

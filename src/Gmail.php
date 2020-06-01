<?php

namespace Ushahidi\Gmail;

use Google_Service_Gmail;

class Gmail 
{
    public $client;

    public $service;

    /**
	 * Gmail constructor.
	 *
	 * @param GmailConnection $client
	 */
	public function __construct(GmailConnection $client)
	{
		$this->client = $client;
		$this->service = new Google_Service_Gmail($client);
    }
    
}
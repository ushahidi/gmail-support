<?php

namespace Ushahidi\Gmail;

use Google_Service_Gmail;
use Ushahidi\Gmail\Concerns\Mail;
use Ushahidi\Gmail\Concerns\Mailbox;

class Gmail
{
    use Mail, Mailbox;

    public $client;

    public $service;

    /**
     * Optional parameter for getting single and multiple emails
     *
     * @var array
     */
    protected $params = [];

    /**
     * Gmail constructor.
     *
     * @param GmailConnector $client
     */
    public function __construct(GmailConnector $client)
    {
        $this->client = $client;
        $this->service = new Google_Service_Gmail($client);
    }
    
    public function getUser()
    {
        return $this->client->user();
    }

    /**
     * Gets the URL to authorize the user
     *
     * @return string
     */
    public function getAuthUrl()
    {
        return $this->client->createAuthUrl();
    }
}

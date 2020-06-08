<?php

namespace Ushahidi\Gmail;

use Ushahidi\Gmail\Concerns\Mail;
use Ushahidi\Gmail\Concerns\Mailbox;

class Gmail extends GmailConnector
{
    use Mail, Mailbox;

    public $user;

    /**
     * Optional parameter for getting single and multiple emails
     *
     * @var array
     */
    protected $params = [];

    /**
     * Gmail constructor.
     * @param $config
     * @param $user
     */
    public function __construct($config, $user = null)
    {
        if (class_basename($config) === 'Application') {
            $config = $config['config'];
        }
        $this->user = $user;
        parent::__construct($config, $user);
    }
    
    public function getUser()
    {
        return $this->getProfile();
    }

    /**
     * Gets the URL to authorize the user
     *
     * @return string
     */
    public function getAuthUrl()
    {
        return $this->createAuthUrl();
    }
}

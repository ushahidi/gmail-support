<?php

namespace Ushahidi\Gmail;

use Exception;
use Ushahidi\Gmail\Services\Mail;
use Ushahidi\Gmail\Services\Mailbox;

class Gmail extends GmailConnector
{
    public $user;

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
    
    public function user()
    {
        return $this->getProfile();
    }

    public function mailbox($params = [])
    {
        if (!$this->check()) {
            throw new Exception('No token credentials found.');
        }

        return new Mailbox($this, $params);
    }

    /**
     * Gets the URL to authorize the user
     *
     * @return string
     */
    public function login()
    {
        return $this->createAuthUrl();
    }

    public function logout()
    {
        $this->revokeToken();
        $this->deleteAccessToken();
    }

    /**
     * Check
     *
     * @return bool
     */
    public function check()
    {
        return !$this->isAccessTokenExpired();
    }
}

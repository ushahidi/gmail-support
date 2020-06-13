<?php

namespace Ushahidi\Gmail;

use Exception;
use Ushahidi\Gmail\Services\Mailer;
use Ushahidi\Gmail\Services\Mailbox;

class Gmail extends GmailClient
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

    public function mailer()
    {
        // Create magic here...
    }

    /**
     * Gets the URL to authorize the user
     *
     * @param null $email
     * @return string
     */
    public function login($email = null)
    {
        $this->setLoginHint($email ?? $this->user);
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

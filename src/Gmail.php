<?php

namespace Ushahidi\Gmail;

use Exception;
use Google_Service_Gmail;
use Google_Service_Gmail_Profile;
use Ushahidi\Gmail\Services\Mailer;
use Ushahidi\Gmail\Services\Mailbox;

class Gmail extends Client
{
    public $user;

    public $service;

    /**
     * Gmail constructor.
     * @param $config
     * @param $user
     */
    public function __construct($config = [], $user = null)
    {
        parent::__construct($config, $user);

        $this->user = $user;
        $this->service = new Google_Service_Gmail($this);
    }

    public function mailbox()
    {
        if (!$this->check()) {
            throw new Exception('No token credentials found.');
        }

        return new Mailbox($this);
    }

    public function mailer()
    {
        if (!$this->check()) {
            throw new Exception('No token credentials found.');
        }

        return new Mailer($this);
    }

    /**
     * @param $code
     * 
     * @return array|string
     * 
     * @throws Exception
     */
    public function authenticate($code)
    {
        $token = $this->fetchAccessTokenWithAuthCode($code);
    
        if (property_exists($me = $this->user(), 'emailAddress')) {
            $this->user = $token['email'] = $me->emailAddress;
        }

        $this->addAccessToken($token);

        return $token;
    }

    /**
     * @param null $email
     * @return string
     */
    public function login($email = null)
    {
        $loginHint = ($email ?? $this->user) ?: '';

        if(!empty($loginHint)) {
            $this->setLoginHint($loginHint);
        }

        $this->setAccessType('offline');

        $this->setScopes(Google_Service_Gmail::GMAIL_READONLY, Google_Service_Gmail::GMAIL_SEND);

        return $this->createAuthUrl();
    }

    public function logout()
    {
        $this->revokeToken();
        $this->deleteAccessToken();
    }

    /**
     * Gets user profile from Gmail
     *
     * @return Google_Service_Gmail_Profile
     */
    public function user()
    {
        return $this->service->users->getProfile('me');
    }

    /**
     * Check
     *
     * @return bool
     */
    public function check()
    {
        return $this->hasToken();
    }
}

<?php

namespace Ushahidi\Gmail;

use Exception;
use Google_Client;
use Google_Service_Gmail;
use Google_Service_Gmail_Profile;
use Ushahidi\Gmail\Contracts\TokenStorage;

class GmailClient extends Google_Client
{
    public $user;

    protected $configuration;

    protected $storage;

    public function __construct($config = null, $user = null)
    {
        $this->configuration = $config;

        $this->user = $user;

        $config = $this->getGmailConfig();

        parent::__construct($config);

        $this->setAccessType($config['access_type']);

        $this->setApprovalPrompt($config['approval_prompt']);

        $this->setScopes(Google_Service_Gmail::MAIL_GOOGLE_COM);

        if ($user && $this->hasToken()) {
            $this->refreshTokenIfNeeded();
        }
    }

    public function getGmailConfig()
    {
        return [
            'access_type' => $this->configuration['services.gmail.access_type'] ?? 'offline',
            'approval_prompt' => $this->configuration['services.gmail.approval_prompt'] ?? 'select_account consent',
            'client_secret' => $this->configuration['services.gmail.client_secret'],
            'client_id' => $this->configuration['services.gmail.client_id'],
            'redirect_uri' => $this->configuration['services.gmail.redirect_url'],
            'state' => $this->configuration['services.gmail.state'],
        ];
    }

    /**
     * @return array|null
     */
    public function getAccessToken()
    {
        return parent::getAccessToken() ?: $this->getToken();
    }

    /**
     * @param $token
     */
    public function addAccessToken($token)
    {
        $this->setAccessToken($token);
        $this->saveAccessToken($token);
    }

    /**
     * Save the token credentials to storage
     *
     * @param array $token
     */
    public function saveAccessToken(array $token)
    {
        $token['email'] = $token['email'] ?: $this->user;

        $this->storage->save($token);
    }

    /**
     * Delete the credentials in a file
     */
    public function deleteAccessToken()
    {
        $this->storage->delete($this->user);
    }

    /**
     * Check if token exists and is expired
     * Throws an AuthException when the auth file its empty or with the wrong token
     *
     * @return bool Returns True if the access_token is expired.
     */
    public function isAccessTokenExpired()
    {
        $token = $this->getAccessToken();

        if ($token) {
            $this->setToken($token);
        }

        return parent::isAccessTokenExpired();
    }

    /**
     * Check and return true if the connection already has a saved token
     *
     * @return bool
     */
    public function hasToken()
    {
        $config = $this->getToken();

        return !empty($config['access_token']);
    }

    /**
     * @param null $key
     * @return mixed
     */
    public function getToken($key = null)
    {
        return $this->storage->get($this->user, $key);
    }

    /**
     * @param $token
     */
    public function setToken($token)
    {
        $this->setAccessToken($token);
    }

    /**
     * @param $code
     * @return array|string
     * @throws Exception
     */
    public function authenticate($code)
    {
        if (!$this->isAccessTokenExpired()) {
            return $this->getAccessToken();
        }

        $token = $this->fetchAccessTokenWithAuthCode($code);
        $me = $this->getProfile();
        if (property_exists($me, 'emailAddress')) {
            $this->user = $me->emailAddress;
            $token['email'] = $me->emailAddress;
        }

        $this->addAccessToken($token);

        return $token;
    }

    /**
     * Gets user profile from Gmail
     *
     * @return Google_Service_Gmail_Profile
     */
    public function getProfile()
    {
        return (new Google_Service_Gmail($this))->users->getProfile('me');
    }

    /**
     * Updates / sets the current user for the service
     *
     * @param $user
     * @return GmailClient
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    public function getStorage()
    {
        return $this->storage;
    }

    /**
     * @param TokenStorage $storage
     * @return GmailClient
     */
    public function setStorage(TokenStorage $storage)
    {
        $this->storage = $storage;
        return $this;
    }

    /**
     * Refresh the auth token if needed
     *
     * @return mixed|null
     */
    private function refreshTokenIfNeeded()
    {
        if ($this->isAccessTokenExpired()) {
            if ($refreshToken = $this->getRefreshToken()) {
                $this->fetchAccessTokenWithRefreshToken($refreshToken);
                $token = $this->getAccessToken();
                $this->addAccessToken($token);

                return $token;
            }
            // Dispatch an Event stating that the user that token has expired and
            // can't be refreshed. This way Listeners can be registered to Event
        }

        return $this->getAccessToken();
    }
}

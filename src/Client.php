<?php

namespace Ushahidi\Gmail;

use Google_Client;
use Ushahidi\Gmail\Contracts\TokenStorage;

class Client extends Google_Client
{
    public $user;

    protected $storage;

    /**
     * GmailClient constructor.
     * @param array $config
     * @param null|string $user
     */
    public function __construct($config = [], $user = null)
    {
        parent::__construct();

        $this->user = $user;

        $this->setClientConfig(
            optional($config['client_id']),
            optional($config['client_secret']),
            optional($config['redirect_uri'])
        );

        if ($user) {
            $this->refreshTokenIfNeeded();
        }
    }

    public function setClientConfig($client_id = null, $client_secret = null, $redirect_uri = '')
    {
        $this->setClientId($client_id);

        $this->setClientSecret($client_secret);

        $this->setRedirectUri($redirect_uri);

        return $this;
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
            $this->setAccessToken($token);
        }

        return parent::isAccessTokenExpired();
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
        $token['email'] = isset($token['email']) ? $token['email'] : $this->user;

        $this->storage->save($token['email'], $token);
    }

    /**
     * Delete the credentials in a file
     */
    public function deleteAccessToken()
    {
        $this->storage->delete($this->user);
    }

    /**
     * Check and return true if the connection already has a saved token
     *
     * @return bool
     */
    public function hasToken()
    {
        $token = $this->refreshTokenIfNeeded();

        return !empty($token['access_token']);
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
     * Updates / sets the current user for the service
     *
     * @param $user
     * @return Client
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return TokenStorage
     */
    public function getStorage()
    {
        return $this->storage;
    }

    /**
     * @param TokenStorage $storage
     * @return Client
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
    protected function refreshTokenIfNeeded()
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

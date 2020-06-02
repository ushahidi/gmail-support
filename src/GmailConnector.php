<?php

namespace Ushahidi\Gmail;

use Google_Client;
use Google_Service_Gmail;
use Illuminate\Support\Facades\Storage;

class GmailConnector extends Google_Client
{
    public $user;

    protected $disk;

    private $_config;

    public function __construct($config)
    {
        if (class_basename($config) === 'Application') {
            $config = $config['config'];
        }

        $this->_config = $config;

        parent::__construct($this->getConfigs());

        $this->configureClient();

        if ($this->hasToken()) {
            $this->refreshTokenIfNeeded();
        }

        $this->disk = Storage::disk('local');
    }

    public function getConfigs()
    {
        return [
            'client_secret' => $this->_config['services.gmail.client_secret'],
            'client_id' => $this->_config['services.gmail.client_id'],
            'redirect_uri' => $this->_config['services.gmail.redirect_url'],
            'state' => isset($this->_config['services.gmail.state']) ? $this->_config['services.gmail.state'] : null,
        ];
    }

    public function config($string = null)
    {
        $file = $this->getFile();

        if ($this->disk->exists($file)) {
            $config = $this->getConfigFromFile($file);

            if ($string) {
                if (isset($config[$string])) {
                    return $config[$string];
                }
            } else {
                return $config;
            }
        }

        return null;
    }

    /**
     * Check and return true if the user has a saved token
     *
     * @return bool
     */
    public function hasToken()
    {
        $config = $this->config();

        return !empty($config['access_token']);
    }

    public function getToken()
    {
        return parent::getAccessToken() ?: $this->config();
    }

    public function setToken($token)
    {
        $this->setAccessToken($token);
    }

    /**
     * @return array|string
     * @throws \Exception
     */
    public function createToken($code)
    {
        if ($this->isAccessTokenExpired()) {
            if (!is_null($code) && !empty($code)) {
                $accessToken = $this->fetchAccessTokenWithAuthCode($code);
                if ($this->haveReadScope()) {
                    $me = $this->getProfile();
                    if (property_exists($me, 'emailAddress')) {
                        $this->emailAddress = $me->emailAddress;
                        $accessToken['email'] = $me->emailAddress;
                    }
                }
                $this->addAccessToken($accessToken);

                return $accessToken;
            } else {
                throw new \Exception('No access token');
            }
        } else {
            return $this->getAccessToken();
        }
    }

    /**
     * Delete the credentials in a file
     */
    public function deleteAccessToken()
    {
        $file = $this->getFile();

        if ($this->disk->exists($file)) {
            $this->disk->delete($file);
        }

        $this->saveConfigToFile($file, []);
    }

    /**
     * Check if token exists and is expired
     * Throws an AuthException when the auth file its empty or with the wrong token
     *
     * @return bool Returns True if the access_token is expired.
     */
    public function isAccessTokenExpired()
    {
        $token = $this->getToken();

        if ($token) {
            $this->setAccessToken($token);
        }

        return parent::isAccessTokenExpired();
    }

    public function getAccessToken()
    {
        $token = parent::getAccessToken() ?: $this->config();

        return $token;
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
     * @param array|string  $token
     */
    public function setAccessToken($token)
    {
        parent::setAccessToken($token);
    }

    /**
     * Save the credentials in a file
     *
     * @param array $config
     */
    public function saveAccessToken(array $config)
    {
        $file = $this->getFile();
        $config['email'] = $this->emailAddress;

        if ($this->disk->exists($file)) {
            if (empty($config['email'])) {
                $savedConfigToken = $this->getConfigFromFile($file);

                if (isset($savedConfigToken['email'])) {
                    $config['email'] = $savedConfigToken['email'];
                }
            }

            $this->disk->delete($file);
        }

        $this->saveConfigToFile($config, $file);
    }

    /**
     * Updates / sets the current user for the service
     *
     * @return \Google_Service_Gmail_Profile
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }
    
    public function setDisk($disk)
    {
        $this->disk = $disk;
        return $this;
    }

    private function configureClient()
    {
        $type = $this->_config['services.gmail.access_type'] ?? 'offline';
        $approval_prompt = $this->_config['services.gmail.approval_prompt'] ?? 'select_account consent';

        $this->setScopes(Google_Service_Gmail::MAIL_GOOGLE_COM);

        $this->setAccessType($type);

        $this->setApprovalPrompt($approval_prompt);
    }

    private function getFile()
    {
        $fileName = $this->getFileName();
        return "gmail/tokens/$fileName.json";
    }

    private function getFileName()
    {
        $user = $this->user;

        $credentialFilename = $this->_config['gmail.credentials_file_name'];
        $allowMultipleCredentials = $this->_config['gmail.allow_multiple_credentials'];

        if (isset($user) && $allowMultipleCredentials) {
            return sprintf('%s-%s', $credentialFilename, $user);
        }

        return $credentialFilename;
    }

    private function getConfigFromFile($file)
    {
        $allowJsonEncrypt = $this->_config['gmail.allow_json_encrypt'];

        if ($allowJsonEncrypt) {
            $config = json_decode(decrypt($this->disk->get($file)), true);
        } else {
            $config = json_decode($this->disk->get($file), true);
        }
    }

    private function saveConfigToFile($config, $file)
    {
        $allowJsonEncrypt = $this->_config['gmail.allow_json_encrypt'];

        if ($allowJsonEncrypt) {
            $this->disk->put($file, encrypt(json_encode($config)));
        } else {
            $this->disk->put($file, json_encode($config));
        }
    }

    /**
     * Refresh the auth token if needed
     *
     * @return mixed|null
     */
    private function refreshTokenIfNeeded()
    {
        if ($this->isAccessTokenExpired()) {
            $this->fetchAccessTokenWithRefreshToken($this->getRefreshToken());
            $token = $this->getAccessToken();
            $this->addAccessToken($token);

            return $token;
        }

        return $this->token;
    }
}

<?php

namespace Ushahidi\Gmail;

use Ushahidi\Gmail\Contracts\TokenStorage;
use Ushahidi\Core\Entity\ConfigRepository;

class TokenConfigStorage implements TokenStorage
{
    protected $configRepo;

    public function __construct(ConfigRepository $configRepo)
    {
        $this->configRepo = $configRepo;
    }

    public function get($email, $key = null)
    {
        $gmailConfig = $this->configRepo->get('gmail');
        $token = $gmailConfig->{"token_for_$email"};

        if ($key && isset($token[$key])) {
            return $token[$key];
        } else {
            return $token;
        }
    }

    public function save($token)
    {
        $gmailConfig = $this->configRepo->get('gmail');
        $dataProvider = $this->configRepo->get('data-provider');

        $credentials = $dataProvider->asArray()['gmail'];
        $credentials['email'] = $token['email'];
        $credentials['authenticated'] = empty($token) ? false : true;

        $gmailConfig->setState([
            "token_for_{$token['email']}" => $token,
        ]);

        $dataProvider->setState([
            'gmail' => $credentials 
        ]);

        $this->configRepo->update($gmailConfig);
        $this->configRepo->update($dataProvider);
    }

    public function delete($email)
    {
        $gmailConfig = $this->configRepo->get('gmail');
        $dataProvider = $this->configRepo->get('data-provider');
        $credentials = $dataProvider->asArray()['gmail'];

        $credentials['authenticated'] = false;

        unset($credentials['email']);

        $dataProvider->setState([
            'gmail' => $credentials
        ]);

        $gmailConfig->setState([
            "token_for_{$email}" => '',
        ]);

        $this->configRepo->update($gmailConfig);
        $this->configRepo->update($dataProvider);
    }
}

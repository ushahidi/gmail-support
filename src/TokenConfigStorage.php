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

    public function get($email, $string = null)
    {
        $gmailConfig = $this->configRepo->get('gmail');
        $token = $gmailConfig->{"token_for_$email"};

        if ($string && isset($token[$string])) {
            return $token[$string];
        } else {
            return $token;
        }
    }

    public function save($email, $token)
    {
        $gmailConfig = $this->configRepo->get('gmail');
        $dataProvider = $this->configRepo->get('data-provider');
        
        $credentials = $dataProvider->asArray()['gmail'];
        $credentials['authenticated'] = empty($token) ? false : true;
       
        $gmailConfig->setState([
            "token_for_$email" => $token,
        ]);

        $dataProvider->setState([
            'gmail' => $credentials
        ]);

        $this->configRepo->update($gmailConfig);
        $this->configRepo->update($dataProvider);
    }

    public function delete($email)
    {
        $this->save($email, []);
    }
}

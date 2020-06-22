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

        if ($string) {
            if (isset($token[$string])) {
                return $token[$string];
            }
        } else {
            return $token;
        }

        return null;
    }

    public function save($email, $token)
    {
        $gmailConfig = $this->configRepo->get('gmail');

        $gmailConfig->setState([
            "token_for_$email" => $token,
        ]);

        $this->configRepo->update($gmailConfig);
    }

    public function delete($email)
    {
        $this->save($email, []);
    }
}

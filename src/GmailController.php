<?php

namespace Ushahidi\Gmail;

use Illuminate\Http\Request;
use Ushahidi\Core\Entity\ConfigRepository;

class GmailController 
{
    protected $configRepo;

    public function __construct(ConfigRepository $configRepo)
    {
        $this->configRepo = $configRepo;
        
        $providers = $this->configRepo->get('data-provider')->asArray();
        $user = isset($providers['gmail']) ? $providers['gmail']['redirect_uri'] : null;
        $config = isset($providers['gmail']) ? [
            'client_id' => $providers['gmail']['client_id'],
            'client_secret' => $providers['gmail']['client_secret'],
            'redirect_uri' => $providers['gmail']['redirect_uri']
        ] : null;

        $this->gmail = app('gmail', $config, $user);
        $this->gmail->setStorage(new TokenConfigStorage($this->configRepo));
    } 

    public function initialize()
    {
        $auth_url = $this->gmail->login();

        return response()->json([
            'message' => 'Authorization URL',
            'auth_url' => $auth_url
        ]);
    }

    public function authorize(Request $request)
    {
        $code = $request->input('code');

        $this->gmail->authenticate($code);

        return response()->json([
            'message' => 'User auth token authorized'
        ], 200);
    }

    public function unauthorize()
    {
        $this->gmail->logout();

        return response()->json([
            'message' => 'Access token for user revoked'
        ]);
    }
}
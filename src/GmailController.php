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
        $this->config = $this->configRepo->get('data-provider')->asArray()['gmail'];

        $this->gmail = app('gmail',
            [
                'client_id' => $this->config['client_id'],
                'client_secret' => $this->config['client_secret'],
                'redirect_uri' => $this->config['redirect_uri']
            ],
            $this->config['user']
        );
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

        $this->gmail->authorize($code);

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
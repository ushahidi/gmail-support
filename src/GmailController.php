<?php

namespace Ushahidi\Gmail;

use Illuminate\Http\Request;
use Ushahidi\Core\Entity\ConfigRepository;

class GmailController
{
    /** @var Gmail */
    public $gmail;

    public $configRepo;

    public function __construct(ConfigRepository $configRepo)
    {
        $this->configRepo = $configRepo;

        $providers = $this->configRepo->get('data-provider')->asArray();

        $user = isset($providers['gmail'])
            ? optional($providers['gmail'])['email']
            : null;

        $config = isset($providers['gmail'])
            ? [
                'client_id'     => $providers['gmail']['client_id'] ?? config('services.gmail.client_id'),
                'client_secret' => $providers['gmail']['client_secret'] ?? config('services.gmail.client_secret'),
                'redirect_uri'  => $providers['gmail']['redirect_uri'] ?? config('services.gmail.redirect_uri')
            ]
            : [
                'client_id'     => config('services.gmail.client_id'),
                'client_secret' => config('services.gmail.client_secret'),
                'redirect_uri'  => config('services.gmail.redirect_uri')
            ];

        $this->gmail = app()->make('gmail', compact('config', 'user'));

        $this->gmail->setStorage(new TokenConfigStorage($configRepo));
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

        $gmailConfig = $this->configRepo->get('gmail');

        $gmailConfig->setState([
            "first_sync_date" => $request->input('date'),
        ]);

        $this->configRepo->update($gmailConfig);

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

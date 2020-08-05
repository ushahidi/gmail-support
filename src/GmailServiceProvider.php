<?php

namespace Ushahidi\Gmail;

use Illuminate\Support\ServiceProvider;

class GmailServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // Main Service
        $this->app->bind('gmail', function ($app, $params) {
            $user   = $params['user'] ?? null;
            $config = $params['config'];
            $gmail  = new Gmail($config, $user);
            $gmail->setStorage($this->app->make(TokenDiskStorage::class));
            return $gmail;
        });

        $this->app->bind(Gmail::class, function ($app) {
            return $app->make('gmail');
        });

        $this->registerGmailSource();

        $this->registerGmailTransport();

        $this->registerCommands();
    }

    /**
     * Register the Gmail data source driver.
     *
     * @return void
     */
    private function registerGmailSource()
    {
        if (! $this->shouldRegisterGmailSourceDriver()) {
            return;
        }

        $this->app['datasources']->extend('gmail', function ($config) {
            return new GmailSource(
                $config, 
                $this->app->make('Ushahidi\Core\Entity\ConfigRepository'), 
                function ($user, $config = null)
                {
                    return $this->app->make('gmail', [
                        'user' => $user,
                        'config' => $config
                    ]);
                }
            );
        });

        $this->app->router->group([
            'namespace' => 'Ushahidi\Gmail'
        ], function($router) {
            require __DIR__.'../../routes/api.php';
        });
    }

    /**
     * Register the Gmail transport driver.
     *
     * @return void
     */
    private function registerGmailTransport()
    {
        if (! $this->shouldRegisterGmailTransportDriver()) {
            return;
        }

        $this->resolveTransportManager()->extend('gmail', function () {
            return new GmailTransport(app('gmail'));
        });
    }

    /**
     * Resolve the mail manager.
     *
     * @return \Illuminate\Mail\TransportManager|\Illuminate\Mail\MailManager
     */
    public function resolveTransportManager()
    {
        if ($this->app->has('mail.manager')) {
            return $this->app['mail.manager'];
        }

        return $this->app['swift.transport'];
    }

    /**
     * Determine if we should register the Gmail data support driver.
     *
     * @return bool
     */
    protected function shouldRegisterGmailSourceDriver()
    {
        if ($this->app->has('datasources')) {
            return true;
        }

        return false;
    }

    /**
     * Determine if we should register the Gmail transport driver.
     *
     * @return bool
     */
    protected function shouldRegisterGmailTransportDriver()
    {
        if ($this->app->has('mail.manager')) {
            return true;
        }

        return $this->app['config']['mail.driver'] === 'gmail';
    }

    public function registerCommands()
    {
        $this->commands([
            Console\AuthCommand::class,
        ]);
    }
}

<?php

namespace Ushahidi\Gmail;

use Illuminate\Support\ServiceProvider;
use Ushahidi\Contracts\Repository\Entity\ConfigRepository;

class GmailServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->bootGmailSource();
    }

    /**
     * Register the Gmail data source driver.
     *
     * @return void
     */
    private function bootGmailSource()
    {
        if (! $this->shouldRegisterGmailSourceDriver()) {
            return;
        }

        $this->app['datasources']->extend('gmail', GmailSource:class, function ($config) {
            return new GmailSource(
                $config,
                $this->app->make(ConfigRepository::class),
                function ($user, $config = null)
                {
                    return $this->app->make('gmail', [
                        'user' => $user,
                        'config' => $config
                    ]);
                }
            );
        });
    }

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
            $config = $params['config'] ?? config('services.gmail');
            $gmail  = new Gmail($config, $user);
            $gmail->setStorage($app->make(TokenDiskStorage::class));
            return $gmail;
        });

        $this->app->bind(Gmail::class, function ($app) {
            return $app->make('gmail');
        });

        $this->registerGmailTransport();

        $this->registerRoutes();

        $this->registerCommands();
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
        return $this->app->has('datasources');
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

    protected function registerRoutes()
    {
        $this->app->router->group([
            'namespace' => 'Ushahidi\Gmail'
        ], function($router) {
            require __DIR__.'../../routes/route.php';
        });
    }

    protected function registerCommands()
    {
        $this->commands([
            Console\AuthCommand::class,
        ]);
    }

    public function provides()
    {
        return ['gmail', Gmail::class];
    }
}

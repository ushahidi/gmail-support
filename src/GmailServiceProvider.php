<?php

namespace Ushahidi\Gmail;

use Illuminate\Support\ServiceProvider;

class GmailServiceProvider extends ServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        // Main Service
        $this->app->bind('gmail', function ($app) {
            $gmail = new Gmail($app['config']);
            $gmail->setStorage($this->app->make(TokenDiskStorage::class));

            return $gmail;
        });

        $this->app->bind(Gmail::class, function ($app) {
            return $app->make('gmail');
        });

        $this->registerGmailSource();

        $this->registerGmailTransport();
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
            return new GmailSource($config);
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
            return new GmailTransport;
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
}

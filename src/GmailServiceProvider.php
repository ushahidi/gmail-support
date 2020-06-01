<?php

namespace Ushahidi\Gmail;

use Illuminate\Support\ServiceProvider;

class PostmarkServiceProvider extends ServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerGmailTransport();

        $this->registerGmailSource();
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

        $this->app['datasource']->extend('gmail', function($config) {
            return new GmailSource($config);
        });
    }

    /**
     * Register the Gmail transport driver.
     *
     * @return void
     */
    private function registerGmailTransportDriver()
    {
        if (! $this->shouldRegisterGmailTransportDriver()) {
            return;
        }

        $this->resolveTransportManager()->extend('gmail', function () {
            return new GmailTransport(
                config('gmail.secret', config('services.gmail.secret'))
            );
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

<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-03-02
 * Time: 05:36
 */

namespace Twdd;

use Illuminate\Support\ServiceProvider;


class TwddServiceProvider extends ServiceProvider
{
    protected $commands = [
    ];

    public function register(){
        $this->app->bind('TwddInvoice', function()
        {
            return app()->make(\Twdd\Helpers\TwddInvoice::class);
        });

        $this->app->bind('GoogleMap', function()
        {
            return app()->make(\Twdd\Helpers\GoogleMap::class);
        });

        $this->app->bind('Infobip', function()
        {
            return app()->make(\Twdd\Helpers\Infobip::class);
        });

        $this->app->bind('SmsMemberRegister', function($app, $params){
            return app()->make(\Twdd\Helpers\Sms\MemberRegister::class);
        });

        $this->app->bind('MemberService', function()
        {
            return app()->make(\Twdd\Helpers\MemberService::class);
        });

        $this->app->bind('TaskService', function()
        {
            return app()->make(\Twdd\Helpers\TaskService::class);
        });

        $this->app->bind('CouponFactory', function()
        {
            return app()->make(\Twdd\Helpers\CouponFactory::class);
        });

        $this->app->bind('Pusher', function()
        {
            return app()->make(\Twdd\Helpers\Pusher::class);
        });

        $this->app->bind('LastCall', function()
        {
            return app()->make(\Twdd\Helpers\LastCall::class);
        });

        $this->app->bind('PushNotification', function()
        {
            return app()->make(\Twdd\Helpers\PushNotification::class);
        });

        $this->registerAliases();
    }

    public function boot(){
        if ($this->isLumen()) {
            require_once 'Lumen.php';
        }

        if ($this->app->runningInConsole()) {
            $this->commands($this->commands);
        }

        $this->loadTranslationsFrom(__DIR__ . '/lang', 'twdd');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            Twdd\TwddServiceProvider::class,
        ];
    }

    /**
     * Register aliases.
     *
     * @return null
     */
    protected function registerAliases()
    {
        if (class_exists('Illuminate\Foundation\AliasLoader')) {
            $loader = \Illuminate\Foundation\AliasLoader::getInstance();

            $loader->alias('GoogleMap', \Twdd\Facades\GoogleMap::class);
            $loader->alias('Infobip', \Twdd\Facades\Infobip::class);
            $loader->alias('TwddInvoice', \Twdd\Facades\TwddInvoice::class);
            $loader->alias('SmsMemberRegister', \Twdd\Facades\SmsMemberRegister::class);
            $loader->alias('MemberService', \Twdd\Facades\MemberService::class);
            $loader->alias('TaskService', \Twdd\Facades\TaskService::class);
            $loader->alias('CouponFactory', \Twdd\Facades\CouponFactory::class);
            $loader->alias('Pusher', \Twdd\Facades\Pusher::class);
            $loader->alias('TaskNo', \Twdd\Services\Task\TaskNo::class);
            $loader->alias('LastCall', \Twdd\Facades\LastCall::class);
            $loader->alias('PushNotification', \Twdd\Facades\PushNotification::class);
        }
    }

    protected function isLumen()
    {
        return str_contains($this->app->version(), 'Lumen');
    }
}

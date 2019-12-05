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
        $this->app->bind('Bank', function()
        {
            return app()->make(\Twdd\Helpers\Bank::class);
        });

        $this->app->bind('CouponFactory', function()
        {
            return app()->make(\Twdd\Helpers\CouponFactory::class);
        });

        $this->app->bind('CouponService', function()
        {
            return app()->make(\Twdd\Helpers\CouponServiceHelper::class);
        });

        $this->app->bind('CouponValid', function()
        {
            return app()->make(\Twdd\Helpers\CouponValid::class);
        });

        $this->app->bind('DriverService', function()
        {
            return app()->make(\Twdd\Helpers\DriverService::class);
        });

        $this->app->bind('GoogleMap', function()
        {
            return app()->make(\Twdd\Helpers\GoogleMap::class);
        });

        $this->app->bind('Infobip', function()
        {
            return app()->make(\Twdd\Helpers\Infobip::class);
        });

        $this->app->bind('LastCall', function()
        {
            return app()->make(\Twdd\Helpers\LastCall::class);
        });

        $this->app->bind('LatLonService', function()
        {
            return app()->make(\Twdd\Helpers\LatLonService::class);
        });

        $this->app->bind('MemberService', function()
        {
            return app()->make(\Twdd\Helpers\MemberService::class);
        });

        $this->app->bind('MoneyAccount', function()
        {
            return app()->make(\Twdd\Helpers\MoneyAccount::class);
        });

        $this->app->bind('PayService', function($app, $params){

            return app()->make(\Twdd\Helpers\PayService::class);
        });

        $this->app->bind('PushNotification', function()
        {
            return app()->make(\Twdd\Helpers\PushNotification::class);
        });

        $this->app->bind('Pusher', function()
        {
            return app()->make(\Twdd\Helpers\Pusher::class);
        });

        $this->app->bind('PushService', function()
        {
            return app()->make(\Twdd\Helpers\PushService::class);
        });

        $this->app->bind('RedisPushService', function()
        {
            return app()->make(\Twdd\Services\PushNotification\RedisPushService::class);
        });

        $this->app->bind('SettingPriceService', function($app, $params){

            return app()->make(\Twdd\Helpers\SettingPriceServiceHelper::class);
        });

        $this->app->bind('SmsMemberRegister', function($app, $params){

            return app()->make(\Twdd\Helpers\Sms\MemberRegister::class);
        });

        $this->app->bind('TaskDone', function()
        {
            return app()->make(\Twdd\Helpers\TaskDoneHelper::class);
        });

        $this->app->bind('TaskService', function()
        {
            return app()->make(\Twdd\Helpers\TaskService::class);
        });

        $this->app->bind('TokenService', function()
        {
            return app()->make(\Twdd\Helpers\TokenService::class);
        });

        $this->app->bind('TwddCache', function()
        {
            return app()->make(\Twdd\Helpers\TwddCache::class);
        });

        $this->app->bind('TwddInvoice', function()
        {
            return app()->make(\Twdd\Helpers\TwddInvoice::class);
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

        $this->publishes([
            __DIR__.'/resources/views' => resource_path('views'),
        ]);
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

            $loader->alias('Bank', \Twdd\Facades\Bank::class);
            $loader->alias('CouponFactory', \Twdd\Facades\CouponFactory::class);
            $loader->alias('CouponService', \Twdd\Facades\CouponService::class);
            $loader->alias('CouponValid', \Twdd\Facades\CouponValid::class);
            $loader->alias('DriverService', \Twdd\Facades\DriverService::class);
            $loader->alias('GoogleMap', \Twdd\Facades\GoogleMap::class);
            $loader->alias('Infobip', \Twdd\Facades\Infobip::class);
            $loader->alias('LastCall', \Twdd\Facades\LastCall::class);
            $loader->alias('LatLonService', \Twdd\Facades\LatLonService::class);
            $loader->alias('MemberService', \Twdd\Facades\MemberService::class);
            $loader->alias('MoneyAccount', \Twdd\Facades\MoneyAccount::class);
            $loader->alias('PayService', \Twdd\Facades\PayService::class);
            $loader->alias('Pusher', \Twdd\Facades\Pusher::class);
            $loader->alias('PushNotification', \Twdd\Facades\PushNotification::class);
            $loader->alias('PushService', \Twdd\Facades\PushService::class);
            $loader->alias('RedisPushService', \Twdd\Facades\RedisPushService::class);
            $loader->alias('SettingPriceService', \Twdd\Facades\SettingPriceService::class);
            $loader->alias('SmsMemberRegister', \Twdd\Facades\SmsMemberRegister::class);
            $loader->alias('TaskDone', \Twdd\Facades\TaskDone::class);
            $loader->alias('TwddCache', \Twdd\Facades\TwddCache::class);
            $loader->alias('TwddInvoice', \Twdd\Facades\TwddInvoice::class);
            $loader->alias('TaskNo', \Twdd\Services\Task\TaskNo::class);
            $loader->alias('TaskService', \Twdd\Facades\TaskService::class);
            $loader->alias('TokenService', \Twdd\Facades\TokenService::class);
        }
    }

    protected function isLumen()
    {
        return str_contains($this->app->version(), 'Lumen');
    }
}

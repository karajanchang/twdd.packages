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

        $this->app->bind('MemberService', function()
        {
            return app()->make(\Twdd\Helpers\MemberService::class);
        });

        $this->app->bind('TaskService', function()
        {
            return app()->make(\Twdd\Helpers\TaskService::class);
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
            $loader->alias('MemberService', \Twdd\Facades\MemberService::class);
            $loader->alias('TaskService', \Twdd\Facades\TaskService::class);
        }
    }

    protected function isLumen()
    {
        return str_contains($this->app->version(), 'Lumen');
    }
}

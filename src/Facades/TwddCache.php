<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 2019-04-28
 * Time: 14:35
 */
namespace Twdd\Facades;

use Illuminate\Support\Facades\Facade;

class TwddCache extends Facade
{
    protected static function getFacadeAccessor() { return 'TwddCache'; }
}


/*
 * 注意：tag第二層以後是多筆的才要加id
 *
 * 一個駕駛只會有一個 profile
 * TwddCache::driver($id)->DriverProfile()->key('DriverProfile', $id)->get();
 *
 * 一個駕駛會有多筆的 News
 * TwddCache::driver($id)->DriverNews($id)->key('DriverNewsList')->get();
 *
 *
 *  Put
 *  $cache = TwddCache::dirver(28)->BB()->key('DriverState', 28)->put('BBBBBBB', $seconds = null);
 *
 * Add  return true or false
 *  $cache = TwddCache::dirver(28)->BB()->key('DriverState', 28)->add('BBBBBBB', $seconds = null);
 *
 * Get 1
 * $cache = TwddCache::driver(27)->AA()->key('DriverState', 27)->get($default = null);
 *
 * Get 2
 * $cache = TwddCache::key('DriverState', 27)->get($default = null);
 *
 * forget
 * $cache = TwddCache::key('DriverState', 27)->forget();
 *
 * has
 * $cache = TwddCache::key('DriverState', 27)->has();
 *
 * flush  若要flush tags 一定要指定tags，不然就會全刪
 * TwddCache::driver(27)->AA()->key('DriverState', 27)->flush();
 *
 *
 * Get default值用closure
 * $cache = TwddCache::driver(27)->key('DriverState', 27)->get(function(){
 *          return \App\Driver::find(35)->id;
 *  });
 *
 * Remember
 * $cache = TwddCache::driver(27)->key('DriverState', 27)->remember(function(){
 *          return \App\Driver::find(35)->id;
 * }, $seconds = null);
 *
 * RememberForever
 * $cache = TwddCache::driver(27)->key('DriverState', 27)->rememberForever(function(){
 *          return \App\Driver::find(35)->id;
 * });
 *
 */
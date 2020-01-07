<?php
/*
 * 注意：tag第二層以後是多筆的才要加id
 *
 * 一個司機只會有一個 profile
 * TwddCache::driver($id)->DriverProfile()->key('DriverProfile', $id)->get();
 *
 * 一個司機會有多筆的 News
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


namespace Twdd\Helpers;


use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TwddCache
{
    private $key = '';
    private $seconds = 1200;
    private $tags = [];
    private $first_key = '';
    private $pre_key = 'MapCacheKey-';


    public function __call($tag, $params)
    {
        array_push($this->tags, $tag);
        foreach($params as $param){
            if(is_array($param) || is_object($param)){

                throw new \Exception('Cache parameters of tags must not be array or object');
            }
            array_push($this->tags, $tag.(string) $param);
        }

        return $this;
    }

    /**
     * @param mixed ...$params
     * @return $this
     * @throws \Exception
     */
    public function key(...$params){
        $key = '';
        if(count($params)){
            foreach($params as $k => $param){
                if($k==0){
                    if(!is_string($param)){

                        throw new \Exception('Cache first key must not be string');
                    }
                    $this->first_key = $param;
                }
                $key .= $param;
            }
        }

        $this->key = env('APP_TYPE', 'developement').$key;

        return $this;
    }

    /**
     * @param null $default
     * @return mixed
     */
    public function get($default = null){
        $tags = $this->getTags();

        if(count($tags)) {
            $res = Cache::tags($tags)->get($this->key, $default);
            $this->clearTags();
        }else {
            $res = Cache::get($this->key, $default);
        }

        return $res;
    }

    private function getTags(){
        $rtags = [];
        if(count($this->tags)) {
            $rtags = $this->tags;
        }else {
            $tags = $this->getMap();
            if (is_array($tags) && count($tags)) {
                $rtags = $tags;
            }
        }

        return $rtags;
    }

    private function getMap(){
        $k = $this->pre_key.$this->key;
        $tags = Cache::get($k);

        return $tags;
    }

    private function setMap(){
        if(count($this->tags)){
            $k = $this->pre_key.$this->key;
            Cache::put($k, $this->tags, $this->seconds);
        }
    }

    /**
     * @param null $seconds
     */
    public function expired($seconds = null){
        $s = is_null($seconds) ? $this->seconds : $seconds;
        $this->seconds = $s;
    }


    /**
     * @param $value
     * @param null $seconds
     * @return bool
     */
    public function add($value, $seconds = null){
        $this->expired($seconds);

        if(count($this->tags)) {
            $this->setMap();
            $this->clearTags();

            return Cache::tags($this->tags)->add($this->key, $value, $this->seconds);
        }

        $this->clearTags();
        return Cache::add($this->key, $value, $this->seconds);
    }

    /**
     * @param $value
     * @param null $seconds
     * @return $this
     */
    public function put($value, $seconds = null){
        $this->expired($seconds);

        if(count($this->tags)) {
            $this->setMap();
            Cache::tags($this->tags)->put($this->key, $value, $this->seconds);
            $this->clearTags();

            return $this;
        }

        Cache::put($this->key, $value, $this->seconds);
        $this->clearTags();

        return $this;
    }

    /**
     * @param $value
     * @param null $seconds
     * @return $this
     */
    public function remember($value, $seconds = null){
        $this->expired($seconds);

        if(count($this->tags)) {
            $this->setMap();
            Cache::tags($this->tags)->remember($this->key, $this->seconds, $value);
            $this->clearTags();

            return $this;
        }

        Cache::remember($this->key, $this->seconds, $value);
        $this->clearTags();

        return $this;
    }


    /**
     * @param $value
     * @return $this
     */
    public function rememberForever($value){

        if(count($this->tags)) {
            $this->setMap();
            Cache::tags($this->tags)->rememberForever($this->key, $value);
            $this->clearTags();

            return $this;
        }

        Cache::rememberForever($this->key, $value);
        $this->clearTags();

        return $this;
    }

    /**
     * @return bool
     */
    public function has(){

        $tags = $this->getTags();
        if(count($tags)){
            $this->clearTags();

            Log::info('TwddCache has '.$this->key.': ', $tags);

            return Cache::tags($tags)->has($this->key);
        }

        return Cache::has($this->key);
    }

    /**
     * @return bool
     */
    public function forget(){

        $tags = $this->getTags();
        if(count($tags)){
            $this->clearTags();

            Log::info('TwddCache foget '.$this->key.': ', $tags);
            return Cache::tags($tags)->forget($this->key);
        }

        $this->clearTags();
        return Cache::forget($this->key);
    }

    /**
     * @return $this
     */
    public function flush(array $tags = []){
        if(count($tags)) {
            Log::info('TwddCache flush: ', $tags);
            Cache::tags($tags)->flush();

            return $this;
        }

        Cache::flush();

        return $this;
    }

    private function clearTags(){
        $this->tags = [];
    }

}
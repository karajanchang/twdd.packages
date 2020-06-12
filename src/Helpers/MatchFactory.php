<?php


namespace Twdd\Helpers;



use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Twdd\Services\Match\CallTypes\InterfaceMatchCallType;

class MatchFactory
{
    private $lut = null;
    const cache_key = 'MatchFactoryCacheLut';

    public function __construct()
    {
        $this->lut = $this->cacheLut();
    }

    public function bind(int $call_type = 1, bool $clear_lut_cache = false){
        if($clear_lut_cache===true){
            Cache::forget(self::cache_key);
        }
        $className = $this->lut->get($call_type);
        App::bind(InterfaceMatchCallType::class, $className);
    }

    private function cacheLut(){
        $lut_file_user =  base_path('/config/lut_call_types.php');
        $lut_file_twdd =  base_path('/vendor/twdd/packages/src/config/lut_call_types.php');

        if(Cache::has(self::cache_key)){

            return Cache::get(self::cache_key);
        }

        $lut_file = $lut_file_user;
        if(!file_exists($lut_file)) {
            $lut_file = $lut_file_twdd;
            if(!file_exists($lut_file)) {
                throw new \Exception('MatchFactory lut file does not exists!!! :'.$lut_file);
            }
        }

        $luts = include $lut_file;
        $content = Collection::make($luts);

        $now = Carbon::now();
        Cache::put(static::cache_key, $content, $now->addDays(1));

        return $content;
    }



}

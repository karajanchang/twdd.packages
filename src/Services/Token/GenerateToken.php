<?php


namespace Twdd\Services\Token;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Twdd\Models\LoginIdentify;

class GenerateToken
{
    private $request;
    
    private $lut = [
        'driver' => 'Driver',
        'member' => 'User',
    ];
    
    /**
     * GenerateToken constructor.
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
    
    private function getKey(LoginIdentify $loginIdentify){
        $collection = new Collection($this->lut);
        $type = $collection->get($loginIdentify['type'], null);
        if(is_null($type)){
            throw new Exception('Must have type!'); 
        }
        
        return env('APP_TYPE').ucfirst($type).$loginIdentify->id;
    }

    private function processExpireAt2Lunch(){
        $expiredAt = Carbon::create(null, null, null, 12, 0, 0);

        return $expiredAt->addDays(env('LOGIN_TOKEN_DAYS', 14));
    }
    
    public function generate(LoginIdentify $loginIdentify){
        $expiredAt = $this->processExpireAt2Lunch();

        $key = $this->getKey($loginIdentify);
        $token = Hash::make($key);
        $keyToken = env('APP_TYPE').'Token'.$token;

        Cache::put($key, $token, $expiredAt);
        Cache::put($keyToken, $loginIdentify->id, $expiredAt);
        Log::info('---------------token: '.$token.'--expiredAt: '.$expiredAt->toDateTimeString());

        return [
            "id"    =>  $loginIdentify->id,
            "token" =>  $token,
            "expired"   =>  [
                "timestamp"      =>  $expiredAt->timestamp,
                "seconds"   =>   ($expiredAt->timestamp - time()),
            ]
        ];

    }

    public function reCacheTokenById(string $type, int $id){
        $expiredAt = Carbon::now()->addDays(env('LOGIN_TOKEN_DAYS', 14));

        $loginIdentify = new LoginIdentify();
        $loginIdentify['id'] = $id;
        $loginIdentify['type'] = $type;
        $key = $this->getKey($loginIdentify);

        $token = app(Request::class)->header('token');
        $keyToken = env('APP_TYPE').'Token'.$token;

        Cache::put($key, $token, $expiredAt);
        Cache::put($keyToken, $loginIdentify->id, $expiredAt);
    }

    public function forget(string $type, int $id){
        $loginIdentify = new LoginIdentify();
        $loginIdentify['id'] = $id;
        $loginIdentify['type'] = $type;
        $key = $this->getKey($loginIdentify);

        Cache::forget($key);
    }

    public function id(){
        $token = app(Request::class)->header('token');
        $keyToken = env('APP_TYPE').'Token'.$token;
        Log::info('---------------token: '.$token);

        return Cache::get($keyToken);
    }
}
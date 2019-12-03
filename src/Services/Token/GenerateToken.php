<?php


namespace Twdd\Services\Token;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
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
    
    public function generate(LoginIdentify $loginIdentify){
        $expiredAt = Carbon::now()->addDays(env('LOGIN_TOKEN_DAYS', 14));
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

        $token = $this->request->header('token');
        $keyToken = env('APP_TYPE').'Token'.$token;

        Cache::put($key, $token, $expiredAt);
        Cache::put($keyToken, $loginIdentify->id, $expiredAt);
    }

    public function id(){
        $token = $this->request->header('token');
        $keyToken = env('APP_TYPE').'Token'.$token;
        Log::info('---------------token: '.$token);

        return Cache::get($keyToken);
    }
}
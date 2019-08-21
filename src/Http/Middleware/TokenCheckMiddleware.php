<?php


namespace Twdd\Http\Middleware;


use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class TokenCheckMiddleware
{
    public function handle($request, Closure $next, $guard = null)
    {

        $token = $request->header("token");
        if(!isset($token) || strlen($token)==0){
            return response('no token.', 401);
        }
        $keyToken = env('APP_TYPE').'Token'.$token;
        $driver_id = (int) Cache::get($keyToken);
        if(!isset($driver_id) || $driver_id==0){
            return response('token error.', 401);
        }
        $key = env('APP_TYPE').'Driver'.$driver_id;
        $token2 = Cache::get($key);
        if($token!=$token2){
            return response('token error.', 401);
        }
        if (!Hash::check($key, $token)){
            return response('token error.', 401);
        }

        /*
        if(!isset($all["method"])){
            return response('No method.', 401);
        }*/

        return $next($request);
    }
}
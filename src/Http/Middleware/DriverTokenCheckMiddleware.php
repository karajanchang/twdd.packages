<?php


namespace Twdd\Http\Middleware;


use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class DriverTokenCheckMiddleware
{
    private $type = 'Driver';

    public function handle($request, Closure $next, $guard = null)
    {

        $token = $request->header("token");
        if(!isset($token) || strlen($token)==0){
            Log::info('No token: ', $request->all());

            return response('no token.', 401);
        }

        $keyToken = env('APP_TYPE').'Token'.$token;
        $id = (int) Cache::get($keyToken);
        if(!isset($id) || $id==0){
            Log::info('Error token1 ('.$token.'): ', ['keyToken' => $keyToken , 'request' => $request->all()]);

            return response('token error.', 401);
        }

        $key = env('APP_TYPE').$this->type.$id;
        $token2 = Cache::get($key);
        if($token!=$token2){
            Log::info('Error token2 ('.$token2.'): ', ['key' => $key, 'request' => $request->all()]);

            return response('token error.', 401);
        }

        if (!Hash::check($key, $token)){
            Log::info('Error token3 ('.$token.'): ', ['key' => $key , 'request' => $request->all()]);

            return response('token error.', 401);
        }


        return $next($request);
    }
}
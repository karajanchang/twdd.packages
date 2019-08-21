<?php


namespace Twdd\Http\Middleware;


use Closure;

class TrimMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $method = $request->getMethod();
        if($method=='POST' || $method=='PUT') {
            $all = $request->all();
            if (isset($all['params'])) {
                $params = $all['params'];
                $res = array_map('trim', $params);
                $all['params'] = $res;
                $request->merge($all);
            }
        }
        return $next($request);
    }
}
<?php

namespace Twdd\Http\Middleware;

use Closure;

class JsonCheckMiddleware
{
    public function handle($request, Closure $next, $guard = null)
    {
        if ($request->getMethod() == 'POST') {
            $params = $request->input("params");
            $as = $this->parseRoute($request);
            $excepts = ['match.uploadPicStart', 'match.uploadPicDone', 'match.uploadZip', 'match.uploadTask', 'match.uploadPicBefore', 'match.uploadPicAfter', 'match.uploadPicRed'];

            if (isset($params) && count($params) == 0 && !in_array($as, $excepts)) {

                return response(trans('incorrect format'), 400);
            }
        }

        return $next($request);
    }

    private function parseRoute($request)
    {
        $route = $request->route();
        if (is_array($route)) {
            if (isset($route[1]['as'])) {
                return $route[1]['as'];
            }
        }
        return '';
    }
}

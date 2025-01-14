<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {

        $token = $request->bearerToken();
        $request->setUserResolver(function () use ($token)  {
            return User::where('api_token', $token)->first();
        });
        $user = $request->user();

        if (!isset($user)) {
            return response([
                'error' => [
                    'code' => 401,
                    'message' => 'Unauthorized',
                ]
            ], 401);
        }

        return $next($request);
    }
}

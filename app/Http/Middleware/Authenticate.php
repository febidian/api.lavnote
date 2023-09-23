<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        return $request->expectsJson() ? null : route('login');
    }

    public function handle($request, Closure $next, ...$guards)
    {

        try {
            // Cek apakah ada header CSRF-TOKEN
            if (!$request->hasCookie('x-csrf-token')) {
                throw new TokenMismatchException();
            }

            // Cek apakah ada token JWT dalam cookie
            if (!$request->hasCookie('jwt_token')) {
                throw new TokenMismatchException();
            }

            $csrfToken = $request->cookie('x-csrf-token');
            $rawToken = $request->cookie('jwt_token');

            // Dekripsi token JWT (karena dalam cookie akan disimpan terenkripsi)
            // $decryptedToken = Crypt::decrypt($rawToken);
            $token = new \PHPOpenSourceSaver\JWTAuth\Token($rawToken);

            // Verifikasi dan autentikasi token JWT menggunakan Tymon JWT
            $payload = JWTAuth::decode($token);


            // Cek kesesuaian csrf-token
            if ($payload['csrf-token'] !== $csrfToken) {
                throw new TokenMismatchException();
            }
            $user = JWTAuth::setToken($rawToken)->authenticate();


            if (!$user) {
                throw new \Exception('User not found.');
            }
        } catch (\Exception $e) {
            if ($e instanceof TokenExpiredException) {
                // TODO: Implement token refresh here if needed
                return response('Token expired', 401);
            }
            return response('Unauthorized', 401);
        }


        return $next($request);
    }
}

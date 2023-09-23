<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Models\Note;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTFactory;
use PHPOpenSourceSaver\JWTAuth\Token;

class AuthController extends Controller
{


    public function register(RegisterRequest $request)
    {
        try {
            $created = User::create([
                'name' => Str::lower($request->input('name')),
                'notes_user_id' => Str::ulid(),
                'email' => Str::lower($request->input('email')),
                'password' => bcrypt($request->input('password'))
            ]);
            if ($created) {
                return response()->json([
                    'message' => 'Account successfully created',
                    'status' => 'success'
                ], Response::HTTP_CREATED);
            } else {
                return response()->json([
                    'message' => 'Account creation failed',
                    'status' => 'failed'
                ], Response::HTTP_BAD_REQUEST);
            }
        } catch (QueryException $q) {
            return response()->json([
                'message' => 'failed'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function login(Request $request)
    {

        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'min:6'],
        ]);

        $credentials = $request->only('email', 'password');
        $user = User::where('email', '=', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'errors' => [
                    'email' => "Credentials don't match our records.",
                ],
                'status' => 'failed'
            ], 401);
        }

        $checkTrash = Note::where('user_id', $user->notes_user_id)
            ->onlyTrashed()->count();
        if ($checkTrash > 0) {
            $status = true;
        } else {
            $status = false;
        }

        $csrf = Str::random(32);
        $minutes = auth()->factory()->getTTL() * 60;
        $customClaims = ['sub' => $user->id, 'csrf-token' => $csrf];
        $payload = JWTFactory::customClaims($customClaims)->make();
        $token = JWTAuth::encode($payload);
        $cookie = cookie('jwt_token', $token, $minutes, null, null, false, true);
        $tokencsrf = cookie('x-csrf-token', $csrf, $minutes, null, null, false, true);

        return response()->json([
            'message' => 'You have successfully logged in.',
            'withTrashed' => $status,
            'status' => 'success',
        ])->withCookie($cookie)->withCookie($tokencsrf);
    }


    public function logout(Request $request)
    {

        $rawToken = $request->cookie('jwt_token');

        if ($rawToken) {
            $request->headers->set('Authorization', 'Bearer' . $rawToken);
        }

        try {
            auth()->logout();
            JWTAuth::invalidate(JWTAuth::parseToken());
            $minutes = auth()->factory()->getTTL() * 60;

            $cookie = cookie('jwt_token', '', $minutes, null, null, false, true);
            $tokencsrf = cookie('x-csrf-token', '', $minutes, null, null, false, true);
            return response()->json([
                'message' => 'You have successfully logged out.',
                'status' => 'success',
            ], 200)->withCookie($cookie)->withCookie($tokencsrf);
        } catch (\Exception $th) {
            return response('Unauthorized', 401);
        }
    }

    public function refresh(Request $request)
    {
        config([
            'jwt.blacklist_enabled' => false
        ]);

        $rawToken = $request->cookie('jwt_token');

        if ($rawToken) {
            $request->headers->set('Authorization', 'Bearer' . $rawToken);
        }

        try {
            $currentToken = JWTAuth::getToken();
            $refrehToken = JWTAuth::parseToken($currentToken)->refresh();
            $newtoken = new Token($refrehToken);
            $payload = JWTAuth::decode($newtoken)->toArray();
            $minutes = auth()->factory()->getTTL() * 60;
            $csrf = Str::random(32);
            $customClaims = ['sub' => $payload['sub'], 'csrf-token' => $csrf];
            $payload = JWTFactory::customClaims($customClaims)->make();
            $token = JWTAuth::encode($payload);
            $cookie = cookie('jwt_token', $token, $minutes, null, null, false, true);
            $tokencsrf = cookie('x-csrf-token', $csrf, $minutes, null, null, false, true);

            return response()->json([
                'message' => 'Token successfully updated.',
                'status' => 'success',
            ])->withCookie($cookie)->withCookie($tokencsrf);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Token update failed'], 500);
        }
    }
}

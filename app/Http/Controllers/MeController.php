<?php

namespace App\Http\Controllers;

use App\Http\Resources\MeResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class MeController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        return response()->json([
            'user' => new MeResource(Auth::user())
        ], Response::HTTP_OK);
    }
}

<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Sanctum\PersonalAccessToken;

class ApiAuthController extends Controller
{
    public function register(Request $request)
    {
        // Validate request data
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users|max:255',
            'password' => 'required|min:10',
            'group' => [
                'required',
                Rule::in(['Admin', 'Viewer', 'Editor']),
            ],
        ]);
        // Return errors if validation error occur.
        if ($validator->fails()) {
            $errors = $validator->errors();
            $res = [
                'error' => $errors
            ];
            Log::channel('request')->info('request api url '.$request->url(), [
                'url' => $request->url(),
                'request' => $request->all(),
                'date' => date('d-m-Y H:i:s'),
                'ip' => $request->ip(),
                'response' => $res
            ]);
            return response()->json($res, 400);
        }
        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'group' => $request->group,
        ]);
        $res = [
            'message' => 'success'
        ];
        Log::channel('request')->info('request api url '.$request->url(), [
            'url' => $request->url(),
            'request' => $request->all(),
            'date' => date('d-m-Y H:i:s'),
            'ip' => $request->ip(),
            'response' => $res
        ]);

        return response($res)->json();
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);
        // Return errors if validation error occur.
        if ($validator->fails()) {
            $errors = $validator->errors();
            $res = [
                'error' => $errors
            ];
            Log::channel('request')->info('request api url '.$request->url(), [
                'url' => $request->url(),
                'request' => $request->all(),
                'date' => date('d-m-Y H:i:s'),
                'ip' => $request->ip(),
                'response' => $res
            ]);
            return response()->json($res, 400);
        }
        if (!Auth::attempt($request->only('email', 'password'))) {
            $res = [
                'message' => 'Invalid login details'
            ];
            Log::channel('request')->info('request api url '.$request->url(), [
                'url' => $request->url(),
                'request' => $request->all(),
                'date' => date('d-m-Y H:i:s'),
                'ip' => $request->ip(),
                'response' => $res
            ]);
            return response()->json($res, 401);
        }
        $user = User::where('email', $request['email'])->firstOrFail();
        $user->tokens()->delete();
        $token = $user->createToken('auth_token', [$user->group])->plainTextToken;
        $res = [
            'access_token' => $token,
            'token_type' => 'Bearer',
        ];
        Log::channel('request')->info('request api url '.$request->url(), [
            'url' => $request->url(),
            'request' => $request->all(),
            'date' => date('d-m-Y H:i:s'),
            'ip' => $request->ip(),
            'response' => $res
        ]);
        return response($res)->json();
    }

    public function me(Request $request)
    {
        if (!$request->user()->tokenCan('Editor')) {
            $res = [
                'message' => 'permission denied'
            ];
            Log::channel('request')->info('request api url '.$request->url(), [
                'url' => $request->url(),
                'request' => $request->all(),
                'date' => date('d-m-Y H:i:s'),
                'ip' => $request->ip(),
                'response' => $res
            ]);
            return response()->json($res, 403);
        }
        return $request->user();
    }
}

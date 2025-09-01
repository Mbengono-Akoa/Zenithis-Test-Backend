<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    public function __construct() {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    public function login(Request $request){
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $credentials = $request->only('email', 'password');

        try {
            // Utilisation directe de JWTAuth pour générer le token
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid credentials'
                ], 401);
            }
        } catch (JWTException $e) {
            return response()->json([
                'status' => 'error', 
                'message' => 'Could not create token'
            ], 500);
        }

        return $this->respondWithToken($token);
    }

    public function register(Request $request) {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|confirmed|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create(array_merge(
            $validator->validated(),
            ['password' => bcrypt($request->password)]
        ));

        // Génération du token après l'inscription
        $token = JWTAuth::fromUser($user);

        return response()->json([
            'status' => 'success',
            'message' => 'User successfully registered',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60
        ], 201);
    }

    public function logout() {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json([
                'status' => 'success',
                'message' => 'User successfully signed out'
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to logout, please try again'
            ], 500);
        }
    }

    public function refresh() {
        try {
            $token = JWTAuth::refresh(JWTAuth::getToken());
            return $this->respondWithToken($token);
        } catch (JWTException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token refresh failed'
            ], 401);
        }
    }

    public function userProfile() {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            return response()->json([
                'status' => 'success',
                'user' => $user
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found'
            ], 404);
        }
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'status' => 'success',
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
            'user' => JWTAuth::user()
        ]);
    }
}
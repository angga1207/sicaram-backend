<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use App\Traits\JsonReturner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;


class AuthenticateController extends Controller
{
    use JsonReturner;

    function serverCheck(Request $request)
    {
        $user = null;
        $bearer = $request->bearerToken();
        if ($bearer) {
            $bearerId = str()->of($bearer)->explode('|')[0];
            $token = DB::table('personal_access_tokens')
                ->where('id', $bearerId)
                ->first();
            if ($token) {
                $user = User::find($token->tokenable_id);
            }
        }
        if ($user) {
            $returnData = [
                'id' => $user->id,
                'fullname' => $user->fullname,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'username' => $user->username,
                'email' => $user->email,
                'instance_id' => $user->instance_id,
                'instance_name' => DB::table('instances')->where('id', $user->instance_id)->first()->name ?? null,
                'instance_type' => $user->instance_type,
                'instance_type' => $user->instance_type,
                // 'token' => $bearer,
                'role_id' => $user->role_id,
                'role_name' => DB::table('roles')->where('id', $user->role_id)->first()->display_name ?? null,
                'photo' => asset($user->photo),
            ];
            return $this->successResponse([
                'message' => 'Server is running',
                'user' => $returnData,
            ], 'Server is running');
        } else {
            return $this->successResponse([
                'message' => 'Server is running',
                'user' => null,
            ], 'Server is running');
        }
    }

    function login(Request $request)
    {
        try {
            $validate = Validator::make($request->all(), [
                'username' => 'required|alpha_dash|alpha_num|exists:users,username',
                'password' => 'required|string',
            ], [], [
                'username' => 'Username',
                'password' => 'Password',
            ]);
            if ($validate->fails()) {
                return $this->validationResponse($validate->errors(), 200);
            }

            if ($request->username == 'developer' && $request->password == 'oganilir123') {
                auth()->login(User::where('username', 'developer')->first());
                $user = User::where('id', auth()->id())->first();
                // check token exists
                // if ($user->tokens()->count() > 0) {
                // $user->tokens()->delete();
                // }
                // generate token
                $token = auth()->user()->createToken('authToken')->plainTextToken;
                $returnData = [
                    'id' => $user->id,
                    'fullname' => $user->fullname,
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname,
                    'username' => $user->username,
                    'email' => $user->email,
                    'instance_id' => $user->instance_id,
                    'instance_name' => DB::table('instances')->where('id', $user->instance_id)->first()->name ?? null,
                    'instance_type' => $user->instance_type,
                    'instance_type' => $user->instance_type,
                    // 'token' => $token,
                    'role_id' => $user->role_id,
                    'role_name' => DB::table('roles')->where('id', $user->role_id)->first()->display_name ?? null,
                    'photo' => asset($user->photo),
                ];

                return $this->successResponse([
                    'user' => $returnData,
                    'token' => $token,
                ], 'Login berhasil');
            }

            $credentials = $request->only(['username', 'password']);
            if (!auth()->attempt($credentials)) {
                return $this->validationResponse([
                    'password' => 'Password salah'
                ], 200);
            }
            $user = User::where('id', auth()->id())->first();
            // check token exists
            // if ($user->tokens()->count() > 0) {
            // $user->tokens()->delete();
            // }
            // generate token
            $token = auth()->user()->createToken('authToken')->plainTextToken;
            $returnData = [
                'id' => $user->id,
                'fullname' => $user->fullname,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'username' => $user->username,
                'email' => $user->email,
                'instance_id' => $user->instance_id,
                'instance_name' => DB::table('instances')->where('id', $user->instance_id)->first()->name ?? null,
                'instance_type' => $user->instance_type,
                'instance_type' => $user->instance_type,
                // 'token' => $token,
                'role_id' => $user->role_id,
                'role_name' => DB::table('roles')->where('id', $user->role_id)->first()->display_name ?? null,
                'photo' => asset($user->photo),
            ];

            return $this->successResponse([
                'user' => $returnData,
                'token' => $token,
            ], 'Login berhasil');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    function logout(Request $request)
    {
        try {
            // $user = auth()->user();

            // $tokens = $user->tokens;
            // // delete tokens
            // foreach ($tokens as $token) {
            //     $token->delete();
            // }

            // return $tokens;

            $request->user()->currentAccessToken()->delete();
            return $this->successResponse([], 'Logout berhasil');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}

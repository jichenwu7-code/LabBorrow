<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class WjcController extends Controller
{
    //用户注册
    public function register(Request $request)
    {
        $validated = $request->validate([
            'account' => 'required|unique:users',
            'name' => 'required',
            'password' => 'required|confirmed',
        ]);

        User::create([
            'account' => $validated['account'],
            'name' => $validated['name'],
            'password' => Hash::make($validated['password']),
            'role' => 1, //默认学生
            'status' => 1,
        ]);

        return response()->json([
            'code' => 200,
            'message' => '注册成功',
            'data' => null
        ]);
    }

    //用户登录
    public function login(Request $request)
    {
        $validated = $request->validate([
            'account' => 'required',
            'password' => 'required',
        ]);

        $credentials = $request->only('account', 'password');
        $token = JWTAuth::attempt($credentials);
        if(!$token){
            return response()->json([
                'code' => 401,
                'message' => '账号或密码错误',
                'data' => null
            ],401);
        }

        $user = JWTAuth::user();

        return response()->json([
            'code' => 200,
            'message' => '登录成功',
            'data'=> [
                'token' => $token,
                'user' => [
                    'id' =>$user->id,
                    'account' => $user->account,
                    'name' => $user->name,
                    'role' =>$user->role,
                ]
            ]
        ]);
    }

    //退出登录
    public function logout()
    {
        JWTAuth::logout();

        return response()->json([
            'code' => 200,
            'message' => '退出成功',
            'data' => null
        ]);
    }

    //获取当前用户信息
    public function userInfo()
    {
        $user = JWTAuth::user();

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => $user,
        ]);
    }

    //修改个人密码
    public function updatePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required',
            'password' => 'required|confirmed',
        ]);

        $user = JWTAuth::user();

        if(!Hash::check($request->old_password,$user->password)){
            return response()->json([
                'code' => 400,
                'message' => '原密码错误',
                'data' => null,
            ],400);
        }

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        return response()->json([
            'code' => 200,
            'message' => '修改成功',
            'data' => null,
        ]);
    }

    //修改个人资料
    public function updateProfile(Request $request)
    {
        $user = JWTAuth::user();

        $user->update($request->only([
            'name',
            'phone',
            'email',
        ]));

        return response()->json([
            'code' => 200,
            'message' => '更新成功',
            'data' => null,
        ]);
    }
}

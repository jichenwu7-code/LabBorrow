<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Booking;
use App\Models\Device;
use App\Models\DeviceCategory;



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
        // 直接返回成功响应，JWT token 由客户端自行处理
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

    //借用记录审核
    public function bookingList(Request $request)
    {
        $bookings = Booking::with(['user', 'device'])
            ->orderBy('id', 'desc')
            ->paginate($request->per_page ?? 10);

        $items = $bookings->map(function ($item) {
            return [
                'id' => $item->id,
                'user_name' => $item->user->name ?? '',
                'user_account' => $item->user->account ?? '',
                'device_name' => $item->device->name ?? '',
                'model' => $item->device->model ?? '',
                'start_date' => $item->start_date,
                'end_date' => $item->end_date,
                'purpose' => $item->purpose,
                'status' => $item->status,
                'status_text' => $item->status_text,
                'created_at' => $item->created_at->toDateTimeString(),
                'returned_at' => $item->returned_at,
                'admin_id' => $item->admin_id,
                'reject_reason' => $item->reject_reason,
            ];
        });

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'total' => $bookings->total(),
                'current_page' => $bookings->currentPage(),
                'per_page' => $bookings->perPage(),
                'items' => $items
            ]
        ]);
    }

    //审核借用申请
    public function auditBooking(Request $request, $id)
    {
        $validated = $request->validate([
            'result' => 'required|in:approved,rejected',
            'reject_reason' => 'nullable|string',
        ]);

        $booking = Booking::findOrFail($id);

        $booking->update([
            'status' => $validated['result'],
            'reject_reason' => $validated['reject_reason'] ?? null,
        ]);

        return response()->json([
            'code' => 200,
            'message' => '审核成功',
            'data' => null
        ]);
    }

    //设备管理

    //新增设备
    public function storeDevice(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:device_categories,id',
            'name' => 'required|string',
            'model' => 'nullable|string',
            'description' => 'nullable|string',
            'total_quantity' => 'required|integer|min:0',
            'status' => 'required|in:0,1',
        ]);

        Device::create($validated);

        return response()->json([
            'code' => 200,
            'message' => '设备新增成功',
            'data' => null
        ]);
    }
    //编辑设备
    public function updateDevice(Request $request, $id)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:device_categories,id',
            'name' => 'required|string',
            'model' => 'nullable|string',
            'description' => 'nullable|string',
            'total_quantity' => 'required|integer|min:0',
            'status' => 'required|in:0,1',
        ]);

        $device = Device::findOrFail($id);
        $device->update($validated);

        return response()->json([
            'code' => 200,
            'message' => '设备编辑成功',
            'data' => null
        ]);
    }

    //更新设备状态（上架/下架）
    public function updateDeviceStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:0,1',
        ]);

        $device = Device::findOrFail($id);
        $device->update($validated);

        return response()->json([
            'code' => 200,
            'message' => '设备状态更新成功',
            'data' => null
        ]);
    }
}

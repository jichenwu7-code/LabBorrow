<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Booking;
use App\Models\Device;
use App\Models\DeviceCategory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class WjcController
{
    //用户注册
    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'account' => 'required|unique:users',
                'name' => 'required',
                'email' => 'required|email|unique:users',
                'password' => 'required|confirmed|min:6|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
                'code' => 'required|digits:6',
            ]);

            // 验证邮箱验证码
            $email = $validated['email'];
            $emailKey = strtolower($email);
            $codeFile = storage_path('app/verify_codes/' . md5($emailKey) . '.txt');

            // 调试信息
            \Illuminate\Support\Facades\Log::info('Register verify - Email: ' . $email . ', Key: ' . $emailKey . ', File: ' . md5($emailKey) . '.txt');

            if (!\Illuminate\Support\Facades\File::exists($codeFile)) {   
                // 列出所有验证码文件用于调试
                $files = \Illuminate\Support\Facades\File::files(storage_path('app/verify_codes'));
                $fileNames = [];
                foreach ($files as $file) {
                    $fileNames[] = $file->getFilename();
                }
                \Illuminate\Support\Facades\Log::info('Available code files: ' . implode(', ', $fileNames));

                return response()->json([
                    'code' => 400,
                    'message' => '验证码不存在，请重新发送',
                    'data' => ['debug_email_key' => md5($emailKey) . '.txt', 'available_files' => $fileNames]
                ], 400);
            }

            $codeData = json_decode(\Illuminate\Support\Facades\File::get($codeFile), true);
            if (!$codeData || !isset($codeData['code']) || !isset($codeData['expire'])) {
                \Illuminate\Support\Facades\File::delete($codeFile);
                return response()->json([
                    'code' => 400,
                    'message' => '验证码数据无效，请重新发送',
                    'data' => null
                ], 400);
            }

            if ($codeData['expire'] < time()) {
                \Illuminate\Support\Facades\File::delete($codeFile);
                return response()->json([
                    'code' => 400,
                    'message' => '验证码已过期，请重新发送',
                    'data' => null
                ], 400);
            }

            if ($codeData['code'] != $validated['code']) {
                return response()->json([
                    'code' => 400,
                    'message' => '验证码错误',
                    'data' => null
                ], 400);
            }

            // 创建用户
            User::create([
                'account' => $validated['account'],
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 1, //默认学生
                'status' => 1,
            ]);

            // 删除已使用的验证码
            \Illuminate\Support\Facades\File::delete($codeFile);

            return response()->json([
                'code' => 200,
                'message' => '注册成功',
                'data' => null
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->errors();
            $firstError = reset($errors);
            $errorMessage = is_array($firstError) ? $firstError[0] : $firstError;

            return response()->json([
                'code' => 400,
                'message' => '验证失败: ' . $errorMessage,
                'data' => null
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '注册失败: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    //用户登录
    public function login(Request $request)
    {
        try {
            $validated = $request->validate([
                'account' => 'required',
                'password' => 'required',
            ]);

            $user = User::where('account', $validated['account'])->first();
            
            if (!$user) {
                return response()->json([
                    'code' => 401,
                    'message' => '账号或密码错误',
                    'data' => null
                ], 401);
            }
            
            if (!password_verify($validated['password'], $user->password)) {
                return response()->json([
                    'code' => 401,
                    'message' => '账号或密码错误',
                    'data' => null
                ], 401);
            }
            
            $token = JWTAuth::fromUser($user);
            
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
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '登录失败: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
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
        try {
            $user = JWTAuth::user();

            return response()->json([
                'code' => 200,
                'message' => '获取成功',
                'data' => $user,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '获取失败: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }


    //修改个人资料
    public function updateProfile(Request $request)
    {
        try {
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
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '更新失败: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    //发送验证码
    public function sendCode(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email',
                'type' => 'required|in:register,reset'
            ]);
            $email = $validated['email'];
            $type = $validated['type'];

            // 根据类型检查用户是否存在（仅用于提示，不影响发送验证码）
            $user = User::where('email', $email)->first();
            if ($type === 'reset' && !$user) {
                return response()->json(['code' => 400, 'message' => '该邮箱未注册', 'data' => null], 400);
            }

            // 生成6位验证码
            $code = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

            // 存储验证码到文件，有效期5分钟
            $emailKey = strtolower($email);
            $codeFile = storage_path('app/verify_codes/' . md5($emailKey) . '.txt');
            $codeData = [
                'code' => $code,
                'expire' => time() + 300
            ];

            // 调试信息
            \Illuminate\Support\Facades\Log::info('Send code - Email: ' . $email . ', Key: ' . $emailKey . ', File: ' . md5($emailKey) . '.txt');

            \Illuminate\Support\Facades\File::ensureDirectoryExists(storage_path('app/verify_codes'));
            \Illuminate\Support\Facades\File::put($codeFile, json_encode($codeData));

            // 根据类型设置邮件主题
            $subject = $type === 'register' ? '注册验证码' : '验证码';

            // 发送邮箱验证码
            try {
                \Illuminate\Support\Facades\Mail::send('emails.verify', ['code' => $code], function ($message) use ($email, $subject) {
                    $message->to($email)
                            ->subject($subject);
                });

                return response()->json([
                    'code' => 200,
                    'message' => '验证码发送成功',
                    'data' => null
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'code' => 500,
                    'message' => '验证码发送失败: ' . $e->getMessage(),
                    'data' => null
                ], 500);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->errors();
            $firstError = reset($errors);
            $errorMessage = is_array($firstError) ? $firstError[0] : $firstError;

            return response()->json([
                'code' => 400,
                'message' => '验证失败: ' . $errorMessage,
                'data' => null
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '发送失败: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    //校验验证码
    public function verifyCode(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email',
                'code' => 'required|digits:6',
            ]);

            $email = $validated['email'];
            $code = $validated['code'];
            $emailKey = strtolower($email);

            // 读取验证码文件
            $codeFile = storage_path('app/verify_codes/' . md5($emailKey) . '.txt');
            if (!\Illuminate\Support\Facades\File::exists($codeFile)) {
                return response()->json([
                    'code' => 400,
                    'message' => '验证码已过期',
                    'data' => null
                ], 400);
            }

            // 解析验证码数据
            $codeData = json_decode(\Illuminate\Support\Facades\File::get($codeFile), true);
            if (!$codeData || !isset($codeData['code']) || !isset($codeData['expire'])) {
                \Illuminate\Support\Facades\File::delete($codeFile);
                return response()->json([
                    'code' => 400,
                    'message' => '验证码已过期',
                    'data' => null
                ], 400);
            }

            // 检查验证码是否过期
            if ($codeData['expire'] < time()) {
                \Illuminate\Support\Facades\File::delete($codeFile);
                return response()->json([
                    'code' => 400,
                    'message' => '验证码已过期',
                    'data' => null
                ], 400);
            }

            // 检查验证码是否正确
            if ($codeData['code'] != $code) {
                return response()->json([
                    'code' => 400,
                    'message' => '验证码错误',
                    'data' => null
                ], 400);
            }

            // 生成验证token，有效期10分钟
            $verifyToken = Str::random(64);
            $tokenFile = storage_path('app/verify_tokens/' . $verifyToken . '.txt');
            $tokenData = [
                'email' => $email,
                'expire' => time() + 600
            ];
            \Illuminate\Support\Facades\File::ensureDirectoryExists(storage_path('app/verify_tokens'));
            \Illuminate\Support\Facades\File::put($tokenFile, json_encode($tokenData));

            // 删除已使用的验证码
            \Illuminate\Support\Facades\File::delete($codeFile);

            return response()->json([
                'code' => 200,
                'message' => '验证成功',
                'data' => [
                    'verify_token' => $verifyToken
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->errors();
            $firstError = reset($errors);
            $errorMessage = is_array($firstError) ? $firstError[0] : $firstError;

            return response()->json([
                'code' => 400,
                'message' => '验证失败: ' . $errorMessage,
                'data' => null
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '验证失败: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    //用户重置密码
    public function resetPassword(Request $request)
    {
        try {
            // 使用validateWithBag来捕获验证错误
            $validated = $request->validateWithBag('resetPassword', [
                'password' => 'required|confirmed|min:6|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
            ]);

            // 获取验证token
            $verifyToken = $request->header('Verify-Token');
            if (!$verifyToken) {
                return response()->json([
                    'code' => 400,
                    'message' => '验证码未验证或 Verify-Token 无效',
                    'data' => null
                ], 400);
            }

            // 验证token是否有效
            $tokenFile = storage_path('app/verify_tokens/' . $verifyToken . '.txt');
            if (!\Illuminate\Support\Facades\File::exists($tokenFile)) {
                return response()->json([
                    'code' => 400,
                    'message' => '验证码未验证或 Verify-Token 无效',
                    'data' => null
                ], 400);
            }

            // 解析token数据
            $tokenData = json_decode(\Illuminate\Support\Facades\File::get($tokenFile), true);
            if (!$tokenData || !isset($tokenData['email']) || !isset($tokenData['expire'])) {
                \Illuminate\Support\Facades\File::delete($tokenFile);
                return response()->json([
                    'code' => 400,
                    'message' => '验证码未验证或 Verify-Token 无效',
                    'data' => null
                ], 400);
            }

            // 检查token是否过期
            if ($tokenData['expire'] < time()) {
                \Illuminate\Support\Facades\File::delete($tokenFile);
                return response()->json([
                    'code' => 400,
                    'message' => '验证码未验证或 Verify-Token 无效',
                    'data' => null
                ], 400);
            }

            // 获取邮箱
            $email = $tokenData['email'];

            // 查找用户
            $user = User::where('email', $email)->first();
            if (!$user) {
                return response()->json([
                    'code' => 400,
                    'message' => '邮箱未注册',
                    'data' => null
                ], 400);
            }

            // 更新密码
            $user->update([
                'password' => Hash::make($validated['password'])
            ]);

            // 删除验证token
            \Illuminate\Support\Facades\File::delete($tokenFile);

            return response()->json([
                'code' => 200,
                'message' => '密码重置成功，请使用新密码登录',
                'data' => null
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // 处理验证错误，返回JSON
            return response()->json([
                'code' => 400,
                'message' => '验证失败: ' . $e->errors()['password'][0],
                'data' => null
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '重置密码失败: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    //注销账号
    public function deleteUser(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email',
                'code' => 'required|digits:6',
            ]);

            $user = JWTAuth::user();

            // 验证邮箱是否匹配
            if ($user->email != $validated['email']) {
                return response()->json([
                    'code' => 400,
                    'message' => '邮箱不匹配',
                    'data' => null
                ], 400);
            }

            $emailKey = strtolower($validated['email']);

            // 读取验证码文件
            $codeFile = storage_path('app/verify_codes/' . md5($emailKey) . '.txt');
            if (!\Illuminate\Support\Facades\File::exists($codeFile)) {
                return response()->json([
                    'code' => 400,
                    'message' => '验证码已过期',
                    'data' => null
                ], 400);
            }

            // 解析验证码数据
            $codeData = json_decode(\Illuminate\Support\Facades\File::get($codeFile), true);
            if (!$codeData || !isset($codeData['code']) || !isset($codeData['expire'])) {
                \Illuminate\Support\Facades\File::delete($codeFile);
                return response()->json([
                    'code' => 400,
                    'message' => '验证码已过期',
                    'data' => null
                ], 400);
            }

            // 检查验证码是否过期
            if ($codeData['expire'] < time()) {
                \Illuminate\Support\Facades\File::delete($codeFile);
                return response()->json([
                    'code' => 400,
                    'message' => '验证码已过期',
                    'data' => null
                ], 400);
            }

            // 检查验证码是否正确
            if ($codeData['code'] != $validated['code']) {
                return response()->json([
                    'code' => 400,
                    'message' => '验证码错误',
                    'data' => null
                ], 400);
            }

            // 检查是否有未归还的设备
            $unreturnedBookings = Booking::where('user_id', $user->id)
                ->where('status', 'approved')
                ->whereNull('returned_at')
                ->exists();

            if ($unreturnedBookings) {
                return response()->json([
                    'code' => 400,
                    'message' => '有未归还设备，禁止注销',
                    'data' => null
                ], 400);
            }

            // 注销账号
            $user->update(['status' => 0]);

            // 删除已使用的验证码
            \Illuminate\Support\Facades\File::delete($codeFile);

            return response()->json([
                'code' => 200,
                'message' => '账号注销成功',
                'data' => null
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // 获取第一个错误消息
            $errors = $e->errors();
            $firstError = reset($errors);
            $errorMessage = is_array($firstError) ? $firstError[0] : $firstError;
            
            return response()->json([
                'code' => 400,
                'message' => '验证失败: ' . $errorMessage,
                'data' => null
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '注销失败: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    //借用记录审核
    public function bookingList(Request $request)
    {
        try {
            $query = Booking::with(['user', 'device'])
                ->orderBy('id', 'desc');

            // 按状态筛选
            if ($request->has('status') && $request->status !== '' && $request->status !== null) {
                $query->where('status', $request->status);
            }

            $bookings = $query->paginate($request->per_page ?? 10);

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
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '获取失败: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    //审核借用申请
    public function auditBooking(Request $request, $id)
    {
        try {
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
        } catch (\Illuminate\Validation\ValidationException $e) {
            // 获取第一个错误消息
            $errors = $e->errors();
            $firstError = reset($errors);
            $errorMessage = is_array($firstError) ? $firstError[0] : $firstError;
            
            return response()->json([
                'code' => 400,
                'message' => '验证失败: ' . $errorMessage,
                'data' => null
            ], 400);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'code' => 404,
                'message' => '预约记录不存在',
                'data' => null
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '审核失败: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    //审核归还申请
    public function auditReturnBooking(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'result' => 'required|in:returned,rejected',
                'reject_reason' => 'nullable|string',
            ]);

            $booking = Booking::findOrFail($id);

            // 只有待审核归还状态才能审核
            if ($booking->status !== 'return_pending') {
                return response()->json([
                    'code' => 400,
                    'message' => '当前状态不允许审核归还',
                    'data' => null
                ], 400);
            }

            $updateData = [
                'status' => $validated['result'],
                'admin_id' => JWTAuth::user()->id,
            ];

            // 如果审核通过，记录归还时间
            if ($validated['result'] === 'returned') {
                $updateData['returned_at'] = now();
            } else {
                // 如果拒绝归还申请，恢复为已通过状态
                $updateData['status'] = 'approved';
                $updateData['reject_reason'] = $validated['reject_reason'] ?? null;
            }

            $booking->update($updateData);

            $message = $validated['result'] === 'returned' ? '归还审核通过' : '归还申请已拒绝';

            return response()->json([
                'code' => 200,
                'message' => $message,
                'data' => null
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->errors();
            $firstError = reset($errors);
            $errorMessage = is_array($firstError) ? $firstError[0] : $firstError;

            return response()->json([
                'code' => 400,
                'message' => '验证失败: ' . $errorMessage,
                'data' => null
            ], 400);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'code' => 404,
                'message' => '预约记录不存在',
                'data' => null
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '审核失败: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    //设备管理

    //新增设备
    public function storeDevice(Request $request)
    {
        try {
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
        } catch (\Illuminate\Validation\ValidationException $e) {
            // 获取第一个错误消息
            $errors = $e->errors();
            $firstError = reset($errors);
            $errorMessage = is_array($firstError) ? $firstError[0] : $firstError;
            
            return response()->json([
                'code' => 400,
                'message' => '验证失败: ' . $errorMessage,
                'data' => null
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '新增失败: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
    //编辑设备
    public function updateDevice(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'category_id' => 'required|exists:device_categories,id',
                'name' => 'required|string',
                'model' => 'nullable|string',
                'description' => 'nullable|string',
                'total_quantity' => 'required|integer|min:0',
                'status' => 'required|in:0,1',
            ]);

            $device = Device::findOrFail($id);

            // 计算当前已借出数量
            $borrowedCount = \App\Models\Booking::where('device_id', $id)
                ->whereIn('status', ['approved', 'return_pending'])
                ->count();

            // 验证总数量不能小于已借出数量
            if ($validated['total_quantity'] < $borrowedCount) {
                return response()->json([
                    'code' => 400,
                    'message' => '编辑失败：总数量不能小于当前已借出数量（' . $borrowedCount . '）',
                    'data' => null
                ], 400);
            }

            $device->update($validated);

            return response()->json([
                'code' => 200,
                'message' => '设备编辑成功',
                'data' => null
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // 获取第一个错误消息
            $errors = $e->errors();
            $firstError = reset($errors);
            $errorMessage = is_array($firstError) ? $firstError[0] : $firstError;
            
            return response()->json([
                'code' => 400,
                'message' => '验证失败: ' . $errorMessage,
                'data' => null
            ], 400);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'code' => 404,
                'message' => '设备不存在',
                'data' => null
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '编辑失败: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    //更新设备状态（上架/下架）
    public function updateDeviceStatus(Request $request, $id)
    {
        try {
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
        } catch (\Illuminate\Validation\ValidationException $e) {
            // 获取第一个错误消息
            $errors = $e->errors();
            $firstError = reset($errors);
            $errorMessage = is_array($firstError) ? $firstError[0] : $firstError;
            
            return response()->json([
                'code' => 400,
                'message' => '验证失败: ' . $errorMessage,
                'data' => null
            ], 400);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'code' => 404,
                'message' => '设备不存在',
                'data' => null
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '更新失败: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    //设备列表
    public function index(Request $request)
    {
        try {
            $page = $request->input('page', 1);
            $limit = $request->input('limit', 10);
            $keyword = $request->input('keyword', '');
            $category_id = $request->input('category_id');

            $query = Device::with('category')
                ->when($keyword, function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%")
                        ->orWhere('model', 'like', "%{$keyword}%");
                })
                ->when($category_id, function ($q) use ($category_id) {
                    $q->where('category_id', $category_id);
                });

            $total = $query->count();
            $devices = $query->paginate($limit, ['*'], 'page', $page);

            $items = $devices->map(function ($item) {
                // 计算已借出数量（状态为 approved 或 return_pending 的借用记录）
                $borrowedCount = \App\Models\Booking::where('device_id', $item->id)
                    ->whereIn('status', ['approved', 'return_pending'])
                    ->count();

                // 计算可借用数量
                $availableQuantity = max(0, $item->total_quantity - $borrowedCount);

                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'model' => $item->model,
                    'category_id' => $item->category_id,
                    'category_name' => $item->category?->name,
                    'description' => $item->description,
                    'total_quantity' => $item->total_quantity,
                    'available_quantity' => $availableQuantity,
                    'status' => $item->status,
                    'status_text' => $item->status == 1 ? '可借' : '不可借',
                ];
            });

            return response()->json([
                'code' => 200,
                'message' => '获取成功',
                'data' => [
                    'total' => $total,
                    'current_page' => $devices->currentPage(),
                    'per_page' => $devices->perPage(),
                    'items' => $items
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '获取失败: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    //设备分类列表
    public function categories()
    {
        try {
            $list = DeviceCategory::get(['id', 'name', 'description']);

            return response()->json([
                'code' => 200,
                'message' => '获取成功',
                'data' => $list
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '获取失败: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    //单台设备详情
    public function show($id)
    {
        try {
            $device = Device::with('category')->find($id);

            if (!$device) {
                return response()->json([
                    'code' => 404,
                    'message' => '设备不存在',
                    'data' => null
                ]);
            }

            return response()->json([
                'code' => 200,
                'message' => '获取成功',
                'data' => [
                    'id' => $device->id,
                    'name' => $device->name,
                    'model' => $device->model,
                    'category_id' => $device->category_id,
                    'category_name' => $device->category?->name,
                    'description' => $device->description,
                    'total_quantity' => $device->total_quantity,
                    'status' => $device->status,
                    'status_text' => $device->status == 1 ? '可借' : '不可借',
                    'created_at' => $device->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $device->updated_at->format('Y-m-d H:i:s'),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '获取失败: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    //获取分类下的设备列表
    public function devicesByCategory(Request $request, $id)
    {
        try {
            $devices = Device::where('category_id', $id)
                ->with('category')
                ->paginate($request->limit ?? 10);

            return response()->json([
                'code' => 200,
                'message' => '获取成功',
                'data' => $devices
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '获取失败: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    //获取可借用设备列表
    public function available(Request $request)
    {
        try {
            $query = Device::where('status', 1)->with('category');

            if ($request->keyword) {
                $query->where('name', 'like', '%'.$request->keyword.'%')
                    ->orWhere('model', 'like', '%'.$request->keyword.'%');
            }
            if ($request->category_id) {
                $query->where('category_id', $request->category_id);
            }

            $data = $query->paginate($request->limit ?? 10);

            return response()->json([
                'code' => 200,
                'message' => '获取成功',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '获取失败: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    //按状态筛选
    public function filterByStatus(Request $request)
    {
        try {
            $validated = $request->validate(['status' => 'required']);

            $data = Device::where('status', $validated['status'])
                ->with('category')
                ->paginate($request->limit ?? 10);

            return response()->json([
                'code' => 200,
                'message' => '获取成功',
                'data' => $data
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'code' => 400,
                'message' => '验证失败: ' . $e->errors()['status'][0],
                'data' => null
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '获取失败: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    //设备状态选项(前端下拉框)
    public function statusOptions()
    {
        try {
            return response()->json([
                'code' => 200,
                'message' => '获取成功',
                'data' => [
                    ['code' => 1, 'text' => '可借'],
                    ['code' => 2, 'text' => '已借出'],
                    ['code' => 3, 'text' => '维护中'],
                    ['code' => 0, 'text' => '已下架'],
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '获取失败: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    //检查设备是否可借
    public function checkAvailable($id)
    {
        try {
            $device = Device::find($id);
            if (!$device) {
                return response()->json(['code' => 400, 'message' => '设备不存在'], 400);
            }

            $available = $device->status == 1 && $device->available_quantity > 0;

            return response()->json([
                'code' => 200,
                'message' => '查询成功',
                'data' => [
                    'device_id' => $device->id,
                    'name' => $device->name,
                    'is_available' => $available,
                    'available_quantity' => $device->available_quantity,
                    'tip' => $available ? '可借' : '不可借'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '查询失败: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    //获取热门设备(按借用次数排序)
    public function hotDevices(Request $request)
    {
        try {
            $devices = Device::orderBy('borrow_count', 'desc')
                ->limit($request->limit ?? 10)
                ->get();

            return response()->json([
                'code' => 200,
                'message' => '获取成功',
                'data' => $devices
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '获取失败: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    //添加设备分类
    public function storeCategory(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
            ]);

            $category = DeviceCategory::create($validated);

            return response()->json([
                'code' => 200,
                'message' => '分类添加成功',
                'data' => $category
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->errors();
            $firstError = reset($errors);
            $errorMessage = is_array($firstError) ? $firstError[0] : $firstError;

            return response()->json([
                'code' => 400,
                'message' => '验证失败: ' . $errorMessage,
                'data' => null
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '添加失败: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    //创建预约
    public function storeBooking(Request $request)
    {
        try {
            $validated = $request->validate([
                'device_id' => 'required|exists:devices,id',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
                'purpose' => 'nullable|string',
            ]);

            $user = auth('api')->user();

            $booking = Booking::create([
                'user_id' => $user->id,
                'device_id' => $validated['device_id'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'purpose' => $validated['purpose'] ?? null,
                'status' => 'pending',
            ]);

            return response()->json([
                'code' => 200,
                'message' => '预约成功',
                'data' => $booking
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->errors();
            $firstError = reset($errors);
            $errorMessage = is_array($firstError) ? $firstError[0] : $firstError;

            return response()->json([
                'code' => 400,
                'message' => '验证失败: ' . $errorMessage,
                'data' => null
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '预约失败: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ZhyController
{
    /**
     * 提交借用申请
     * POST /api/bookings
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'device_id'   => 'required|exists:devices,id',
            'start_date'  => 'required|date|after_or_equal:today',
            'end_date'    => 'required|date|after_or_equal:start_date',
            'purpose'     => 'nullable|string|max:500',
        ], [
            'device_id.required' => '请选择设备',
            'device_id.exists'   => '设备不存在',
            'start_date.required' => '请选择借用开始日期',
            'start_date.after_or_equal' => '开始日期不能早于今天',
            'end_date.required' => '请选择借用结束日期',
            'end_date.after_or_equal' => '结束日期不能早于开始日期',
        ]);

        $device = Device::findOrFail($request->device_id);

        // 检查设备是否可借（状态为 1 表示可借）
        if ($device->status != 1) {
            return response()->json([
                'code'    => 400,
                'message' => '该设备当前不可借用',
                'data'    => null,
            ]);
        }

        // 检查库存（实时计算法：总库存 - 待审核和已通过的数量）
        $borrowedCount = Booking::where('device_id', $device->id)
            ->whereIn('status', ['pending', 'approved'])
            ->count();

        if ($borrowedCount >= $device->total_quantity) {
            return response()->json([
                'code'    => 400,
                'message' => '该设备当前无可用库存，请选择其他时间或设备',
                'data'    => null,
            ]);
        }

        // 创建借用申请
        $booking = Booking::create([
            'user_id'    => $user->id,
            'device_id'  => $device->id,
            'start_date' => $request->start_date,
            'end_date'   => $request->end_date,
            'purpose'    => $request->purpose,
            'status'     => 'pending',
        ]);

        return response()->json([
            'code'    => 200,
            'message' => '申请已提交，等待审核',
            'data'    => ['id' => $booking->id],
        ]);
    }

    /**
     * 获取个人借用记录
     * GET /api/bookings/my
     */
    public function myBookings(Request $request)
    {
        $user = Auth::user();

        $perPage = $request->input('limit', 10);
        $status  = $request->input('status');

        $query = Booking::with('device')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        $bookings = $query->paginate($perPage);

        $items = $bookings->map(function ($booking) {
            return [
                'id'            => $booking->id,
                'device_name'   => $booking->device->name ?? '未知设备',
                'model'         => $booking->device->model ?? '',
                'start_date'    => $booking->start_date->format('Y-m-d'),
                'end_date'      => $booking->end_date->format('Y-m-d'),
                'purpose'       => $booking->purpose,
                'status'        => $booking->status,
                'status_text'   => $this->getStatusText($booking->status),
                'created_at'    => $booking->created_at->format('Y-m-d H:i:s'),
                'returned_at'   => $booking->returned_at ? $booking->returned_at->format('Y-m-d H:i:s') : null,
            ];
        });

        return response()->json([
            'code'    => 200,
            'message' => '获取成功',
            'data'    => [
                'total'        => $bookings->total(),
                'current_page' => $bookings->currentPage(),
                'per_page'     => $bookings->perPage(),
                'items'        => $items,
            ],
        ]);
    }

    /**
     * 申请归还设备
     * POST /api/bookings/{id}/return
     */
    public function returnBooking($id)
    {
        $user = Auth::user();

        $booking = Booking::where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        // 只有已通过状态才能归还
        if ($booking->status !== 'approved') {
            return response()->json([
                'code'    => 400,
                'message' => '当前状态不允许归还',
                'data'    => null,
            ]);

        }

        $booking->status = 'returned';
        $booking->returned_at = Carbon::now();
        $booking->save();

       return response()->json([
            'code'    => 200,
            'message' => '归还成功',
           'data'    => null,
        ]);
    }

    /**
     * 状态文本映射
     */
    private function getStatusText($status)
    {
        $map = [
            'pending'  => '待审核',
            'approved' => '已通过',
            'rejected' => '已拒绝',
            'returned' => '已归还',
        ];
        return $map[$status] ?? '未知';
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DeviceCategory;
use Illuminate\Http\Request;

class GyzController extends Controller
{

    // 1. 设备列表
    public function index(Request $request)
    {
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
            return [
                'id' => $item->id,
                'name' => $item->name,
                'model' => $item->model,
                'category_id' => $item->category_id,
                'category_name' => $item->category?->name,
                'description' => $item->description,
                'total_quantity' => $item->total_quantity,
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
    }

    // 2. 设备分类列表
    public function categories()
    {
        $list = DeviceCategory::get(['id', 'name', 'description']);

        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => $list
        ]);
    }

    // 3. 单台设备详情
    public function show($id)
    {
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
    }
}

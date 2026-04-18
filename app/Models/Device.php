<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'model',
        'description',
        'total_quantity',
        'available_quantity',   // 来自 main 分支
        'borrow_count',         // 来自 main 分支
        'status',
    ];

    // 关联分类
    public function category()
    {
        return $this->belongsTo(DeviceCategory::class, 'category_id');
    }

    // 关联借用记录（你的分支必须保留）
    public function bookings()
    {
        return $this->hasMany(Booking::class, 'device_id');
    }
}

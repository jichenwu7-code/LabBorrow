<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'device_id',
        'admin_id',
        'start_date',
        'end_date',
        'purpose',
        'status',
        'reject_reason',
        'returned_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'returned_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function device()
    {
        return $this->belongsTo(Device::class, 'device_id');
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    // 状态常量
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_RETURN_PENDING = 'return_pending';
    const STATUS_RETURNED = 'returned';

    // 状态文字（来自 main 分支）
    public function getStatusTextAttribute()
    {
        return match ($this->status) {
            'pending' => '待审核',
            'approved' => '已通过',
            'rejected' => '已拒绝',
            'return_pending' => '待审核归还',
            'returned' => '已归还',
            default => '未知'
        };
    }
}

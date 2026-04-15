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
        return $this->belongTo(User::class,'user_id');
    }

    public function device()
    {
        return $this->belongTo(Device::class,'device_id');
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    // 状态文字
    public function getStatusTextAttribute()
    {
        return match ($this->status) {
            'pending' => '待审核',
            'approved' => '已通过',
            'rejected' => '已拒绝',
            'returned' => '已归还',
            default => '未知'
        };
    }
}
 
?>
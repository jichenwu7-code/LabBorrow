<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    // 关联设备（你的分支必须保留）
    public function devices()
    {
        return $this->hasMany(Device::class, 'category_id');
    }
}

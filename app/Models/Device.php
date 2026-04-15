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
        'status',
    ];

    public function category()
    {
        return $this->belongTo(DeviceCategory::class,'category_id');
    }
}

?>
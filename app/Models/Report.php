<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'reporter_id',
        'reportable_type',
        'reportable_id',
        'reason',
        'detail',
        'status',
        'seen_at',
    ];

    protected $casts = [
        'seen_at' => 'datetime',
    ];

    // คนที่รายงาน
    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    // สิ่งที่ถูกรายงาน (หนังสือ หรือ ร้าน) - polymorphic
    public function reportable()
    {
        return $this->morphTo();
    }

    // แปลงประเภทเป็นข้อความไทย
    public function typeLabel()
    {
        return match($this->reportable_type) {
            'App\Models\Book' => 'หนังสือ',
            'App\Models\User' => 'ร้านค้า',
            default => 'ไม่ทราบ',
        };
    }
}
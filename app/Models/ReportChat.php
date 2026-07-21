<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportChat extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_id',
        'user_id',
        'is_closed',
    ];

    // รายงานที่แชทนี้เกี่ยวข้อง
    public function report()
    {
        return $this->belongsTo(Report::class);
    }

    // คู่สนทนา (ร้านหรือผู้รายงาน)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ข้อความในแชทนี้
    public function messages()
    {
        return $this->hasMany(ReportMessage::class);
    }
}
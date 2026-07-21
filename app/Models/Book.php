<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'author',
        'category',
        'type',
        'price',
        'description',
        'condition',
        'status',
    ];

    // หนังสือเล่มนี้เป็นของ user คนไหน
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // หนังสือเล่มนี้มีรูปภาพอะไรบ้าง
    public function images()
    {
        return $this->hasMany(BookImage::class);
    }

    // รายงานที่หนังสือเล่มนี้ได้รับ
    public function reports()
    {
        return $this->morphMany(Report::class, 'reportable');
    }
}
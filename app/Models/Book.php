<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasFactory;

    // หมวดหมู่ที่ระบบรองรับ ใช้เป็นรายการอ้างอิงให้ AI search ตรวจว่าหมวดที่ AI ตอบมามีจริงไหม
    // หมายเหตุ: ตอนนี้ dropdown ใน home/create/edit ยัง hardcode รายการเดียวกันนี้ไว้เอง
    // (ตั้งใจไม่แก้ในรอบนี้ ถ้าจะเพิ่มหมวดใหม่ต้องแก้ทั้ง 4 ที่จนกว่าจะยุบรวม)
    public const CATEGORIES = ['นิยาย', 'วิชาการ', 'การ์ตูน', 'ตำราเรียน', 'ธุรกิจ', 'จิตวิทยา', 'อื่นๆ'];

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

    // รูปหลักของประกาศ (รูปช่อง "ปกหนังสือ") ถ้าไม่มีให้ fallback เป็นรูปแรก (รองรับหนังสือเก่า)
    public function coverImage()
    {
        return $this->images->firstWhere('slot', 'cover') ?? $this->images->first();
    }

    // รายงานที่หนังสือเล่มนี้ได้รับ
    public function reports()
    {
        return $this->morphMany(Report::class, 'reportable');
    }
}
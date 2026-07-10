<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'reviewer_id',
        'shop_id',
        'rating',
        'comment',
    ];

    // คนที่เขียนรีวิว
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    // ร้านที่ถูกรีวิว
    public function shop()
    {
        return $this->belongsTo(User::class, 'shop_id');
    }
}
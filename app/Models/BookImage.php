<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_id',
        'image_path',
        'ai_condition',
        'ai_score',
    ];

    // รูปนี้เป็นของหนังสือเล่มไหน
    public function book()
    {
        return $this->belongsTo(Book::class);
    }
}
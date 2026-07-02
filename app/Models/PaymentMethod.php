<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'qr_image',
        'bank_name',
        'account_number',
        'account_name',
    ];

    // ช่องทางนี้เป็นของ user คนไหน
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
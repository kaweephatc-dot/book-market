<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'address',
        'shop_name',
        'avatar',
        'is_shop',
        'is_admin',
        'is_banned',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    // ช่องทางการชำระเงินทั้งหมดของผู้ใช้คนนี้
    public function paymentMethods()
    {
        return $this->hasMany(PaymentMethod::class);
    }

    // รีวิวที่ร้านนี้ได้รับ
    public function reviews()
    {
        return $this->hasMany(Review::class, 'shop_id');
    }

    // รีวิวที่ผู้ใช้คนนี้เขียนให้คนอื่น
    public function reviewsGiven()
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }

    // คะแนนดาวเฉลี่ยของร้าน
    public function averageRating()
    {
        return round($this->reviews()->avg('rating'), 1);
    }

    // จำนวนรีวิวทั้งหมด
    public function reviewCount()
    {
        return $this->reviews()->count();
    }

    // หนังสือทั้งหมดของผู้ใช้คนนี้
    public function books()
    {
        return $this->hasMany(Book::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasApiTokens,HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'display_name',
        'email',
        'password',
        'token',
        'code',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    public function billingAddress(){
        return $this->hasOne(BillingAddress::class);
    }
    public function shippingAddress(){
        return $this->hasOne(ShippingAddress::class);
    }
    public function orders(){
        return $this->hasMany(Order::class);
    }

    public function cart(){
        return $this->hasOne(Cart::class);
    }
    public function wishlist(){
        return $this->hasOne(Wishlist::class);
    }
    public function coupons(){
        return $this->belongsToMany(Coupon::class);
    }


}

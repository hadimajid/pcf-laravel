<?php

namespace App\Models;

use App\Http\Controllers\ConfigController;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $guarded=[];
    public function user(){
        return $this->belongsTo(User::class);
    }
    public function items(){
        return $this->hasMany(OrderItem::class);
    }
    public function address(){
        return $this->hasOne(ShippingAddress::class);
    }

}

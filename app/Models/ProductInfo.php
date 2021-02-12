<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductInfo extends Model
{
    use HasFactory;
    protected $guarded=[];

    public function product(){
        return $this->belongsTo(Product::class,'ProductId');
    }
    public function highlights(){
        return $this->hasMany(Highlight::class,'ProductInfoId');
    }
    public function bullets(){
        return $this->hasMany(Bullet::class,'ProductInfoId');
    }
    public function features(){
        return $this->hasMany(Feature::class,'ProductInfoId');
    }
}

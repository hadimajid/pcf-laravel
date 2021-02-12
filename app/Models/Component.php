<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Component extends Model
{
    use HasFactory;
    protected $guarded=[];

    public function product(){
        return $this->belongsTo(Product::class,'ProductId');
    }
//    public function boxSize(){
//        return $this->hasOne(ComponentBoxSize::class,'ComponentBoxSizeId');
//
//    }
}

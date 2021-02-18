<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WishlistItem extends Model
{
    use HasFactory;
    protected $guarded=[];
    public function wishlist(){
        return $this->belongsTo(Wishlist::class,'wishlist_id');
    }
    public function product(){
        return $this->belongsTo(Product::class,'product_id');
    }
}

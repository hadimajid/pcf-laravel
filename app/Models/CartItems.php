<?php

namespace App\Models;

use App\Http\Controllers\ConfigController;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItems extends Model
{
    use HasFactory;
    protected $guarded=[];
    protected $appends=['price'];
    public function cart(){
        return $this->belongsTo(Cart::class,'cart_id');
    }
    public function getPriceAttribute(){
        return (ConfigController::calculateCartPrice($this->product_id)*$this->quantity);
    }
    public function product(){
        return $this->belongsTo(Product::class,'product_id');
    }
}

<?php

namespace App\Models;

use App\Http\Controllers\ConfigController;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;
    protected $guarded=[];
    protected $appends=['price'];

    public function order(){
        return $this->belongsTo(Order::class);
    }
    public function product(){
        return $this->belongsTo(Product::class);
    }
    public function getPriceAttribute(){
        return (ConfigController::calculateCartPrice($this->product_id)*$this->quantity);
    }

}

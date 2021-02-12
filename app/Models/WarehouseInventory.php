<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseInventory extends Model
{
    use HasFactory;
    protected $guarded=[];
    public function product(){
        return $this->belongsTo(Product::class,'ProductId');
    }
    public function warehouse(){
        return $this->belongsTo(Warehouse::class,'WarehouseId');
    }
}

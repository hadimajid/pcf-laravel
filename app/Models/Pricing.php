<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pricing extends Model
{
    use HasFactory;
    protected $guarded=[];
    protected $table='pricing';
    public function priceList(){
        return $this->hasMany(ProductPrice::class,'PriceId');
    }
}

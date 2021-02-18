<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PricingException extends Model
{
    use HasFactory;
    protected $guarded=[];
    public function priceList(){
        return $this->hasMany(PricingExceptionList::class,'PriceExceptionId ');
    }

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RelatedProductList extends Model
{
    use HasFactory;
    protected $guarded=[];

    public function product(){
        return $this->belongsTo(Product::class,'ProductId');
    }
    public function relatedProduct(){
        return $this->hasOne(Product::class,'id','RelatedProductId');
    }
}

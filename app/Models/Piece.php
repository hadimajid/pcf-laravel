<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Piece extends Model
{
    use HasFactory;
    protected $guarded=[];
    public function subcategory(){
        return $this->belongsTo(SubCategory::class,'SubCategoryId');
    }
    public function product(){
        return $this->hasOne(Piece::class,'PieceId');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubCategory extends Model
{
    use HasFactory;
    protected $guarded=[];
    public function category(){
        return $this->belongsTo(Category::class,'CategoryId','id');
    }
    public function products(){
        return $this->hasMany(Product::class,'SubcategoryId','id');

    }
    public function pieces(){
        return $this->hasMany(Piece::class,'SubCategoryId');
    }
}

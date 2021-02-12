<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;
    protected $guarded=[];
    public function subCategories(){
        return $this->hasMany(SubCategory::class,'CategoryId');
    }
    public function products(){
        return $this->hasMany(Product::class,'CategoryId','id');
    }
}

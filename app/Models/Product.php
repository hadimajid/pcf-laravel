<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $guarded=[];

//    public function boxSize(){
//        return $this->belongsTo(BoxSize::class,'BoxSizeId');
//    }
    public function measurements(){
        return $this->hasMany(Measurement::class,'ProductId');
    }
    public function materials(){
        return $this->hasMany(Material::class,'ProductId');
    }
    public function additionalFields(){
        return $this->hasMany(AdditionalField::class,'ProductId');
    }
    public function relatedProducts(){
        return $this->hasMany(RelatedProductList::class,'ProductId');
    }
    public function components(){
        return $this->hasMany(Component::class,'ProductId');
    }
    public function nextGenImages(){
        return $this->hasMany(NextGenImage::class,'ProductId');
    }
    public function category(){
        return $this->belongsTo(Category::class,'CategoryId');
    }
    public function subCategory(){
        return $this->belongsTo(SubCategory::class,'SubcategoryId');
    }
    public function piece(){
        return $this->belongsTo(Piece::class,'PieceId');
    }
    public function style(){
        return $this->belongsTo(Style::class,'StyleId');
    }
    public function collection(){
        return $this->belongsTo(CollectionModel::class,'CollectionId');
    }
    public function productLine(){
        return $this->belongsTo(ProductLine::class,'ProductLineId');
    }
    public function group(){
        return $this->belongsTo(Group::class,'GroupId');
    }
    public function inventory(){
        return $this->hasOne(WarehouseInventory::class,'ProductId');
    }
    public function ratings(){
        return $this->hasMany(Rating::class);
    }
    public function ratingUser(){
        return $this->hasMany(Rating::class);
    }
    public function productInfo(){
        return $this->belongsTo(ProductInfo::class,'ProductInfoId');
    }
    public function price(){
        return $this->hasOne(ProductPrice::class,'ProductId');
    }

}

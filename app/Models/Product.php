<?php
namespace App\Models;
use App\Http\Controllers\ConfigController;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Product extends Model
{
    use HasFactory;
    protected $guarded=[];
    protected $appends=['PromotionPrice','DiscountPercentage'];
    public function toArray()
    {
        $toArray = parent::toArray();
        $toArray['SalePrice'] = $this->SalePrice;
        return $toArray;
    }
    public function getSalePriceAttribute($value){
        if(!empty($this->ProductNumber)){

            return ConfigController::priceCalculator($value);
        }
        else{
            return $value;
        }
    }
    public function getPromotionPriceAttribute(){
        if(!empty($this->PromotionCheck)){
            return round(ConfigController::discountPrice($this->SalePrice),2);
        }
        return $this->SalePrice;
    }
    public function getDiscountPercentageAttribute(){
        if(!empty($this->PromotionCheck)) {
            return ConfigController::percentageCalculator();
        }
        return 0;
    }
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

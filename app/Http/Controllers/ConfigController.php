<?php
namespace App\Http\Controllers;
use App\Models\Product;
use App\Models\WebsiteSettings;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
class ConfigController extends Controller
{
//Pricing Formula:
//[3:47 PM] The regular price is "the price x 2.5"
//The sale price is "the price x 1.85"
    public static function priceCalculator($price){
        $newPrice=WebsiteSettings::first()->price==null?0:WebsiteSettings::first()->price;
        return $newPrice*$price;
    }
    public static function percentageCalculator(){
       return self::percentageCalculatorDecimal()*100;
    }
    public static function percentageCalculatorDecimal(){
        $discount=WebsiteSettings::first()->promotion==null?0:WebsiteSettings::first()->promotion;
        $price=WebsiteSettings::first()->price==null?0:WebsiteSettings::first()->price;
        $temp=0;
        if($discount >0 && $price>0){
            if($discount<=$price){
                $temp=1-($discount/$price);
            }elseif($discount>$price){
                $temp=1;
            }
        }
        return $temp;
    }
    public static function discountPrice($price){
        $discount=$price*self::percentageCalculatorDecimal();
        return $price-$discount;
    }
    public static function price(Collection $products){
        $products=$products->map(function ($p){
            if(!empty($p->ProductNumber)) {
                $p->SalePrice = self::priceCalculator($p->SalePrice);
                $p->PromotionPrice = round($p->SalePrice,2);
                $p->DiscountPercentage = 0;
            }else{
                $p->PromotionPrice = round($p->SalePrice,2);
                $p->DiscountPercentage = 0;
            }
            if(!empty($p->PromotionCheck)){
                $p->PromotionPrice = round(self::discountPrice($p->SalePrice),2);
                $p->DiscountPercentage = self::percentageCalculator();
            }
            return $p;
        });
        return $products;
    }
    public static function calculateTax($price){
        return round(($price*WebsiteSettings::first()->tax)/100,2);
    }
    public static function calculateTaxPrice($price){
        if(empty($price)){
            return 0;
        }
        $price+=(($price*WebsiteSettings::first()->tax)/100);
        $price+=WebsiteSettings::first()->delivery_fees;
        return round($price,2);
    }

    public static function calculateCartPrice($id){
        return Product::where('id',$id)->first()->PromotionPrice;
    }
}

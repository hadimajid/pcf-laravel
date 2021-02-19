<?php

namespace App\Http\Controllers;

use App\Models\WebsiteSettings;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class ConfigController extends Controller
{
//Pricing Formula:
//[3:47 PM] The regular price is “the price x 2.5”
//The sale price is “the price x 1.85”
    public static function priceCalculator($price){
        return $price*env('SALE_PRICE');
    }
    public static function percentageCalculator(){
         $temp=(WebsiteSettings::first()->promotion==null?0:WebsiteSettings::first()->promotion)/env('SALE_PRICE');
        if($temp>0){
            return (1-$temp)*100;
        }
        return $temp;
    }
    public static function percentageCalculatorDecimal(){
        $temp=(WebsiteSettings::first()->promotion==null?0:WebsiteSettings::first()->promotion)/env('SALE_PRICE');
        if($temp>0){
            return (1-$temp);
        }
        return $temp;
    }
    public static function discountPrice($price){
       $discount=$price*self::percentageCalculatorDecimal();
       $newPrice=$price-$discount;
       return $newPrice;
    }
    public static function price(Collection $products){
        $products=$products->map(function ($p){
            if(!empty($p->ProductNumber)) {
                $p->SalePrice = self::priceCalculator($p->SalePrice);
                $p->PromotionPrice = 0;
                $p->DiscountPercentage = 0;
            }
            if($p->PromotionCheck){
                $p->PromotionPrice = self::discountPrice($p->SalePrice);
                $p->DiscountPercentage = self::percentageCalculator();
            }
            return $p;
        });
        return $products;
    }
}

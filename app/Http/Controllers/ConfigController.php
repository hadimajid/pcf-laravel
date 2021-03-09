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
        return $price*WebsiteSettings::first()->price==null?0:WebsiteSettings::first()->price;
    }
    public static function percentageCalculator(){
         $temp=(WebsiteSettings::first()->promotion==null?0:WebsiteSettings::first()->promotion)/WebsiteSettings::first()->price==null?0:WebsiteSettings::first()->price;
        if($temp>0){
            return (1-$temp)*100;
        }
        return $temp;
    }
    public static function percentageCalculatorDecimal(){
        $discount=WebsiteSettings::first()->promotion==null?0:WebsiteSettings::first()->promotion;
        $price=WebsiteSettings::first()->price==null?0:WebsiteSettings::first()->price;
        $temp=0;
        if($discount >0 && $price>0){
            if($discount<=$price){
                $temp=1-($discount/$price);
            }else{
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
}

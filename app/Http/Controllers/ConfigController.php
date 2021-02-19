<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class ConfigController extends Controller
{
//Pricing Formula:
//[3:47 PM] The regular price is “the price x 2.5”
//The sale price is “the price x 1.85”
    public static function priceCalculator($price){
        return $price*2.5;
    }
    public static function price(Collection $products){
        $products=$products->map(function ($p){
            if(!empty($p->ProductNumber)) {
                $p->SalePrice = self::priceCalculator($p->SalePrice);
            }
            return $p;
        });
        return $products;
    }
}

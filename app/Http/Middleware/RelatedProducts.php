<?php

namespace App\Http\Middleware;

use App\Models\Product;
use App\Models\RelatedProductList;
use Closure;
use Illuminate\Http\Request;

class RelatedProducts
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        ini_set('max_execution_time', 600000);

//        $relatedProducts=RelatedProductList::whereNull('RelatedProductId')->get();
//        if($relatedProducts){
//            foreach ($relatedProducts as $relatedProduct){
//
//                $product=Product::where('ProductNumber','like',$relatedProduct->ProductNumber)->first();
//                if($product){
//                    $relatedProduct->RelatedProductId=$product->id;
//                    $relatedProduct->save();
//                }
//
//            }
//        }
        $relatedProducts=RelatedProductList::whereNull('RelatedProductId')->delete();

        return $next($request);
    }
}

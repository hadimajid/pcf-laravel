<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItems;
use App\Models\WebsiteSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Stripe\StripeClient;

class PaymentController extends Controller
{
    private StripeClient $stripe;
    public function __construct()
    {
        $this->stripe=new StripeClient(env('STRIPE_SK'));
        Stripe::setApiKey(env('STRIPE_SK'));

    }
    public function checkOutWithStripe(Request $request){
        $request->validate([
           'success_url'=>'required|url',
           'cancel_url'=>'required|url',
        ]);
        $user=auth()->guard('user')->user();
        $cart=$user->cart;
        if(!$cart){
            return false;
        }
        $items=CartItems::where('cart_id',$cart->id)->with('product.nextGenImages')->get();
        if(!$items){
            return false;
        }
        $checkoutItem=[];
        $tax_rate = \Stripe\TaxRate::create([
            'display_name' => 'Sales Tax',
            'inclusive' => false,
            'percentage' => WebsiteSettings::first()->tax?WebsiteSettings::first()->tax:0,
            'country' => 'US',
            'state' => 'CA',
            'jurisdiction' => 'US - CA',
            'description' => 'CA Sales Tax',
        ]);
        foreach ($items as $key=>$item){
            $checkoutItem[$key]['price_data']['currency']='usd';
            $checkoutItem[$key]['price_data']['unit_amount']=$item->product->PromotionPrice*100;
            $checkoutItem[$key]['price_data']['product_data']['name']=$item->product->Name;
            $checkoutItem[$key]['quantity']=$item->quantity;
            $checkoutItem[$key]['tax_rates']=[$tax_rate->id];
            $checkoutItem[$key]['price_data']['product_data']['images']=[$_SERVER['APP_URL'].'/'.$item->product->nextGenImages->pluck('name')[0]];
//                $item->product->nextGenImages->pluck('name')->map(function ($value){
//                    if($value){
//                        return $_SERVER['APP_URL'].'/'.$value;
//                    }
//                });
        }
//            return $checkoutItem;
//        $checkoutItem=[
//            [
//                'price_data' => [
//                    'currency' => 'usd',
//                    'unit_amount' => 2000,
//                    'product_data' => [
//                        'name' => 'Stubborn Attachments',
//                        'images' => ["https://i.imgur.com/EHyR2nP.png"],
//                    ],
//                ],
//                'quantity' => 1,
//            ],[
//                'price_data' => [
//                    'currency' => 'usd',
//                    'unit_amount' => 2000,
//                    'product_data' => [
//                        'name' => 'Stubborn Attachments',
//                        'images' => ["https://i.imgur.com/EHyR2nP.png"],
//                    ],
//                ],
//                'quantity' => 1,
//            ]
//        ];

        $checkout_session = Session::create([
            'payment_method_types' => ['card'],
            'shipping_rates' => ['shr_1IbjlWA0smjrwOKOJuGhAZBy'],
            'shipping_address_collection' => [
                'allowed_countries' => ['US', 'CA'],
            ],
            'client_reference_id'=>$user->id,
            'line_items' => $checkoutItem,
            'mode' => 'payment',
            'metadata'=>[
                'user_id'=>  $user->id,
                'cart_id'=>$cart->id
            ],
            'success_url' => $request->input('success_url'),
            'cancel_url' => $request->input('cancel_url'),
        ]);
        return Response::json(['id' => $checkout_session->id],200);
    }
}

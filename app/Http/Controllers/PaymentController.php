<?php

namespace App\Http\Controllers;

use App\Models\BillingAddress;
use App\Models\Cart;
use App\Models\CartItems;
use App\Models\ShippingAddress;
use App\Models\WebsiteSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Stripe\Checkout\Session;
use Stripe\Customer;
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
            'notes'=>'nullable',
            'ship'=>'nullable',
            'update'=>'nullable',
            'shipping_address'=>'required_if:ship,1|array',
            'coupon'=>'nullable',
            'shipping_address.name'=>'required_if:ship,1',
            'shipping_address.company_name'=>'nullable',
            'shipping_address.street_address'=>'required_if:ship,1',
            'shipping_address.city'=>'required_if:ship,1',
            'shipping_address.state'=>'required_if:ship,1',
            'shipping_address.zip'=>'required_if:ship,1',
            'shipping_address.country'=>'required_if:ship,1',
            'shipping_address.phone'=>'required_if:ship,1',
            'shipping_address.email'=>'required_if:ship,1|email',
            'billing_address.name'=>'required',
            'billing_address.company_name'=>'nullable',
            'billing_address.street_address'=>'required',
            'billing_address.city'=>'required',
            'billing_address.state'=>'required',
            'billing_address.zip'=>'required',
            'billing_address.country'=>'required',
            'billing_address.phone'=>'required',
            'billing_address.email'=>'required|email',
        ]);
        try {
            DB::beginTransaction();
            $user=auth()->guard('user')->user();
            $update=$request->input('update');
            $ship=$request->input('ship');
            if($update===1){
                $temp=$request->input('shipping_address');
                if(empty($user->shippingAddress)){
                    $shipping=new ShippingAddress();
                    $temp=array_merge($temp,['user_id'=>$user->id]);
                    $shipping->fill($temp);
                    $shipping->save();
                }else{
                    $shipping=$user->shippingAddress;
                    $shipping->fill($temp);
                    $shipping->save();
                }
            }
            if(empty($user->billingAddress)){
                $temp=$request->input('billing_address');
                $billingAddress=new BillingAddress();
                $temp=array_merge($temp,['user_id'=>$user->id]);
                $billingAddress->fill($temp);
                $billingAddress->save();
            }else{
                $temp=$request->input('billing_address');
                $billingAddress=$user->billingAddress;
                $billingAddress->fill($temp);
                $billingAddress->save();
            }

            if($ship===1){
                $add=$request->input('shipping_address');

            }else{
                $add=$request->input('billing_address');
            }
            $shippingTemp=new ShippingAddress();
            $shippingTemp->fill($add);
            $shippingTemp->save();

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
//            'shipping'=>[
//                'name'=>$add['name'],
//                'address'=>[
//                    'city'=>$add['city'],
//                    'country'=>$add['country'],
//                    'line1'=>$add['street_address'],
//                    'line2'=>$add['street_address'],
//                    'postal_code'=>$add['zip'],
//                    'state'=>$add['state'],
//                ]
//            ],
            $customer=Customer::create([
                'email'=>$user->email,
                'name'=>$user->first_name.' '.$user->last_name,
                'shipping'=>[
                    'name'=>$add['name'],
                    'address'=>[
                        'city'=>$add['city'],
                        'country'=>$add['country'],
                        'line1'=>$add['street_address'],
                        'line2'=>$add['street_address'],
                        'postal_code'=>$add['zip'],
                        'state'=>$add['state'],
                    ]
                ],
                    'address'=>[
                        'city'=>$add['city'],
                        'country'=>$add['country'],
                        'line1'=>$add['street_address'],
                        'line2'=>$add['street_address'],
                        'postal_code'=>$add['zip'],
                        'state'=>$add['state'],

                ],

            ]);
            $checkout_session = Session::create([
                'customer'=>$customer->id,
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
                    'cart_id'=>$cart->id,
                    'shipping_id'=>$shippingTemp->id,
                ],
                'success_url' => $request->input('success_url'),
                'cancel_url' => $request->input('cancel_url'),
            ]);
            DB::commit();
            return Response::json(['id' => $checkout_session->id],200);
        }catch (\Exception $ex){
            DB::rollback();
            return Response::json(['error'=>$ex->getMessage()],422);
        }
    }
}

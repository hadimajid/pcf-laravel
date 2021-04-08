<?php

namespace App\Http\Controllers;

use App\Models\BillingAddress;
use App\Models\Cart;
use App\Models\CartItems;
use App\Models\Coupon;
use App\Models\ShippingAddress;
use App\Models\WebsiteSettings;
use App\Traits\PayPal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Response;
use Stripe\Checkout\Session;
use Stripe\Customer;
use Stripe\Stripe;
use Stripe\StripeClient;

class PaymentController extends Controller
{
    use PayPal;
    private StripeClient $stripe;
    private string $token;
    public function __construct()
    {
        $this->stripe=new StripeClient(env('STRIPE_SK'));
        Stripe::setApiKey(env('STRIPE_SK'));
        $response = Http::withBasicAuth(env('PAYPAL_CLIENT_ID'), env('PAYPAL_SECRET'))->asForm()->
        post(env('PAYPAL_MODE').'/v1/oauth2/token', [
            'grant_type' => 'client_credentials',
        ]);
        $responseDecode=json_decode($response);
        $this->token=$responseDecode->token_type.' '.$responseDecode->access_token;

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
//            $tax_rate = \Stripe\TaxRate::create([
//                'display_name' => 'Sales Tax',
//                'inclusive' => false,
//                'percentage' => WebsiteSettings::first()->tax?WebsiteSettings::first()->tax:0,
//                'country' => 'US',
//                'state' => 'CA',
//                'jurisdiction' => 'US - CA',
//                'description' => 'CA Sales Tax',
//            ]);
//            foreach ($items as $key=>$item){
//                $checkoutItem[$key]['price_data']['currency']='usd';
//                $checkoutItem[$key]['price_data']['unit_amount']=$item->product->PromotionPrice*100;
//                $checkoutItem[$key]['price_data']['product_data']['name']=$item->product->Name;
//                $checkoutItem[$key]['quantity']=$item->quantity;
//                $checkoutItem[$key]['tax_rates']=[$tax_rate->id];
//                $checkoutItem[$key]['price_data']['product_data']['images']=[$_SERVER['APP_URL'].'/'.$item->product->nextGenImages->pluck('name')[0]];
//            }
            $coupon=$user->cart->coupon;;
//            $c=$this->getCart($user,$coupon)['apply_coupon'];
//            if($c){
//                $coupon=$request->input('coupon');
//            }else{
//                $coupon=null;
//            }
            $checkoutItem[0]['price_data']['currency']='usd';
            $checkoutItem[0]['price_data']['unit_amount']=self::getCart($user,$coupon)['total_price']*100;
            $checkoutItem[0]['price_data']['product_data']['name']="Total Bill";
            $checkoutItem[0]['quantity']=1;
            $checkoutItem[0]['price_data']['product_data']['images']=[];


//            $customer=Customer::create([
//                'email'=>$user->email,
//                'name'=>$user->first_name.' '.$user->last_name,
//                'shipping'=>[
//                    'name'=>$add['name'],
//                    'phone'=>$add['phone'],
//                    'address'=>[
//                        'city'=>$add['city'],
//                        'country'=>$add['country'],
//                        'line1'=>$add['street_address'],
//                        'line2'=>$add['street_address'],
//                        'postal_code'=>$add['zip'],
//                        'state'=>$add['state'],
//                    ]
//                ],
//                    'address'=>[
//                        'city'=>$add['city'],
//                        'country'=>$add['country'],
//                        'line1'=>$add['street_address'],
//                        'line2'=>$add['street_address'],
//                        'postal_code'=>$add['zip'],
//                        'state'=>$add['state'],
//
//                ],
//
//            ]);
            $checkout_session = Session::create([
//                'customer'=>$customer->id,
                'payment_method_types' => ['card'],
//                'shipping_rates' => ['shr_1IbjlWA0smjrwOKOJuGhAZBy'],
//                'shipping_address_collection' => [
//                    'allowed_countries' => ['US', 'CA'],
//                ],
                'client_reference_id'=>$user->id,
                'line_items' => $checkoutItem,
                'mode' => 'payment',
                'metadata'=>[
                    'user_id'=>  $user->id,
                    'cart_id'=>$cart->id,
                    'shipping_id'=>$shippingTemp->id,
                    'ship'=>$ship?$ship:0,
                    'coupon'=>$coupon?$coupon:"No Coupon",
                    'notes'=>$request->input('notes')?$request->input('notes'):"No Notes",
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
    public static function getCart($user,$coupon=null){
        $applyCoupon=false;
        $discount=0;
        $cart=null;
        $totalPrice=0;
        if($user->cart){
            $cart=CartItems::where('cart_id',$user->cart->id)->with(['product:id,Name,SalePrice,PromotionCheck,ProductNumber,slug','product.nextGenImages:ProductId,name','product.inventory.eta'])->get();
            $coupon=$user->cart->coupon_id;
            if($coupon){
                $couponCount=DB::table('coupon_user')
                    ->where('coupon_id','=',$coupon)
                    ->count();
                $getCoupon=Coupon::where('id',$coupon)
                    ->where('max_usage','<=',$couponCount)
                    ->where('to','>=',Carbon::now()->format('Y-m-d'))
                    ->where('from','<=',Carbon::now()->format('Y-m-d'))
                    ->first();
//            $validUser=null;
                if($getCoupon){
                    $validUser=$getCoupon->users->where('id',$user->id)->first();
                    if(!empty($validUser)){
                        if($validUser->pivot->count()<$getCoupon->max_usage_per_user){
                            $applyCoupon = true;
                            $discount = $getCoupon->discount;
                        }
                    }else{
                        $applyCoupon = true;
                        $discount = $getCoupon->discount;
                    }
                }
            }
            $prices=$cart->pluck('price');
            $totalPrice=round($prices->sum(),2);
            $subTotal=$totalPrice;
            if($discount){
                $d=($totalPrice*$discount)/100;
                $totalPrice=$totalPrice-$d;
            }
            $totalPrice=round($totalPrice,2);
            if(!$applyCoupon){
                if($user->cart){
                    $user->cart->coupon=null;
                    $user->cart->save();
                }
            }
            return [
                'cart'=>$cart,
                'sub_total'=>$subTotal,
                'sub_total_discount'=>$totalPrice,
                'tax'=>ConfigController::calculateTax($totalPrice),
                'shipping'=>$subTotal?WebsiteSettings::first()->delivery_fees:0,
                'apply_coupon'=>$applyCoupon,
                'coupon_discount'=>$discount,
                'total_price'=>ConfigController::calculateTaxPrice($totalPrice)
            ];
        }
        return [];
    }

}

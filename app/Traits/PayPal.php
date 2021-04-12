<?php

namespace App\Traits;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PaymentController;
use App\Models\BillingAddress;
use App\Models\CartItems;
use App\Models\DeliveryFees;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaypalOrder;
use App\Models\ShippingAddress;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Response;

trait PayPal{
    public function payNow(Request $request){
        $request->validate([
            'success_url'=>'required|url',
            'cancel_url'=>'required|url',
            'notes'=>'nullable',
//            'delivery_fee'=>'required,exists:delivery_fees,id',
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
            $user = auth()->guard('user')->user();
            $update = $request->input('update');
            $ship = $request->input('ship');
            if (!empty($update) && $update == 1) {
                $temp = $request->input('shipping_address');
                if (empty($user->shippingAddress)) {
                    $shipping = new ShippingAddress();
                    $temp = array_merge($temp, ['user_id' => $user->id]);
                    $shipping->fill($temp);
                    $shipping->save();
                } else {
                    $shipping = $user->shippingAddress;
                    $shipping->fill($temp);
                    $shipping->save();
                }
            }
            if (empty($user->billingAddress)) {
                $temp = $request->input('billing_address');
                $billingAddress = new BillingAddress();
                $temp = array_merge($temp, ['user_id' => $user->id]);
                $billingAddress->fill($temp);
                $billingAddress->save();
            } else {
                $temp = $request->input('billing_address');
                $billingAddress = $user->billingAddress;
                $billingAddress->fill($temp);
                $billingAddress->save();
            }

            if (!empty($ship) && $ship == 1) {
                $add = $request->input('shipping_address');
            } else {
                $add = $request->input('billing_address');
            }
            $shippingTemp = new ShippingAddress();
            $shippingTemp->fill($add);
            $shippingTemp->save();

            $cart = $user->cart;
            if (!$cart) {
                return false;
            }
            $items = CartItems::where('cart_id', $cart->id)->with('product.nextGenImages')->get();
            if (!$items) {
                return false;
            }
            $checkoutItem = [];
            $coupon = $user->cart->coupon;;

            $totalAmount = self::getCart($user, $coupon)['total_price'];
            $order = json_decode(Http::
            withHeaders(
                [
                    "Accept" => "application/json",
                    "Authorization" => $this->token,
                    "Content-Type" => "application/json",
                    "PayPal-Request-Id" => "ORDER-" . uniqid() . time() . date('d-m-y'),
                ])
                ->post(env('PAYPAL_MODE') . '/v2/checkout/orders',
                    [
                        "intent" => "CAPTURE",
                        "purchase_units" => [
                            [
                                'amount' => [
                                    "value" => $totalAmount,
                                    "currency_code" => "USD"
                                ],
                            ]
                        ],
                        'application_context' => [
                            'return_url' => $request->input('success_url'),
                            'cancel_url' => $request->input('cancel_url'),
                        ]
                    ]
                ));
            PaypalOrder::create([
                'order_id' => $order->id,
                'user_id' => \auth()->guard('user')->user()->id,
                'ship'=>$ship,
                'notes'=>$request->input('notes'),
                'shipping_id'=>$shippingTemp->id
            ]);
            DB::commit();
//            return [$order];
            return redirect($this->getApprovalLink($order));
        }catch(\Exception $ex){
            DB::rollBack();
            return Response::json(['error'=>$ex->getMessage()],422);
        }
    }
    public function getApprovalLink($order){
        foreach ($order->links as $link){
            if($link->rel=='approve'){
                return $link->href;
            }
        }
    }
    public function success(Request $request){

        $returnUrl='http://127.0.0.1:8000/api/paypal/success';
        $cancelUrl='http://127.0.0.1:8000/api/paypal/cancel';
        $token=$request->get('token');
        $orderSuccess=
            Http::
        withHeaders(
            [
                "Accept"=> "application/json",
                "Authorization"=> $this->token,
                "Content-Type"=> "application/json",
                "PayPal-Request-Id"=> "ORDER-".uniqid() . time() . date('d-m-y'),
            ]
        )
            ->post(env('PAYPAL_MODE')."/v2/checkout/orders/".$token."/capture",
                [
                    'application_context'=>[
                        'return_url'=>$returnUrl,
                        'cancel_url'=>$cancelUrl,
                        ]
                ]);
        $orderSuccess=json_decode($orderSuccess);
        if(property_exists($orderSuccess,'status') && $orderSuccess->status=="COMPLETED"){
            $od=PaypalOrder::where('order_id',$orderSuccess->id)->first();
            $user=$od->user;
            $items=CartItems::where('cart_id',$user->cart->id)->get();
            $coupon=$user->cart->coupon_id;
            $cart=PaymentController::getCart($user,null,false);
            $discount=$cart['coupon_discount'];
            $totalPrice=$cart['total_price'];
            if($discount){
                $user->coupons()->attach($coupon);
            }
            $subTotal=$cart['sub_total_discount'];
            $tax=$cart['tax'];
            $delivery_fees=$cart['shipping'];



            $order=Order::create([
                'user_id'=>$user->id,
                'status'=>'pending',
                'notes'=>$od->notes,
                'ship'=>$od->ship,
                'tax'=>$tax,
                'sub_total'=>$cart['sub_total'],
                'shipping'=>$delivery_fees,
                'total'=>$totalPrice,
                'discount'=>$discount,
            ]);
            $shipping_address=ShippingAddress::find($od->shipping_id);
            $shipping_address->order_id=$order->id;
            $shipping_address->save();
            foreach ($items as $key=>$product){
                $orderItem=OrderItem::create([
                    'order_id'=>$order->id,
                    'product_id'=>$product->product_id,
                    'quantity'=>$product->quantity,
                ]);
            }
            $payment=new Payment([
                'payment_id'=>$orderSuccess->purchase_units[0]->payments->captures[0]->id,
                'charge_id'=>null,
                'order_id'=>$order->id,
                'payment_by'=>'paypal',
                'total_price'=>$orderSuccess->purchase_units[0]->payments->captures[0]->amount->value,
            ]);
            $payment->save();
            CartItems::where('cart_id',$user->cart->id)->delete();
            $user->cart->coupon_id=null;
            $user->cart->delivery_fee_id=null;
            $user->cart->save();
            return Response::json($orderSuccess);
            }
        else{
            return Response::json($orderSuccess,422);
        }
        }
        public function cancel(Request $request){
        return $request;
        }
        public function getOrderDetails($order_id){
        $order_id="73E60493JA419774R";
            $order=json_decode(Http::
            withHeaders(
                [
                    "Authorization"=> $this->token,
                    "Content-Type"=> "application/json"
                ])
                ->get(env('PAYPAL_MODE').'/v2/checkout/orders/'.$order_id));
            return [$order];

        }
}

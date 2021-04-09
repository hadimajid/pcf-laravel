<?php

namespace App\Jobs;

use App\Http\Controllers\ConfigController;
use App\Http\Controllers\PaymentController;
use App\Models\BillingAddress;
use App\Models\CartItems;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\ShippingAddress;
use App\Models\User;
use App\Models\WebsiteSettings;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Spatie\WebhookClient\Models\WebhookCall;
class CheckOutSessionCompleted implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public $webhookCall;

    public function __construct(WebhookCall $webhookCall)
    {
        $this->webhookCall = $webhookCall;
    }


    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $dataObject=$this->webhookCall->payload['data']['object'];
        if($dataObject['payment_status']==='paid'){
//            try {
//                DB::beginTransaction();
                $user= User::find($dataObject['metadata']['user_id']);
                $items=CartItems::where('cart_id',$user->cart->id)->get();

                $coupon=$user->cart->coupon_id;

            $cart=PaymentController::getCart($user,null,false);
            $discount=$cart['coupon_discount'];
            $totalPrice=$cart['total_price'];
            if($discount){
                    $user->coupons()->attach($coupon);
//                    $getCoupon=Coupon::find($coupon);
//                    $getCoupon->max_usage=$getCoupon->max_usage-1;
//                    $getCoupon->save();
                }
                $subTotal=$cart['sub_total_discount'];
                $tax=$cart['tax'];
                $delivery_fees=$cart['shipping'];
                if($dataObject['metadata']['notes']=="No Notes"){
                    $notes=null;
                }else{
                    $notes=$dataObject['metadata']['notes'];
                }
                $order=Order::create([
                    'user_id'=>$user->id,
                    'status'=>'pending',
                    'notes'=>$notes,
                    'ship'=>$dataObject['metadata']['ship'],
                    'tax'=>$tax,
                    'sub_total'=>$cart['sub_total'],
                    'shipping'=>$delivery_fees,
                    'total'=>$totalPrice,
                    'discount'=>$discount,
                ]);
                $shipping_address=ShippingAddress::find($dataObject['metadata']['shipping_id']);
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
                    'payment_id'=>$dataObject['payment_intent'],
                    'charge_id'=>null,
                    'order_id'=>$order->id,
                    'payment_by'=>'stripe',
                    'total_price'=>$dataObject['amount_total']/100,
                ]);
                $payment->save();

                CartItems::where('cart_id',$user->cart->id)->delete();
                $user->cart->coupon_id=null;
                $user->cart->save();
//                DB::commit();

//                exit();
//                return Response::json(['message'=>'Order sent.']);
//            }
//            catch (\Exception $ex){
//                DB::rollback();
//                exit();
//                return Response::json([$ex->getMessage()],422);
//            return Response::json(['message'=>'Some error has occurred while placing order.'],422);
//            }

        }

    }
}

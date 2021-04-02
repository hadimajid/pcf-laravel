<?php

namespace App\Jobs;

use App\Http\Controllers\ConfigController;
use App\Models\BillingAddress;
use App\Models\CartItems;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\ShippingAddress;
use App\Models\User;
use App\Models\WebsiteSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
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
        $dataObject= $this->webhookCall->payload['data']['object'];
        if($dataObject['payment_status']=='paid'){
            try {
                DB::beginTransaction();
                $user= User::find($dataObject['metadata']['user_id']);
                $items=CartItems::where('cart_id',$user->cart->id)->get();
                $o=[];
                foreach ($items as $item){
//                    if($this->productQuantityCheck($item->product_id,$item->quantity)){
                        $o[]=$item;
//                    }
                }
                $o=collect($o);
                $count=$o->count();
                if(empty($count)){
                    exit();
//                    return Response::json(['message'=>'Cart Empty Cannot Place Order!']);
                }
                $prices=$o->pluck('price');
                $totalPrice=round($prices->sum(),2);
                $discount=0;
                $coupon=$dataObject['metadata']['coupon'];
                if($coupon){
                    $getCoupon=Coupon::where('code',$coupon)->first();
                    if($getCoupon){
                        $validUser=$getCoupon->users->where('id',$user->id)->where('pivot.status','not_used')->first();
                    }
                    if(!empty($validUser)){
                        $discount=$getCoupon->discount;
                    }
                }
                if($discount){
                    $d=($totalPrice*$discount)/100;
                    $totalPrice=$totalPrice-$d;
                    $validUser->pivot->status="expired";
                    $validUser->pivot->save();
                }
                $subTotal=$totalPrice;
                $tax=ConfigController::calculateTax($totalPrice);
                $delivery_fees=WebsiteSettings::first()->delivery_fees;
                $totalPrice=ConfigController::calculateTaxPrice($totalPrice);

                $order=Order::create([
                    'user_id'=>$user->id,
                    'status'=>'pending',
                    'notes'=>$dataObject['metadata']['notes'],
                    'ship'=>$dataObject['metadata']['ship'],
                    'tax'=>$tax,
                    'sub_total'=>$subTotal,
                    'shipping'=>$delivery_fees,
                    'total'=>$totalPrice,
                    'discount'=>$discount,
                ]);
                $shipping_address=ShippingAddress::find($dataObject['metadata']['shipping_id']);
                $shipping_address->order_id=$order->id;
                foreach ($o as $key=>$product){
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
                DB::commit();
                $this->cartEmpty();
                exit();
//                return Response::json(['message'=>'Order sent.']);
            }catch (\Exception $ex){
                DB::rollback();
                exit();
//                return Response::json([$ex->getMessage()],422);
//            return Response::json(['message'=>'Some error has occurred while placing order.'],422);
            }

        }

    }
}

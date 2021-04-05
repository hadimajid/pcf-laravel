<?php

namespace App\Traits;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Response;

trait PayPal{
    public function payNow($totalAmount,$returnUrl=null,$id=null){

        $returnUrl='http://127.0.0.1:8000/api/success';
        $cancelUrl='http://127.0.0.1:8000/api/cancel';
        $id='1';
        $order=json_decode(Http::
        withHeaders(
            [
                "Accept"=> "application/json",
                "Authorization"=> $this->token,
                "Content-Type"=> "application/json",
                "PayPal-Request-Id"=> "ORDER-".uniqid() . time() . date('d-m-y'),
            ])
            ->post(env('PAYPAL_MODE').'/v2/checkout/orders',
                   [
                       "custom_id"=>$id,
                       "intent"=> "CAPTURE",
                       "purchase_units"=>[
                        [
                        'amount' => [
                            "value"=> $totalAmount,
                            "currency_code"=> "USD"
                        ],
                    ]
                ],
                       'application_context'=>[
                        'return_url'=>$returnUrl,
                        'cancel_url'=>$cancelUrl,
                        ]
                   ]
            ));
        return redirect($this->getApprovalLink($order));
    }
    public function getApprovalLink($order){
        foreach ($order->links as $link){
            if($link->rel=='approve'){
                return $link->href;
            }
        }
    }
    public function newTest(Request $request){

        $returnUrl='http://127.0.0.1:8000/api/success';
        $cancelUrl='http://127.0.0.1:8000/api/cancel';
        $token=$request->get('token');
        $orderSuccess=
            Http::
        withHeaders(
            [
                "Accept"=> "application/json",
                "Authorization"=> $this->token,
                "Content-Type"=> "application/json",
                "PayPal-Request-Id"=> "ORDER-".uniqid() . time() . date('d-m-y'),
//                "PayPal-Client-Metadata-Id"=>$id
            ]
        )
            ->post(env('PAYPAL_MODE')."/v2/checkout/orders/".$token."/capture",['application_context'=>[
                'return_url'=>$returnUrl,
                'cancel_url'=>$cancelUrl,
            ]]);
        return [json_decode($orderSuccess)];
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

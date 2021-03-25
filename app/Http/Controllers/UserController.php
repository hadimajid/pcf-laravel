<?php

namespace App\Http\Controllers;

use App\Models\BillingAddress;
use App\Models\Cart;
use App\Models\CartItems;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Rating;
use App\Models\ShippingAddress;
use App\Models\SubCategory;
use App\Models\User;
use App\Models\WebsiteSettings;
use App\Models\Wishlist;
use App\Models\WishlistItem;
use App\Rules\ArraySize;
use App\Rules\Unique;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function __construct()
    {
            ini_set('max_execution_time', 600000);
    }
    public function login(Request $request){
            $rules=[
                'email'=>'required',
                'password'=>'required',
            ];
            $validator=Validator::make($request->all(),$rules) ;
            if ($validator->fails()) {
            return Response::json(['errors'=>$validator->errors()],400);
            }
            $user=User::where('email',$request->email)->orWhere('display_name',$request->email)->first();
            if(!empty($user)){
                if($user->blocked==1){
                    return Response::json(['message'=>'User blocked.'],422);
                }
                if(empty($user->email_verified_at)){

                    return Response::json(['message'=>'Please verify your email an email has been sent to you.'],422);
                }

                if(Hash::check($request->password,$user->password)){

                    $token=  $user->createToken($request->email,['basic'])->accessToken;
                    return Response::json([
                        'message'=>'Sign in successful',
                        'user'=>$user,
                        'token'=>$token
                    ],
                        200);
                }
            }
            return Response::json(['message'=>'Username/Email or Password incorrect.'],422);
    }
    public function register(Request $request){
        $rules=[
            'email'=>'required|email|unique:users,email',
            'username'=>'required|unique:users,display_name',
            'password'=>'required',
            'url'=>'required',
        ];
        $validator=Validator::make($request->all(),$rules) ;
        if ($validator->fails()) {
            return Response::json(['errors'=>$validator->errors()],422);
        }
        $token=strtoupper(Str::random(20));
        $code=strtoupper(Str::random(5));
        $user=User::create([
            'email'=>$request->email,
            'password'=>Hash::make($request->password),
            'display_name'=>$request->username,
            'token'=>$token,
            'code'=>$code
        ]);

        MailController::sendVerifyEmail($user->email,$token,$code,$request->url);
        return Response::json(['message'=>'Sign up successful'],200);
    }
    public function verify(Request $request,$token,$email){
        $user=User::where('email',$email)->where('token',$token)->first();
        if(empty($user)){
            return Response::json(["message"=>'Invalid Link.'],422);
        }
        if(!empty($user->email_verified_at)){
            return Response::json(["message"=>'Email Already Verified.']);
        }
        $user->email_verified_at=Carbon::now();
        $user->token=null;
        $user->code=null;
        $user->save();

        return Response::json(["message"=>'Email successfully verified.']);


    }
    public function resendVerifyEmail(Request $request){
        $user=User::where('email',$request->email)->first();
        if(empty($user)){
            return Response::json('User doesn\'t exist.');
        }
        $token=strtoupper(Str::random(20));
        $code=strtoupper(Str::random(5));
        $user->token=$token;
        $user->code=$code;
        $user->save();
        MailController::sendVerifyEmail($user->email,$token,$code,$request->url);

        return Response::json(['message'=>'Email sent.']);


    }
    public function checkLoggedIn()
    {
        if (Auth::guard('user')->check()) {
            return Response::json(['message' => true], 200);
        } else {
            return Response::json(['message' => false], 200);
        }
    }
    public function logout (Request $request) {
        $token = $request->user()->token();
        $token->revoke();
        $response = ['message' => 'You have been successfully logged out!'];
        return Response::json($response, 200);
    }
    public function getBillingAddress(Request $request){
        $billingAddress=Auth::guard('user')->user()->billingAddress;
        return Response::json(['billing_address'=>$billingAddress]);
    }
    public function storeBillingAddress(Request $request){
        $rules=[
            'name'=>'required',
            'company_name'=>'required',
            'street_address'=>'required',
            'city'=>'required',
            'state'=>'required',
            'zip'=>'required',
            'country'=>'required',
            'phone'=>'required',
            'email'=>'required|email',

        ];
        $validator=Validator::make($request->all(),$rules) ;
        if ($validator->fails()) {
            return Response::json(['errors'=>$validator->errors()],400);
        }
        $user=User::find(\auth()->guard('user')->user()->id);
        if(empty($user->billingAddress)){
            $billing=new BillingAddress();
            $request->merge(['user_id'=>\auth()->guard('user')->user()->id]);
            $billing->fill($request->all());
            $billing->save();
        }else{
            $billing=$user->billingAddress;
            $billing->fill($validator->valid());
            $billing->save();
        }
        return Response::json([
            'message'=>'Billing address updated.',
            'data'=>$billing]);
    }

    public function getShippingAddress(Request $request){
        $shippingAddress=Auth::guard('user')->user()->shippingAddress;
        return Response::json(['shipping_address'=>$shippingAddress]);
    }
    public function storeShippingAddress(Request $request){
        $rules=[
            'name'=>'required',
            'company_name'=>'nullable',
            'street_address'=>'required',
            'city'=>'required',
            'state'=>'required',
            'zip'=>'required',
            'country'=>'required',
            'phone'=>'required',
            'email'=>'required|email',
        ];
        $validator=Validator::make($request->all(),$rules) ;
        if ($validator->fails()) {
            return Response::json(['errors'=>$validator->errors()],400);
        }
        $user=User::find(\auth()->guard('user')->user()->id);
        if(empty($user->shippingAddress)){
        $shipping=new ShippingAddress();
        $request->merge(['user_id'=>\auth()->guard('user')->user()->id]);
        $shipping->fill($request->all());
        $shipping->save();
        }else{
        $shipping=$user->shippingAddress;
        $shipping->fill($validator->valid());
        $shipping->save();
        }
        return Response::json([
            'message'=>'Shipping address added.',
            'data'=>$validator->valid()]);
    }
//    public function updateShippingAddress(Request $request){
//        $rules=[
//            'name'=>'required',
//            'company_name'=>'required',
//            'street_address'=>'required',
//            'city'=>'required',
//            'state'=>'required',
//            'zip'=>'required'
//        ];
//        $validator=Validator::make($request->all(),$rules) ;
//        if ($validator->fails()) {
//            return Response::json(['errors'=>$validator->errors()],400);
//        }
//        $shipping=\auth()->guard('user')->user()->shippingAddress;
//
//        return Response::json([
//            'message'=>'Shipping address updated.',
//            'data'=>$validator->valid()]);
//    }
    public function updateYourProfile(Request $request){
        $rules=[
            'first_name'=>'required',
            'last_name'=>'required',
            'display_name'=>['required',new Unique('users','display_name',\auth()->guard('user')->user()->id)],
//            'email'=>'required|email',
        ];
        $validator=Validator::make($request->all(),$rules);
        if($validator->fails()){
            return Response::json(['errors'=>$validator->errors()],400);
        }else{
            $user=User::find(\auth()->guard('user')->user()->id);
            $user->fill($validator->valid());
            $user->save();
            return Response::json([
                'message'=>'Profile updated.',
//                'data'=>$validator->valid(),
                'user'=>$user,
                ]
            );
        }
    }
    public function updateYourPassword(Request $request){
        $rules=[
            'password'=>'required',
            'new_password'=>'required',
            'confirm_new_password'=>'same:new_password',
        ];
        $validator=Validator::make($request->all(),$rules);
        if($validator->fails()){
            return Response::json(['errors'=>$validator->errors()],400);
        }
        else{
            $user=User::find(\auth()->guard('user')->user()->id);

            if(!Hash::check($request->password,$user->password)){
                return Response::json([
                        'message'=>'Current password incorrect.',
                    ]
                );
            }
            $user->password=Hash::make($request->new_password);
            $user->save();
            return Response::json([
                    'message'=>'Password updated.',
                ]
            );
        }
    }
    public function getCategories(Request $request){
        $page=0;
        $limit=Category::all()->count();
        $count=Category::all()->count();
        if(!empty($request->input('limit'))){
            $limit=$request->input('limit');
        }
        if(!empty($request->input('page'))){
            $page=($request->input('page')-1)*$limit;
        }
        if(empty($request->input('category_name'))) {
            $categories = Category::
                withCount('subCategories', 'products')
                ->with('subCategories')
                ->offset($page)
                ->limit($limit)
                ->get();
        }
        else{
            $categories=Category::
                where('CategoryName','like','%'.$request->input('category_name').'%')
                ->withCount('subCategories','products')
                ->with('subCategories')
                ->offset($page)
                ->limit($limit)
                ->get();
            $count=Category::where('CategoryName','like','%'.$request->input('category_name').'%')->count();
        }
        return Response::json(['categories'=>$categories,'total_number'=>$count,'filtered'=>$categories->count()],200);
    }
    //    Get All Products Search Filter Paginate
    public function getProducts(Request $request)
    {
        $category_name=$request->input('category_id');
        $slug=$request->input('slug');
        $category_slug=$request->input('category_slug');
        $subcategory_slug=$request->input('subcategory_slug');
        $subcategory_name=$request->input('subcategory_id');
        $product_name=$request->input('product_name');
        $style=$request->input('style_id');
        $material=$request->input('material');
        $color=$request->input('color');
        $warehouse=$request->input('warehouse');
        $type=$request->input('type');
        $page=0;
        $limit=Product::all()->count();
        $count=Product::all()->count();
        $sort=['id','asc'];
        if($request->input('sort')){
            $s= $request->input('sort');
            if($s==1){
                $sort=['id','asc'];
            }
            if($s==2){
                $sort=['id','asc'];

            }
            if($s==3){
                $sort=['id','desc'];
            }
            if($s==4){
                $sort=['SalePrice','asc'];
            }
            if($s==5){
                $sort=['SalePrice','desc'];
            }
        }
        if(!empty($request->input('limit'))){
            $limit=$request->input('limit');
            if($request->input('page')){
                $page=($request->input('page')-1)*$limit;
            }
        }
        $where='';
// If category id
        $b=0;
        $cat=null;

        if(!empty($category_slug)){
            $category_name=  Category::where('Slug','like',$category_slug)->first();
            if($category_name){
                $category_name=$category_name->id;
                $cat=$category_name;
            }
            else{
                $category_name=null;
            }
        }

        if(!empty($category_name)){
            if($where==''){
                $where.=" CategoryId = $category_name ";
            }else{
                $where.=" and CategoryId = $category_name ";
            }
            $b=1;
        }
//        if sub category
        $sub=null;

        if(!empty($subcategory_slug)){
            $subcategory_name=  SubCategory::where('Slug','like',$subcategory_slug)->first();
            if($subcategory_name){
                $subcategory_name=$subcategory_name->id;
                $sub=$subcategory_name;
            }
            else{
                $subcategory_name=null;
            }
        }
        if(!empty($subcategory_name)){
            if($where=='') {
                $where .= " SubCategoryId = $subcategory_name ";
            }else{
                $where .= " and SubCategoryId = $subcategory_name ";
            }
            $b=1;

        }
//        if hide
        $a=0;
        if($type=="1"){

            if($where=='') {
                $where .= " Hide = 1 ";
            }else{
                $where .= " and Hide = 1  ";
            }
            $a=1;
        }
        if($type=="2"){

            if($where=='') {
                $where .= " Hide = 0 ";
            }else{
                $where .= " and Hide = 0 ";
            }
            $a=1;
        }
        if($type=="3"){

            if($where=='') {
                $where .= " New = 1 ";
            }else{
                $where .= " and New =  1 ";
            }
            $a=1;
        }
        if($type=="4"){

            if($where=='') {
                $where .= " Featured = 1 ";
            }else{
                $where .= " and Featured =  1 ";
            }
            $a=1;
        }
//          if  product
        if(!empty($product_name)){
//            $product_name=str_replace('"','\"',$product_name);
            if($where=='') {
                $where.=" Name like '%$product_name%' ";
            }else{
                $where.=" and Name like '%$product_name%' ";
            }
            $b=1;
        }
//          if  product
        if(!empty($slug)){

            if($where=='') {
                $where.=" slug like '$slug' ";
            }else{
                $where.=" and slug like '$slug' ";
            }
            $b=1;
        }
//          if style
        if(!empty($style)){
            if($where=='') {
                $where.=" StyleId = $style ";
            }
            else{
                $where.=" and StyleId = $style ";
            }
            $b=1;
        }
        if(!empty($color)){
            if($where=='') {
                $where.=" (FabricColor like '%$color%' or FinishColor like '%$color%')";
            }else{
                $where.=" and (FabricColor like '%$color%' or FinishColor like '%$color%')";
            }
            $b=1;

        }
        if(!empty($material)  && empty($warehouse)){
            if($where==''){
                $products=Product::
                    whereHas('materials',function ($query) use ($material){
                        $query->where('Value','like',"%$material%");
                    })->offset($page)->limit($limit)
                    ->orderBy($sort[0],$sort[1])
                    ->with(AdminController::getRelationProduct())
                    ->withCount('ratings')
                    ->get();
                $count=Product::
                    whereHas('materials',function ($query) use ($material){
                        $query->where('Value','like',"%$material%");
                    })->count();
            }
            else{
                $products = Product::
                    whereRaw($where)
                    ->whereHas('materials', function ($query) use ($material) {
                        $query->where('Value', 'like', "%$material%");
                    })->offset($page)->limit($limit)
                    ->orderBy($sort[0],$sort[1])
                    ->with(AdminController::getRelationProduct())
                    ->withCount('ratings')
                    ->get();
                $count=Product::
                    whereRaw($where)
                    ->whereHas('materials', function ($query) use ($material) {
                        $query->where('Value', 'like', "%$material%");
                    })->count();
            }


        }
        if(empty($material) && !empty($warehouse)){
            if($where!=''){

                $products=Product::
                    whereHas('inventory',function ($query) use ($warehouse){
                        $query->where('WarehouseId','=',$warehouse);
                    })
                    ->whereRaw($where)
                    ->offset($page)->limit($limit)
                    ->orderBy($sort[0],$sort[1])
                    ->with(AdminController::getRelationProduct())
                    ->withCount('ratings')
                    ->get();
                $count=Product::
                    whereHas('inventory',function ($query) use ($warehouse){
                        $query->where('WarehouseId','like',$warehouse);
                    })
                    ->whereRaw($where)->count();
            }
            else{
                $products=Product::
                    whereHas('inventory',function ($query) use ($warehouse){
                        $query->where('WarehouseId','like',$warehouse);
                    })
                    ->offset($page)->limit($limit)
                    ->orderBy($sort[0],$sort[1])
                    ->with(AdminController::getRelationProduct())
                    ->withCount('ratings')
                    ->get();
                $count=Product::
                    whereHas('inventory',function ($query) use ($warehouse){
                        $query->where('WarehouseId','like',$warehouse);
                    })->count();
            }

        }if(!empty($material) && !empty($warehouse)){
        if($where!=''){

            $products=Product::
                whereHas('inventory',function ($query) use ($warehouse){
                    $query->where('WarehouseId','like',$warehouse);
                })
                ->whereHas('materials', function ($query) use ($material) {
                    $query->where('Value', 'like', "%$material%");
                })
                ->whereRaw($where)
                ->offset($page)->limit($limit)
                ->orderBy($sort[0],$sort[1])
                ->with(AdminController::getRelationProduct())
                ->withCount('ratings')
                ->get();
            $count=Product::
                whereHas('inventory',function ($query) use ($warehouse){
                    $query->where('WarehouseId','like',$warehouse);
                })
                ->whereHas('materials', function ($query) use ($material) {
                    $query->where('Value', 'like', "%$material%");
                })
                ->whereRaw($where)->count();
        }
        else{
            $products=Product::
                whereHas('inventory',function ($query) use ($warehouse){
                    $query->where('WarehouseId','like',$warehouse);
                })
                ->whereHas('materials', function ($query) use ($material) {
                    $query->where('Value', 'like', "%$material%");
                })
                ->offset($page)->limit($limit)
                ->orderBy($sort[0],$sort[1])
                ->with(AdminController::getRelationProduct())
                ->withCount('ratings')
                ->get();

            $count=Product::
                whereHas('inventory',function ($query) use ($warehouse){
                    $query->where('WarehouseId','like',$warehouse);
                })
                ->whereHas('materials', function ($query) use ($material) {
                    $query->where('Value', 'like', "%$material%");
                })->count();
        }

    }if(empty($material) && empty($warehouse)){
        if($where!=''){
            $products=Product::
                    whereRaw($where)
                ->offset($page)->limit($limit)
                ->orderBy($sort[0],$sort[1])
                ->with(AdminController::getRelationProduct())
                ->withCount('ratings')
                ->get();
            $count=Product::
                whereRaw($where)->count();
        }
        else{
            $products=Product::
                offset($page)->limit($limit)
                ->orderBy($sort[0],$sort[1])
                ->with(AdminController::getRelationProduct())
                ->withCount('ratings')
                ->get();
            $count=Product::all()->count();
        }
//            if($a==1 && $b==0){
//                $count=Product::
//                    whereRaw($where)
//                    ->count();
//            }
//            if($a==1 && $b==1){
//                $count=$products->count();
//            }
    }
//        $products=ConfigController::price($products);

        return Response::json([
            'products'=>$products,
            'category'=>$cat,
            'subcategory'=>$sub,
            'total_number'=>$count,
            'filtered'=>$products->count()]);
    }
    public function cart(Request $request){
        $request->validate([
            'product'=>'required|array|min:1',
            'product.*.id'=>'required|exists:products,id',
            'product.*.quantity'=>'required|numeric'
        ]);
           $user= User::find(Auth::guard('user')->user()->id);
           $cart=Cart::where('user_id',$user->id)->first();
            $products=$request->input('product');

        if($cart){
               foreach ($products as $key=>$product){
                    $item=CartItems::where('cart_id','=',$cart->id)->where('product_id','=',$product['id'])->first();
                    if($item){
                    $item->quantity=$product['quantity'];
                    $item->save();
                    }else{
                        CartItems::create([
                            'cart_id'=>$cart->id,
                            'product_id'=>$product['id'],
                            'quantity'=>$product['quantity']
                        ]);
                    }
               }

           }else {
               $cart=Cart::create([
                   'user_id' => $user->id,
               ]);
            foreach ($products as $key=>$product){

                   CartItems::create([
                       'cart_id' => $cart->id,
                       'product_id' => $product['id'],
                       'quantity' => $product['quantity']
                   ]);
               }
           }
        return Response::json(['message'=>'User cart is updated.']);
    }
    public function cartDelete(Request $request){
        $request->validate([
            'product_id'=>'required|exists:products,id',
        ]);
           $user= User::find(Auth::guard('user')->user()->id);
           $cart=$user->cart;
            $product_id=$request->input('product_id');
        if($cart){
            if($cart->items){
                $item=$cart->items->where('product_id','=',$product_id)->first();

                if($item){
                    $item->delete();
                    return Response::json(['message'=>'Product Deleted From Cart.']);

                }
            }
           }
        return Response::json(['message'=>'Product Not In Cart.']);
    }
    public function getCart($coupon=null){
        $user= User::find(Auth::guard('user')->user()->id);
        $applyCoupon=false;
        $discount=0;
        if($coupon){
            $getCoupon=Coupon::where('code',$coupon)->first();
            if($getCoupon){
                $validUser=$getCoupon->users->where('id',$user->id)->where('pivot.status','not_used')->first();
            }
            if(!empty($validUser)){
                $applyCoupon=true;
                $discount=$getCoupon->discount;
            }
        }
        $cart=null;
        $totalPrice=0;
        if($user->cart){
            $cart=CartItems::where('cart_id',$user->cart->id)->with(['product:id,Name,SalePrice,PromotionCheck,ProductNumber,slug','product.nextGenImages:ProductId,name','product.inventory.eta'])->get();
//            $prices=$cart->map(function ($value){
//                return $value->quantity*$value->product->PromotionPrice;
//            });
            $prices=$cart->pluck('price');
            $totalPrice=round($prices->sum(),2);
            $subTotal=$totalPrice;
            if($discount){
                $d=($totalPrice*$discount)/100;
                $totalPrice=$totalPrice-$d;
            }
            $totalPrice=round($totalPrice,2);
            return Response::json([
            'cart'=>$cart,
            'sub_total'=>$subTotal,
            'tax'=>ConfigController::calculateTax($totalPrice),
            'shipping'=>WebsiteSettings::first()->delivery_fees,
            'apply_coupon'=>$applyCoupon,
            'coupon_discount'=>$discount,
            'total_price'=>ConfigController::calculateTaxPrice($totalPrice)
            ]);
        }else{
            return Response::json(['cart'=>'empty']);
        }
    }
    public function cartEmpty(Request $request){
        $user= User::find(Auth::guard('user')->user()->id);

        $cart=$user->cart;
        if($cart){
            if($cart->items){
            foreach ($cart->items as $item){
                $item->delete();
            }
            }
        }
        return Response::json(['message'=>'Products deleted from cart.']);
    }
    public function wishlist(Request $request){
        $request->validate([
            'product_id'=>'required|array|min:1',
            'product_id.*'=>'exists:products,id',
        ]);
           $user= User::find(Auth::guard('user')->user()->id);
           $wishlist=$user->wishlist;
            $product_id=$request->input('product_id');

        if($wishlist){
               foreach ($product_id as $key=>$id){
                    $item=$wishlist->items->where('product_id','=',$id)->first();
                    if($item){

                    }else{
                        WishlistItem::create([
                            'wishlist_id'=>$wishlist->id,
                            'product_id'=>$id,
                        ]);
                    }
               }

           }else {
               $wishlist=Wishlist::create([
                   'user_id' => $user->id,
               ]);
               foreach ($product_id as $key => $id) {

                   WishlistItem::create([
                       'wishlist_id' => $wishlist->id,
                       'product_id' => $id,
                   ]);
               }
           }
        return Response::json(['message'=>'User wishlist is updated.']);
    }
    public function wishlistDelete(Request $request){
        $request->validate([
            'product_id'=>'required|exists:products,id',
        ]);
           $user= User::find(Auth::guard('user')->user()->id);
           $wishlist=$user->wishlist;
            $product_id=$request->input('product_id');
        if($wishlist){
            if($wishlist->items){
                $item=$wishlist->items->where('product_id','=',$product_id)->first();
                if($item){
                    $item->delete();
                    return Response::json(['message'=>'Product Delete From Wishlist.']);
                }
            }
           }
        return Response::json(['message'=>'Product Not In Wishlist.']);
    }
    public function getWishlist(){
        $user= User::find(Auth::guard('user')->user()->id);
        $wishlist=null;
        if($user->wishlist){
            $wishlist=$user->wishlist->with('items','items.product','items.product.nextGenImages')->get();
        }

        return Response::json(['wishlist'=>$wishlist]);
    }
    public function wishlistEmpty(Request $request){
        $user= User::find(Auth::guard('user')->user()->id);
        $wishlist=$user->wishlist;
        if($wishlist){
            if($wishlist->items){
            foreach ($wishlist->items as $item){
                $item->delete();
            }
            }
        }
        return Response::json(['message'=>'Products deleted from wishlist.']);
    }
    public function createOrder(Request $request){
        $request->validate([
            'product'=>'required|array|min:1',
            'product.*.id'=>'exists:products,id',
            'product.*.quantity'=>'required|numeric|min:1',
            'ship'=>'nullable',
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

        ]);
        try {
            DB::beginTransaction();
            $user= User::find(Auth::guard('user')->user()->id);
            $ship=$request->input('ship');
            $order=Order::create([
                'user_id'=>$user->id,
                'status'=>'pending',
                'ship'=>$ship?$ship:0,
                'tax'=>0,
                'sub_total'=>0,
                'shipping'=>0,
                'total'=>0
            ]);
            if($ship==1){
                $temp=$request->input('shipping_address');
                if(empty($user->shippingAddress)){
                    $shipping=new ShippingAddress();
                    $temp->merge(['user_id'=>$user->id]);
                    $shipping->fill($temp);
                    $shipping->save();
                }else{
                    $shipping=$user->shippingAddress;
                    $shipping->fill($temp);
                    $shipping->save();
                }
            }
            $productIds=$request->input('product');
            foreach ($productIds as $key=>$product){
                $orderItem=OrderItem::create([
                    'order_id'=>$order->id,
                    'product_id'=>$product['id'],
                    'quantity'=>$product['quantity'],
                ]);
            }
            $discount=0;
            $coupon=$request->input('coupon');
            if($coupon){
                $getCoupon=Coupon::where('code',$coupon)->first();
                if($getCoupon){
                    $validUser=$getCoupon->users->where('id',$user->id)->where('pivot.status','not_used')->first();
                }
                if(!empty($validUser)){
                    $discount=$getCoupon->discount;
                }
            }

            $o=OrderItem::where('order_id',$order->id)->get();
            $prices=$o->pluck('price');
            $totalPrice=round($prices->sum(),2);
            if($discount){
                $d=($totalPrice*$discount)/100;
                $totalPrice=$totalPrice-$d;
            }
            $subTotal=$totalPrice;
            $tax=ConfigController::calculateTax($totalPrice);
            $delivery_fees=WebsiteSettings::first()->delivery_fees;
            $totalPrice=ConfigController::calculateTaxPrice($totalPrice);
            $order->tax=$tax;
            $order->sub_total=$subTotal;
            $order->shipping=$delivery_fees;
            $order->total=$totalPrice;
            $order->discount=$discount;
            $order->save();
            DB::commit();
            return Response::json(['message'=>'Order sent.']);

        }catch (\Exception $ex){
            DB::rollback();
            return Response::json(['message'=>'Some error has occurred while placing order.']);
        }

    }
    public function rateProduct(Request $request){
        $request->validate([
            'product_id'=>'required',
            'rating'=>'required|numeric|min:1|max:5',
            'comment'=>'nullable'
        ]);
        $user= User::find(Auth::guard('user')->user()->id);

        try {

            $rate = Rating::create([
                'user_id' => $user->id,
                'product_id' => $request->input('product_id'),
                'rating' => $request->input('rating'),
                'comment' => $request->input('comment'),
            ]);
        }catch (\Exception $exception){
            return Response::json(['message'=>'You already rated this product.'],422);
//            return Response::json(['message'=>$exception->getMessage()],422);
        }
        return Response::json(['message'=>'Product rated successfully.']);
    }
    public function getOrders(Request $request){
        $page=0;
        $limit=Order::all()->count();
        if(!empty($request->limit) && !empty($request->page)){
            $limit=$request->limit;
            $page=($request->page-1)*$limit;
        }
        $where=" user_id = ".\auth()->guard('user')->id();
        $orders=Order::whereRaw($where)->with('items')->withCount('items')->limit($limit)->offset($page)->get();
        $total=Order::whereRaw($where)->count();

        return Response::json([
            'orders'=>$orders,
            'total_number'=>$total,
            'filtered'=>$orders->count()
        ]);
    }

}

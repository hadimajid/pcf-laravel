<?php

namespace App\Http\Controllers;

use App\Models\BillingAddress;
use App\Models\Cart;
use App\Models\CartItems;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\DeliveryFees;
use App\Models\Material;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PasswordReset;
use App\Models\Product;
use App\Models\Rating;
use App\Models\ShippingAddress;
use App\Models\Style;
use App\Models\SubCategory;
use App\Models\User;
use App\Models\WebsiteSettings;
use App\Models\Wishlist;
use App\Models\WishlistItem;
use App\Rules\ArraySize;
use App\Rules\PasswordValidate;
use App\Rules\Unique;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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
                    return Response::json(['message'=>'block'],422);
                }
                if(empty($user->email_verified_at)){

                    return Response::json(['message'=>'Please verify your email address. An email has been sent to you for verification.'],422);
                }

                if(Hash::check($request->password,$user->password)){

                    $token=  $user->createToken($request->email,['basic'])->accessToken;
                    return Response::json([
                        'message'=>'Successfully Logged In!',
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
                        'password'=>['required',new PasswordValidate()],
            'url'=>'required',
            'order'=>'nullable'
        ];
        $validator=Validator::make($request->all(),$rules);
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
        if(!empty($request->input('order'))){
            $token=  $user->createToken($request->email,['basic'])->accessToken;
            return Response::json([
                'message'=>'Sign up successful!',
                'user'=>$user,
                'token'=>$token
            ],200);
        }
        return Response::json(['message'=>'Sign up successful!'],200);
    }
    public function verify(Request $request,$token,$email){
        $user=User::where('email',$email)->where('token',$token)->first();
        if(empty($user)){
            return Response::json(["message"=>'Sorry! The link is Invalid/Expired.'],422);
        }
        if(!empty($user->email_verified_at)){
            return Response::json(["message"=>'Email is Already Verified.']);
        }
        $user->email_verified_at=Carbon::now();
        $user->token=null;
        $user->code=null;
        $user->save();

        return Response::json(["message"=>'Email verified, thank you!']);


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
    public function sendForgotPasswordMail(Request $request){
        $request->validate([
           'email'=>'required|email',
           'url'=>'required',
        ]);
        $user=User::where('email',$request->email)->first();
        if(!$user){
            return Response::json(['message'=>'This user does not exist in our system.'],422);
        }else{
            $token=Str::random(20);
            PasswordReset::create([
                'email'=>$user->email,
                'token'=>$token
            ]);
            MailController::sendUserForgotPasswordMail($user->email,$token,$request->url);
            return Response::json(['message'=>'An email has been sent to you with password reset instructions.']);

        }
    }
    public function verifyForgotEmail(Request $request,$token,$email){
//        $token=$request->get('token');
//        $email=$request->get('email');
        if(!$token && !$email ){
            return Response::json(['message'=>'Sorry! The link is Invalid/Expired.'],422);
        }
        $password=PasswordReset::where(['email'=>$email,'token'=>$token])->orderBy('created_at','desc')->first();

        if(!$password){
            return Response::json(['message'=>'Sorry! The link is Invalid/Expired.'],422);
        }else{
            $date=Carbon::now();
            $passwordDate=new Carbon(strtotime($password->created_at));
            if($passwordDate->diffInMinutes($date)>env('PASSWORD_EXPIRE')){
                return Response::json(['message'=>'Sorry! The link is Invalid/Expired.'],422);
            }
            return Response::json(['message'=>'Sorry! The link is Invalid/Expired.'],200);
        }
    }
    public function changeForgotPassword(Request $request){

        $request->validate([
            'password'=>['required',new PasswordValidate()],
            'confirm_password'=>'required|same:password',
            'token'=>'required',
            'email'=>'required|email',
        ]);
                $token=$request->input('token');
        $email=$request->input('email');
        if(!$token && !$email ){
            return Response::json(['message'=>'Sorry! The link is Invalid/Expired.'],422);
        }
        $password=PasswordReset::where(['email'=>$email,'token'=>$token])->orderBy('created_at','desc')->first();

        if(!$password){
            return Response::json(['message'=>'Sorry! The link is Invalid/Expired.'],422);
        }else{

            $date=Carbon::now();
            $passwordDate=new Carbon(strtotime($password->created_at));

            if($passwordDate->diffInMinutes($date)>env('PASSWORD_EXPIRE')){
                return Response::json(['message'=>'Sorry! The link is Invalid/Expired.'],422);
            }
            $user=User::where('email',$email)->first();
            $user->password=Hash::make($request->password);
            $user->save();
            PasswordReset::where('email',$user->email)->delete();
            $this->revokeAllToken($user);
            return Response::json(['message'=>'Password successfully updated!'],200);

        }
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
            'message'=>'Billing address successfully updated!',
            'data'=>$billing
        ]);
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
            'message'=>'Shipping address successfully updated!',
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
            'display_name'=>['required',Rule::unique('users','display_name')->ignore(\auth()->guard('user')->id())],
        ];
        $validator=Validator::make($request->all(),$rules);
        if($validator->fails()){
            return Response::json(['errors'=>$validator->errors()],400);
        }else{
            $user=User::find(\auth()->guard('user')->user()->id);
            $user->fill($validator->valid());
            $user->save();
            return Response::json([
                'message'=>'Profile successfully updated!',
//                'data'=>$validator->valid(),
                'user'=>$user,
                ]
            );
        }
    }
    public function updateYourPassword(Request $request){
        $rules=[
            'password'=>'required',
            'new_password'=>['required',new PasswordValidate()],
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
                ,422);
            }
            $user->password=Hash::make($request->new_password);
            $user->save();
            return Response::json([
                    'message'=>'Password successfully updated!',
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
        $where=" id!=0";

        if(!empty($request->input('category_name'))) {
            $cat=$request->input('category_name');
            $where.=" and CategoryName like '%$cat%'";
        }

            $categories=Category::
                whereRaw($where)
                ->withCount(['subCategories', 'products'=>function($query){
                    $query->where('Hide',0);
                }])
                ->with('subCategories')
                ->offset($page)
                ->limit($limit)
                ->get();
            $count=Category::whereRaw($where)
               ->count();
        return Response::json(['categories'=>$categories,'total_number'=>$count,'filtered'=>$categories->count()],200);
    }
    public function getCategoriesNew(Request $request){
        $page=0;
        $limit=Category::all()->count();
        $count=Category::all()->count();
        if(!empty($request->input('limit'))){
            $limit=$request->input('limit');
        }
        if(!empty($request->input('page'))){
            $page=($request->input('page')-1)*$limit;
        }
        $where=" id!=0";

        if(!empty($request->input('category_name'))) {
            $cat=$request->input('category_name');
            $where.=" and CategoryName like '%$cat%'";
        }

            $whereHas="  New = 1 AND Hide = 0";

            $categories=Category::
                whereRaw($where)
                ->whereHas('products',function ($query) use ($whereHas){
                        $query->whereRaw($whereHas);
                })
                ->withCount('subCategories','products')
                ->with('subCategories')
                ->offset($page)
                ->limit($limit)
                ->get();
            $count=Category::whereRaw($where)->whereHas('products',function ($query) use ($whereHas){
                $query->whereRaw($whereHas);
            })->count();
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
        $image=$request->input('image');
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
                $sort=['PromotionPrice','asc'];
            }
            if($s==5){
                $sort=['PromotionPrice','desc'];
            }
        }
//
        if(!empty($request->input('limit'))){
            $limit=$request->input('limit');
            if($request->input('page')){
                $page=($request->input('page')-1)*$limit;
            }
        }
        $cat=null;
        if(!empty($category_slug)){
            $category_name=  Category::where('Slug','like',$category_slug)->first();
            if($category_name){
                $cat=$category_name->CategoryName;
                $category_name=$category_name->id;
            }
            else{
                $category_name=Category::orderBy('id','desc')->first()->id+1;
            }
        }

        $sub=null;
        if(!empty($subcategory_slug)){
            $subcategory_name=  SubCategory::where('Slug','like',$subcategory_slug)->first();
            if($subcategory_name){
                $sub=$subcategory_name->SubCategoryName;
                $subcategory_name=$subcategory_name->id;
            }
            else{
                $subcategory_name=SubCategory::orderBy('id','desc')->first()->id+1;

            }
        }
        $productsQuery = Product::where(function ($query) use ($image,$category_name,$subcategory_name,$slug,$product_name,$style,$color,$material,$warehouse,$type){
                if($image)
                {
                    $query->whereHas('nextGenImages');
                }
                if($category_name){
                    $query->where('CategoryId',$category_name);
                }
                if($subcategory_name){
                    $query->where('SubcategoryId',$subcategory_name);
                }
                if($slug){
                    $query->where('slug',$slug);
                }
                if($product_name){
                    $query->where('Name','like',"%$product_name%");
                }
                if($style){
                    $query->whereHas('style',function ($query) use ($style){
                        $query->where('StyleName',$style);
                    });
                }
                if($color){
                    $query->where(function ($query) use ($color){
                        $query->where('FabricColor','like',"%$color%")->orWhere('FinishColor','like',"%$color%");
                    });
                }
                if($material){
                    $query->whereHas('materials', function ($query) use ($material) {
                        $query->where('Value', 'like', "%$material%");
                    });
                }
                if($warehouse){
                    $query->whereHas('inventory', function ($query) use ($warehouse) {
                        $query->where('WarehouseId', '=', $warehouse);
                    });
                }
                if($type){
                    if ($type == "1") {
                        $query->where('Hide',1);
                    }
                    if ($type == "2") {
                        $query->where('Hide',0);
                    }
                    if ($type == "3") {
                        $query->where('New',1);
                    }
                    if ($type == "4") {
                        $query->where('Featured',1);

                    }

                }
            });
//        =>function($query) {
//        $query->whereHas('relatedProduct.nextGenImages');
//    }
        $count=$productsQuery
            ->count();
        $products=$productsQuery->offset($page)->limit($limit)
            ->with(self::getRelationProduct())->where('Hide',0)
            ->get();
        if($sort[1]=='desc'){
            $sorted=$products->sortByDesc($sort[0]);
        }else{
            $sorted=$products->sortBy($sort[0]);
        }

        return Response::json([
            'products'=>$sorted->values()->all(),
            'category'=>$cat,
            'subcategory'=>$sub,
            'total_number'=>$count,
            'filtered'=>$products->count()]);
    }

    public static function getRelationProduct(){
        return [
            'measurements'
            , 'materials'
            , 'additionalFields'
            , 'relatedProducts'=>function($query) {
                $query->whereHas('relatedProduct.nextGenImages');
            }
            , 'relatedProducts.relatedProduct'
            , 'relatedProducts.relatedProduct.inventory'
            , 'relatedProducts.relatedProduct.price'
            , 'relatedProducts.relatedProduct.nextGenImages'
            , 'components'
            , 'nextGenImages'
            , 'category'
            , 'subCategory'
            , 'piece'
            , 'collection'
            , 'style'
            , 'productLine'
            , 'group'
            , 'inventory.eta'
            , 'productInfo'
            , 'productInfo.highlights'
            , 'productInfo.bullets'
            , 'productInfo.features'
            , 'price'
            , 'ratings'=>function($query){
                $query->selectRaw('product_id, AVG(rating) as rating')
                    ->groupBy(['product_id']);
            }
            , 'ratingsCount'=>function($query){
                $query->selectRaw('product_id,product_id as pid,rating,(count(rating)/(SELECT count(rating) as count from ratings where `product_id`=`pid` group by `product_id`))*100 as rating_count')
                    ->groupBy(['rating','product_id']);
            }
            ,'ratingUser'
            ,'ratingUser.user'
        ];
    }
    public function ProductRating(Request $request)
    {
        $request->validate([
            'product_id'=>'required|exists:products,id',
        ]);
        $rating = Product::with(['ratings'=>function($query){
            $query->selectRaw('product_id, AVG(rating) as rating')
                ->groupBy(['product_id']);
        },'ratingUser','ratingUser.user'])->where('id',$request->product_id)->first();
        return Response::json(['ratings' => $rating]);
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

                    if($this->productQuantityCheck($product['id'],$product['quantity'])){
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
                    }else{
                        $item=CartItems::where('cart_id','=',$cart->id)->where('product_id','=',$product['id'])->first();
                        if($item) {
                        $item->delete();
                        }
                    }
               }

           }else {
               $cart=Cart::create([
                   'user_id' => $user->id,
               ]);

            foreach ($products as $key=>$product){

                if($this->productQuantityCheck($product['id'],$product['quantity'])){

                   CartItems::create([
                       'cart_id' => $cart->id,
                       'product_id' => $product['id'],
                       'quantity' => $product['quantity']
                   ]);
               }
            }
       }
        return Response::json(['message'=>'User cart is updated.']);
    }
    public function productQuantityCheck($id,$quantity){
        $product=Product::find($id);
        if($product->inventory->QtyAvail>=$quantity){
         return true;
        }
        return false;
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
    public function applyCoupon(Request $request){
        $request->validate([
           'coupon'=>'required',
        ]);
        $user= User::find(Auth::guard('user')->user()->id);
        $coupon=$request->input('coupon');
        $applyCoupon=false;
        $discount=0;
        if($user->cart){
            if($user->cart->items->count()){
                if($coupon){
                    $c=Coupon::where('code',$coupon)->first();
                    if(empty($c)){
                        $msg="Coupon does not exist.";

                    }else{
                        $couponCount=DB::table('coupon_user')
                            ->where('coupon_id','=',$c->id)
                            ->count();
                        $getCoupon=Coupon::where('id',$c->id)
                            ->where('max_usage','>',$couponCount)
                            ->where('to','>=',Carbon::now()->format('Y-m-d'))
                            ->where('from','<=',Carbon::now()->format('Y-m-d'))
                            ->first();
                        if($getCoupon){
                            $validUser=DB::table('coupon_user')
                                ->where('coupon_id','=',$getCoupon->id)
                                ->where('user_id','=',$user->id)
                                ->count();;
                            if(!empty($validUser)){
                                if($validUser<$getCoupon->max_usage_per_user){
                                    $applyCoupon = true;
                                    $discount = $getCoupon->discount;
                                }else{
                                    $msg="Max usage limit reach.";
                                    $msg="Given Coupon is already Availed ";
                                }
                            }else{
                                $applyCoupon = true;
                                $discount = $getCoupon->discount;
                            }
                        }else{
                            $msg="Coupon expired.";
                            $msg="Given Coupon is expired.";
                        }
                    }

                }
            }else{
                $msg="Cart Empty.";

            }
        }else{
            $msg="Cart Empty.";

        }
        if($applyCoupon){
            $user->cart->coupon_id=$getCoupon->id;
            $user->cart->save();
            return Response::json(['message'=>'Coupon Successfully Applied']);
        }else{
            return Response::json(['message'=>$msg],422);

        }

    }
    public function removeCoupon(){
        $user= User::find(Auth::guard('user')->user()->id);
        $user->cart->coupon_id=null;
        $user->cart->save();
        return Response::json(['message'=>'Coupon Successfully Removed.']);

    }
    public function getCart($coupon=null){
        $user= User::find(Auth::guard('user')->user()->id);
        return PaymentController::getCart($user);
    }
    public function cartEmpty(){
        $user= User::find(Auth::guard('user')->user()->id);
        $cart=$user->cart;
        if($cart){
            if($cart->items){
            foreach ($cart->items as $item){
                $item->delete();
            }
            }
            $cart->coupon_id=null;
            $cart->save();
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
            $wishlist=$user->wishlist->with('items','items.product.inventory','items.product.nextGenImages')->get();
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
                $user= User::find(Auth::guard('user')->user()->id);
                $items=CartItems::where('cart_id',$user->cart->id)->get();
                $o=[];
                foreach ($items as $item){
                    if($this->productQuantityCheck($item->product_id,$item->quantity)){
                        $o[]=$item;
                    }
                }
                $o=collect($o);
                $count=$o->count();
                if(empty($count)){
                    return Response::json(['message'=>'Cart Empty Cannot Place Order!']);
                }
                $prices=$o->pluck('price');
                $totalPrice=round($prices->sum(),2);
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


                $ship=$request->input('ship');
                $update=$request->input('update');
                $order=Order::create([
                    'user_id'=>$user->id,
                    'status'=>'pending',
                    'notes'=>$request->notes,
                    'ship'=>$ship?$ship:0,
                    'tax'=>$tax,
                    'sub_total'=>$subTotal,
                    'shipping'=>$delivery_fees,
                    'total'=>$totalPrice,
                    'discount'=>$discount,
                ]);
                if($update==1){
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
                foreach ($o as $key=>$product){
                    $orderItem=OrderItem::create([
                        'order_id'=>$order->id,
                        'product_id'=>$product->product_id,
                        'quantity'=>$product->quantity,
                    ]);
                }
                $address=$order->address;
                if(empty($address)){
                    if($ship==1){
                        $temp=$request->input('shipping_address');
                        $address=new ShippingAddress();
                        $temp=array_merge($temp,['order_id'=>$order->id]);
                        $address->fill($temp);
                        $address->save();
                    }else{
                        $temp=$billingAddress->toArray();
                        unset($temp['user_id']);
                        unset($temp['id']);
                        unset($temp['created_at']);
                        unset($temp['updated_at']);
                        $address=new ShippingAddress();
                        $temp=array_merge($temp,['order_id'=>$order->id]);
                        $address->fill($temp);
                        $address->save();
                    }
                }
                DB::commit();
                $this->cartEmpty();
                return Response::json(['message'=>'Order sent.']);
        }catch (\Exception $ex){
            DB::rollback();
            return Response::json([$ex->getMessage()],422);
//            return Response::json(['message'=>'Some error has occurred while placing order.'],422);
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
        $orders=Order::whereRaw($where)->with('items','address')->withCount('items')->limit($limit)->offset($page)->get();
        $total=Order::whereRaw($where)->count();

        return Response::json([
            'orders'=>$orders,
            'total_number'=>$total,
            'filtered'=>$orders->count()
        ]);
    }
    public function getOrderById($id){
        $order=Order::where('id',$id)->where('user_id',auth()->guard('user')->id())->with('items.product.nextGenImages','address')->withCount('items')->first();
        return Response::json([
            'order'=>$order,
        ]);
    }
    public function cancelOrder($id){
        $order=Order::where('id',$id)->where('user_id',auth()->guard('user')->id())->first();
        $message='';
        $status='';
            if(!empty($order)){
                $status=$order->status;

            }
        if($status=='pending'){
            $order->status='cancelled';
            $order->cancelled_by='user';
            $message="Order Cancelled!";
            MailController::sendOrderConfirmationEmail($order->user->email,[
                'type'=>'Order Cancelled',
                'order'=>$order,
                'cancelled_by'=>'user'
            ]);
            $order->save();
        }
        else if($status=='cancelled'){
            $message="Order Already Cancelled!";
        }
        else{
            $message="You cannot cancel order.";
        }
        return Response::json(['message'=>$message]);
    }
    public function revokeAllToken(User $user)
    {
        $userTokens = $user->tokens;
        foreach ($userTokens as $token) {
            $token->revoke();
        }
        return Response::json(['message' => "Tokens revoked."], 200);
    }
    public function contactUs(Request $request){
        $request->validate([
           'name'=>'required',
           'email'=>'required|email',
//           'phone'=>'required',
           'subject'=>'required',
           'message'=>'required',
            'contact_email'=>'required'
        ]);

        MailController::sendContactUsEmail($request->contact_email,$request->email,$request->name,$request->subject,$request->message);
        return Response::json(['message'=>'Contact us email sent to admin.']);
    }
    public function applyDeliveryFees(Request $request){
        $request->validate([
           'delivery_fee'=>'required|exists:delivery_fees,id'
        ]);
        $user=User::find(\auth()->guard('user')->user()->id);
        if($user->cart){
            $user->cart->delivery_fee_id=$request->input('delivery_fee');
            $user->cart->save();
        }
        return Response::json(['message'=>'Delivery Fees Applied.']);
    }
    public function getStyles(){
        $styles = Style::select('StyleName')->groupBy('StyleName')->get();
        return Response::json(['styles' => $styles]);
    }

}

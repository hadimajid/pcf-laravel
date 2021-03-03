<?php

namespace App\Http\Controllers;

use App\Models\BillingAddress;
use App\Models\Cart;
use App\Models\CartItems;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Rating;
use App\Models\ShippingAddress;
use App\Models\SubCategory;
use App\Models\User;
use App\Models\Wishlist;
use App\Models\WishlistItem;
use App\Rules\ArraySize;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function __construct()
    {
        ini_set('max_execution_time', 600000);

    }
    public function login(Request $request){
            $rules=[
                'email'=>'required|email',
                'password'=>'required'
            ];
            $validator=Validator::make($request->only('email','password'),$rules) ;
            if ($validator->fails()) {
            return Response::json(['errors'=>$validator->errors(),'old_data'=>$validator->valid()],400);
            }
            $user=User::where('email',$request->email)->first();
            if(!empty($user)){
                if($user->blocked==1){
                    return Response::json(['message'=>'User blocked.'],404);
                }
//                if($user->email_verified_at==null){
//                    return Response::json(['message'=>'Please verify your email.'],404);
//                }
                if(Hash::check($request->password,$user->password)){
                    Auth::guard('user')->setUser($user);
                    $token=  \auth()->guard('user')->user()->createToken($request->email,['basic'])->accessToken;
                    return Response::json([
                        'message'=>'Sign in successful',
                        'user'=>\auth()->guard('user')->user(),
                        'token'=>$token
                    ],
                        200);
                }
            }
            return Response::json(['message'=>'Sign in failed.'],422);
    }
    public function register(Request $request){
        $rules=[
            'email'=>'required|email|unique:users,email',
            'password'=>'required',
        ];
        $validator=Validator::make($request->only('email','password'),$rules) ;
        if ($validator->fails()) {
            return Response::json(['errors'=>$validator->errors(),'old_data'=>$validator->valid()],422);
        }
        User::create([
           'email'=>$request->email,
            'password'=>Hash::make($request->password)
        ]);
        return Response::json(['message'=>'Sign up successful'],200);
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
            'zip'=>'required'
        ];
        $validator=Validator::make($request->all(),$rules) ;
        if ($validator->fails()) {
            return Response::json(['errors'=>$validator->errors(),'old_data'=>$validator->valid()],400);
        }
        $billing=new BillingAddress();
        $request->merge(['user_id'=>\auth()->guard('user')->user()->id]);
        $billing->fill($request->all());
        $billing->save();
        return Response::json([
            'message'=>'Billing address added.',
            'data'=>$validator->valid()]);
    }
    public function updateBillingAddress(Request $request){
        $rules=[
            'name'=>'required',
            'company_name'=>'required',
            'street_address'=>'required',
            'city'=>'required',
            'state'=>'required',
            'zip'=>'required'
        ];
        $validator=Validator::make($request->all(),$rules) ;
        if ($validator->fails()) {
            return Response::json(['errors'=>$validator->errors(),'old_data'=>$validator->valid()],400);
        }
        $billing=\auth()->guard('user')->user()->billingAddress;
        $billing->fill($validator->valid());
        $billing->save();
        return Response::json([
            'message'=>'Billing address updated.',
            'data'=>$validator->valid()]);
    }
    public function getShippingAddress(Request $request){
        $shippingAddress=Auth::guard('user')->user()->shippingAddress;
        return Response::json(['shipping_address'=>$shippingAddress]);
    }
    public function storeShippingAddress(Request $request){
        $rules=[
            'name'=>'required',
            'company_name'=>'required',
            'street_address'=>'required',
            'city'=>'required',
            'state'=>'required',
            'zip'=>'required'
        ];
        $validator=Validator::make($request->all(),$rules) ;
        if ($validator->fails()) {
            return Response::json(['errors'=>$validator->errors(),'old_data'=>$validator->valid()],400);
        }
        $shipping=new ShippingAddress();
        $request->merge(['user_id'=>\auth()->guard('user')->user()->id]);
        $shipping->fill($request->all());
        $shipping->save();
        return Response::json([
            'message'=>'Shipping address added.',
            'data'=>$validator->valid()]);
    }
    public function updateShippingAddress(Request $request){
        $rules=[
            'name'=>'required',
            'company_name'=>'required',
            'street_address'=>'required',
            'city'=>'required',
            'state'=>'required',
            'zip'=>'required'
        ];
        $validator=Validator::make($request->all(),$rules) ;
        if ($validator->fails()) {
            return Response::json(['errors'=>$validator->errors(),'old_data'=>$validator->valid()],400);
        }
        $shipping=\auth()->guard('user')->user()->shippingAddress;
        $shipping->fill($validator->valid());
        $shipping->save();
        return Response::json([
            'message'=>'Shipping address updated.',
            'data'=>$validator->valid()]);
    }
    public function updateYourProfile(Request $request){
        $rules=[
            'first_name'=>'required',
            'last_name'=>'required',
            'display_name'=>'required',
            'email'=>'required|email',
        ];
        $validator=Validator::make($request->all(),$rules);
        if($validator->fails()){
            return Response::json(['errors'=>$validator->errors(),'old_data'=>$validator->valid()],400);
        }else{
            $user=User::find(\auth()->guard('user')->user()->id);
            $user->fill($validator->valid());
            $user->save();
            return Response::json([
                'message'=>'Profile updated.',
                'data'=>$validator->valid()]
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
            return Response::json(['errors'=>$validator->errors(),'old_data'=>$validator->valid()],400);
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
                    return Response::json(['message'=>'Product Delete From Cart.']);

                }
            }
           }
        return Response::json(['message'=>'Product Not In Cart.']);
    }
    public function getCart(){
        $user= User::find(Auth::guard('user')->user()->id);
        $cart=null;
        if($user->cart){
            $cart=CartItems::where('cart_id',$user->cart->id)->with('product','product.nextGenImages')->get();
            $price=$cart->pluck('product')->sum('PromotionPrice');
        }

        return Response::json(['cart'=>$cart,'total_price'=>$price]);
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
            'product_id'=>'required|array|min:1',
            'product_id.*'=>'exists:products,id',
            'quantity'=>['required','array','min:1',new ArraySize($request->input('product_id'))],
            'quantity.*'=>'required|numeric|min:1'
        ]);

        $user= User::find(Auth::guard('user')->user()->id);

        $order=Order::create([
            'user_id'=>$user->id,
            'status'=>'pending',
        ]);
        $productIds=$request->input('product_id');
        foreach ($productIds as $key=>$productId){
            $orderItem=OrderItem::create([
                'order_id'=>$order->id,
                'product_id'=>$productId,
                'quantity'=>$request->input('quantity')[$key],
            ]);
        }
        return Response::json(['message'=>'Order sent.']);
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

}

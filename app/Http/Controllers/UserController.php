<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserLogin;
use App\Http\Requests\UserRegister;
use App\Models\BillingAddress;
use App\Models\Category;
use App\Models\Product;
use App\Models\ShippingAddress;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
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
        if(!empty($category_name)){
            if($where==''){
                $where.=" CategoryId = $category_name ";
            }else{
                $where.=" and CategoryId = $category_name ";
            }
            $b=1;
        }
//        if sub category
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
//          if  product
        if(!empty($product_name)){
//            $product_name=str_replace('"','\"',$product_name);
            if($where=='') {
                $where.=" Name like '%$product_name%' ";
            }else{
                $where.=" and Name like' %$product_name%' ";
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
                    ->with('measurements'
                        ,'materials'
                        ,'additionalFields'
                        ,'relatedProducts'
                        ,'relatedProducts.relatedProduct'
                        ,'components'
                        ,'nextGenImages'
                        ,'category'
                        ,'subCategory'
                        ,'piece'
                        ,'collection'
                        ,'style'
                        ,'productLine'
                        ,'group'
                        ,'inventory'
                        ,'productInfo'
                        ,'productInfo.highlights'
                        ,'productInfo.bullets'
                        ,'productInfo.features'
                    )
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
                    ->with('measurements'
                        , 'materials'
                        , 'additionalFields'
                        , 'relatedProducts'
                        , 'components'
                        , 'nextGenImages'
                        , 'category'
                        , 'subCategory'
                        , 'piece'
                        , 'collection'
                        , 'style'
                        , 'productLine'
                        , 'group'
                        , 'inventory'
                        ,'productInfo'
                        ,'productInfo.highlights'
                        ,'productInfo.bullets'
                        ,'productInfo.features'
                    )
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
                    ->with('measurements'
                        ,'materials'
                        ,'additionalFields'
                        ,'relatedProducts'
                        ,'relatedProducts.relatedProduct'
                        ,'components'
                        ,'nextGenImages'
                        ,'category'
                        ,'subCategory'
                        ,'piece'
                        ,'collection'
                        ,'style'
                        ,'productLine'
                        ,'group'
                        ,'inventory'
                        ,'productInfo'
                        ,'productInfo.highlights'
                        ,'productInfo.bullets'
                        ,'productInfo.features'
                    )
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
                    ->with('measurements'
                        ,'materials'
                        ,'additionalFields'
                        ,'relatedProducts'
                        ,'relatedProducts.relatedProduct'
                        ,'components'
                        ,'nextGenImages'
                        ,'category'
                        ,'subCategory'
                        ,'piece'
                        ,'collection'
                        ,'style'
                        ,'productLine'
                        ,'group'
                        ,'inventory'
                        ,'productInfo'
                        ,'productInfo.highlights'
                        ,'productInfo.bullets'
                        ,'productInfo.features'
                    )
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
                ->with('measurements'
                    ,'materials'
                    ,'additionalFields'
                    ,'relatedProducts'
                    ,'relatedProducts.relatedProduct'
                    ,'components'
                    ,'nextGenImages'
                    ,'category'
                    ,'subCategory'
                    ,'piece'
                    ,'collection'
                    ,'style'
                    ,'productLine'
                    ,'group'
                    ,'inventory'
                    ,'productInfo'
                    ,'productInfo.highlights'
                    ,'productInfo.bullets'
                    ,'productInfo.features'
                )
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
                ->with('measurements'
                    ,'materials'
                    ,'additionalFields'
                    ,'relatedProducts'
                    ,'relatedProducts.relatedProduct'
                    ,'components'
                    ,'nextGenImages'
                    ,'category'
                    ,'subCategory'
                    ,'piece'
                    ,'collection'
                    ,'style'
                    ,'productLine'
                    ,'group'
                    ,'inventory'
                    ,'productInfo'
                    ,'productInfo.highlights'
                    ,'productInfo.bullets'
                    ,'productInfo.features'
                )
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
                ->with(
                    'measurements'
                    ,'materials'
                    ,'additionalFields'
                    ,'relatedProducts'
                    ,'relatedProducts.relatedProduct'
                    ,'components'
                    ,'nextGenImages'
                    ,'category'
                    ,'subCategory'
                    ,'piece'
                    ,'collection'
                    ,'style'
                    ,'productLine'
                    ,'group'
                    ,'inventory'
                    ,'productInfo'
                    ,'productInfo.highlights'
                    ,'productInfo.bullets'
                    ,'productInfo.features'
                )
                ->get();
            $count=Product::
                whereRaw($where)->count();
        }
        else{
            $products=Product::
                offset($page)->limit($limit)
                ->orderBy($sort[0],$sort[1])
                ->with('measurements'
                    ,'materials'
                    ,'additionalFields'
                    ,'relatedProducts'
                    ,'relatedProducts.relatedProduct'
                    ,'components'
                    ,'nextGenImages'
                    ,'category'
                    ,'subCategory'
                    ,'piece'
                    ,'collection'
                    ,'style'
                    ,'productLine'
                    ,'group'
                    ,'inventory'
                    ,'productInfo'
                    ,'productInfo.highlights'
                    ,'productInfo.bullets'
                    ,'productInfo.features'
                )
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
        return Response::json([
            'products'=>$products,
            'total_number'=>$count,
            'filtered'=>$products->count()]);
    }
}

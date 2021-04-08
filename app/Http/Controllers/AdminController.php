<?php

namespace App\Http\Controllers;

use App\Models\AdditionalField;
use App\Models\Admin;
use App\Models\ApiKey;
use App\Models\Banner;
use App\Models\Bullet;
use App\Models\Category;
use App\Models\CollectionModel;
use App\Models\Component;
use App\Models\Coupon;
use App\Models\Feature;
use App\Models\Footer;
use App\Models\Group;
use App\Models\Header;
use App\Models\Highlight;
use App\Models\Hour;
use App\Models\InventoryEta;
use App\Models\Material;
use App\Models\Measurement;
use App\Models\NextGenImage;
use App\Models\Order;
use App\Models\PasswordReset;
use App\Models\Permission;
use App\Models\Piece;
use App\Models\Pricing;
use App\Models\PricingException;
use App\Models\PricingExceptionList;
use App\Models\Product;
use App\Models\ProductInfo;
use App\Models\ProductLine;
use App\Models\ProductPrice;
use App\Models\RelatedProductList;
use App\Models\Social;
use App\Models\Style;
use App\Models\SubCategory;
use App\Models\Testimonial;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseInventory;
use App\Models\WebsiteSettings;
use App\Rules\AlphaSpace;
use App\Rules\PasswordValidate;
use App\Rules\Phone;
use App\Rules\Unique;
use App\Rules\Zip;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Intervention\Image\ImageManagerStatic as Image;
class AdminController extends Controller
{
    public function __construct()
    {
        ini_set('max_execution_time', 600000);
    }

    public function login(Request $request)
    {
        $rules = [
            'email' => 'required|email',
            'password' => 'required'
        ];
        $validator = Validator::make($request->only('email', 'password'), $rules);
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors(), 'old_data' => $validator->valid()], 422);
        }
        $admin = Admin::where('email', $request->input('email'))->with('permissions')->first();
        if ($admin == null) {
            return Response::json(['message' => 'User not found.'], 422);
        }
        if ($admin->email == strtolower($request->input('email'))) {
            if (Hash::check($request->input('password'), $admin->password)) {
//                Auth::guard('admin')->setUser($admin);
                if ($admin->sub_admin == 1) {
                    $permissions = $admin->permissions;
                    if (!empty($permissions)) {
                        foreach ($permissions as $permission) {
                            $perm[] = $permission->slug;
                        }
                    }
                }
                if ($admin->super_admin == 1) {
                    $perm[] = '*';
                }
                $token = $admin->createToken($request->email, $perm)->accessToken;

                return Response::json([
                    'message' => 'Sign in successful',
                    'user' => $admin,
                    'token' => $token
                ],
                    200);
            }

        }
        return Response::json(['message' => 'Username or password incorrect.'], 422);
    }

    public function resetPassword(Request $request)
    {
        $rules = [
            'email' => 'required|email',
            'url' => 'required'
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors()], 422);
        } else {
            $token = sha1(time() . uniqid() . $request->email);
            $code = Str::random(6);
            PasswordReset::where('email', $request->input('email'))->delete();
            if (Admin::where('email', $request->input('email'))->first()) {
                try {
                    $pr = new PasswordReset([
                        'email' => strtolower($request->email),
                        'token' => $token,
                        'code' => $code
                    ]);
                    $pr->save();
                } catch (\Exception $e) {
                    $check = false;
                    while (!$check) {
                        $code = Str::random(8);
                        $pr = new PasswordReset([
                            'email' => strtolower($request->email),
                            'token' => $token,
                            'code' => $code
                        ]);
                        $check = $pr->save();
                    }
                }
                MailController::sendAdminForgotPasswordMail($pr->email, $pr->token, $pr->code, $request->url,);
                return Response::json(['message' => "Password reset link sent on your mail."], 200);
            }
            return Response::json(['message' => 'User does not exist.'], 422);
        }
    }

    public function verifyForgotPasswordToken(Request $request, $token = null)
    {
        $passwordReset = null;
        if ($token != null) {
            $passwordReset = PasswordReset::where('token', $token)->orderBy('created_at', 'desc')->first();
            if ($passwordReset == null) {
                if ($request->input('code') != null) {
                    $passwordReset = PasswordReset::where('code', $request->input('code'))->orderBy('created_at', 'desc')->first();
                }
            }
        }

        if ($passwordReset == null) {
            return Response::json(['message' => 'Your link is expired try to get another one.'], 422);
        } else {
            $now = Carbon::now();
            $diff = $now->diffInMinutes($passwordReset->created_at);
            if ($diff < env('PASSWORD_EXPIRE')) {

                return Response::json(['message' => true], 200);
            } else {
                return Response::json(['message' => 'Your link is expired try to get another one.'], 422);

            }
        }
    }

    public function verifyCode(Request $request)
    {
        $rules = [
            'code' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors()], 422);
        } else {
            $passwordReset = PasswordReset::where('code', 'like', $request->input('code'))->orderBy('created_at', 'desc')->first();
            if ($passwordReset != null) {
                $admin = Admin::where('email', $passwordReset->email)->first();
//                $admin->password=Hash::make($request->password);
//                PasswordReset::where('email',$admin->email)->delete();
                $now = Carbon::now();
                $diff = $now->diffInMinutes($passwordReset->created_at);
                if ($diff < env('PASSWORD_EXPIRE')) {

                    return Response::json(['message' => 'Code Verified.'], 200);

                } else {
                    return Response::json(['message' => 'Your code is expired try to get another one.'], 422);

                }
            } else {
                return Response::json(['message' => 'Your code is expired try to get another one.'], 422);

            }
        }

    }
//    public function verifyCodeUpdate(Request $request,$email){
//        $rules=[
//            'code'=>'required',
//            'password'=>'required',
//            'confirm_password'=>'same:password'
//        ];
//        $validator=Validator::make($request->all(),$rules) ;
//        if ($validator->fails()) {
//            return Response::json(['errors'=>$validator->errors()],422);
//        }
//        else{
//            $passwordReset=PasswordReset::where('code','like',$request->input('code'))->where('email','like',$email)->orderBy('created_at','desc')->first();
//            if($passwordReset!=null){
//                $admin=Admin::where('email',$passwordReset->email)->first();
//                $admin->password=Hash::make($request->password);
//                PasswordReset::where('email',$admin->email)->delete();
//                return Response::json(['message'=>'Password updated.'],200);
//            }else{
//                return Response::json(['message'=>'Invalid Code.'],422);
//
//            }
//        }
//
//    }
    public function resetPasswordUpdate(Request $request)
    {
        $rules = [
            'code' => 'required',
            'password' => ['required', new PasswordValidate],
            'confirm_password' => 'same:password'
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors()], 422);
        } else {
            $passwordReset = PasswordReset::where('token', 'like', $request->input('code'))->orderBy('created_at', 'desc')->first();
            if ($passwordReset == null) {
                $passwordReset = PasswordReset::where('code', 'like', $request->input('code'))->orderBy('created_at', 'desc')->first();
                if ($passwordReset == null) {
                    return Response::json(['message' => 'Token Or Code Invalid.'], 422);
                }
            }
            if ($passwordReset != null) {
                $now = Carbon::now();
                $diff = $now->diffInMinutes($passwordReset->created_at);
                if ($diff < env('PASSWORD_EXPIRE')) {
                    $admin = Admin::where('email', $passwordReset->email)->first();
                    if (Hash::check($request->input('password'), $admin->password)) {
                        return Response::json(['message' => 'Please use different password you already used this password.'], 422);
                    }
                    $admin->password = Hash::make($request->input('password'));
                    $admin->save();
                    PasswordReset::where('email', $admin->email)->delete();
                    return Response::json(['message' => 'Password updated.'], 200);

                } else {
                    return Response::json(['message' => 'Your code is expired try to get another one.'], 422);
                }
            } else {
                return Response::json(['message' => 'Token Or Code Invalid.'], 422);
            }
        }
    }

//    Category
    public function storeCategory(Request $request)
    {

        $rules = [
            'name' => ['required', 'unique:categories,CategoryName', new AlphaSpace()],
            'image' => 'required|mimes:jpeg,jpg,png'
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors()], 422);
        } else {
            $image = $request->file('image');
            $imageName = time() . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('uploads/category/'), $imageName);
            $slug = Str::slug($request->input('name'), '-');

            $category = Category::create([
                'CategoryName' => $request->input('name'),
                'Image' => 'uploads/category/' . $imageName,
                'slug' => $slug
            ]);
            return Response::json(['message' => 'Category Added.'], 200);
        }
    }

    public function updateCategory(Request $request, $id)
    {

        $rules = [
            'name' => ['required', new AlphaSpace()],
            'image' => 'mimes:jpeg,jpg,png'
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors()], 422);
        } else {
            $category = Category::find($id);
            if ($category != null) {
                if ($request->hasFile('image')) {
                    $image = $request->file('image');
                    $imageName = time() . uniqid() . '.' . $image->getClientOriginalExtension();
                    if (!empty($category->Image)) {
                        if (file_exists(public_path($category->Image))) {
                            unlink(public_path($category->Image));
                        }
                    }

                    $image->move(public_path('uploads/category'), $imageName);
                    $category->Image = 'uploads/category/' . $imageName;
                }
                $cat = Category::where('id', '!=', $category->id)->where('CategoryName', 'like', $request->name)->first();

                if (empty($cat)) {
                    $slug = Str::slug($request->input('name'), '-');

                    $category->CategoryName = $request->name;
                    $category->slug = $slug;
                }
                $category->save();

                return Response::json(['message' => 'Category Updated.'], 200);
            } else {
                return Response::json(['message' => 'Category Not Found.'], 404);
            }

        }

    }

    public function deleteCategory(Request $request, $id)
    {
        $category = Category::find($id);
        if ($category != null) {

            try {
                if ($category->delete()) {
                    if (file_exists(public_path($category->Image))) {
                        unlink(public_path($category->Image));
                    }
                }
                return Response::json(['message' => 'Category Deleted.'], 200);
            } catch (\Exception $exception) {
                return Response::json(['message' => "Category cannot be deleted."], 422);
            }
        } else {
            return Response::json(['message' => 'Category Not Found.'], 404);
        }
    }

    public function getCategories(Request $request)
    {
        $page = 0;
        $limit = Category::all()->count();
        $count = Category::all()->count();
        if (!empty($request->input('limit'))) {
            $limit = $request->input('limit');
        }
        if (!empty($request->input('page'))) {
            $page = ($request->input('page') - 1) * $limit;
        }
        if (empty($request->input('category_name'))) {
            $categories = Category::withCount('subCategories', 'products')->offset($page)->limit($limit)->get();
        } else {
            $categories = Category::where('CategoryName', 'like', '%' . $request->input('category_name') . '%')->withCount('subCategories', 'products')->offset($page)->limit($limit)->get();
            $count = Category::where('CategoryName', 'like', '%' . $request->input('category_name') . '%')->count();
        }


        return Response::json(['categories' => $categories, 'total_number' => $count, 'filtered' => $categories->count()], 200);
    }

    public function getCategoriesByCoasterName(Request $request)
    {

        if ($request->input('type') == 0) {
            $categories = Category::select('id', 'CategoryName')->whereNull('CategoryCode')->orderBy('id', 'asc')->get();

        } else {
            $categories = Category::select('id', 'CategoryName')->whereNotNull('CategoryCode')->orderBy('id', 'asc')->get();
        }

        return Response::json(['categories' => $categories]);

    }

    public function getSubCategoriesByCoasterName(Request $request)
    {

        if ($request->input('type') == 0) {
            $sub_categories = SubCategory::select('id', 'SubCategoryName')->whereNull('SubCategoryCode')->orderBy('id', 'asc')->get();


        } else {
            $sub_categories = SubCategory::select('id', 'SubCategoryName')->whereNotNull('SubCategoryCode')->orderBy('id', 'asc')->get();
        }


        return Response::json(['sub_categories' => $sub_categories]);

    }

    public function getWarehouseName(Request $request)
    {

        if ($request->input('type') == 0) {
            $warehouse = Warehouse::select('id', 'Name')->whereNull('WarehouseCode')->orderBy('id', 'asc')->get();

        } else {
            $warehouse = Warehouse::select('id', 'Name')->whereNotNull('WarehouseCode')->orderBy('id', 'asc')->get();
        }


        return Response::json(['warehouses' => $warehouse]);

    }

    public function getCategoriesByCoaster(Request $request)
    {

        $page = 0;
        $limit = Category::all()->count();
        $count = Category::all()->count();
        if (!empty($request->input('limit'))) {
            $limit = $request->input('limit');
        }
        if (!empty($request->input('page'))) {
            $page = ($request->input('page') - 1) * $limit;
        }
        if (empty($request->input('category_name'))) {
            $categories = Category::whereNotNull('CategoryCode')->with('subCategories', 'subCategories.pieces')->withCount('subCategories', 'products')->offset($page)->limit($limit)->orderBy('id', 'asc')->get();
        } else {
            $categories = Category::whereNotNull('CategoryCode')->where('CategoryName', 'like', '%' . $request->input('category_name') . '%')->with('subCategories', 'subCategories.pieces')->withCount('subCategories', 'products')->offset($page)->limit($limit)->orderBy('id', 'asc')->get();
            $count = Category::where('CategoryName', 'like', '%' . $request->input('category_name') . '%')->count();
        }
        return Response::json(['categories' => $categories, 'total_number' => $count, 'filtered' => $categories->count()], 200);

    }

    //   Sub Category
    public function storeSubCategory(Request $request)
    {

        $rules = [
            'category_id' => 'required|exists:categories,id',
            'name' => ['required', 'unique:sub_categories,SubCategoryName', new AlphaSpace()],
            'image' => 'required|mimes:jpeg,jpg,png'
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors()], 422);
        } else {
            $image = $request->file('image');
            $imageName = time() . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('uploads/subcategory'), $imageName);
            $slug = Str::slug($request->input('name'), '-');

            $category = SubCategory::create([
                'CategoryId' => $request->category_id,
                'SubCategoryName' => $request->name,
                'Image' => 'uploads/subcategory/' . $imageName,
                'slug' => $slug
            ]);
            return Response::json(['message' => 'Sub Category Added.'], 200);

        }

    }

    public function updateSubCategory(Request $request, $id)
    {

        $rules = [
            'category_id' => 'required|exists:categories,id',
            'name' => ['required', new AlphaSpace()],
            'image' => 'mimes:jpeg,jpg,png'
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors()], 422);
        } else {
            $subcategory = SubCategory::find($id);
            if ($subcategory != null) {
                if ($request->hasFile('image')) {
                    $image = $request->file('image');
                    $imageName = time() . uniqid() . '.' . $image->getClientOriginalExtension();
                    if (!empty($subcategory->Image)) {
                        if (file_exists(public_path($subcategory->Image))) {
                            unlink(public_path($subcategory->Image));
                        }
                    }

                    $image->move(public_path('uploads/subcategory'), $imageName);
                    $subcategory->Image = 'uploads/subcategory/' . $imageName;
                }
                $subcat = SubCategory::where('id', '!=', $subcategory->id)->where('SubCategoryName', 'like', $request->name)->first();

                $subcategory->CategoryId = $request->category_id;
                $slug = Str::slug($request->input('name'), '-');

                if (empty($subcat)) {
                    $subcategory->SubCategoryName = $request->name;
                    $subcategory->slug = $slug;

                }
                $subcategory->save();
                return Response::json(['message' => 'Sub Category Updated.'], 200);
            } else {
                return Response::json(['message' => 'Sub Category Not Found.'], 404);
            }
        }
    }

    public function deleteSubCategory(Request $request, $id)
    {

        $subcategory = SubCategory::find($id);
        if ($subcategory != null) {


            try {

                if ($subcategory->delete()) {

                    if (file_exists(public_path($subcategory->Image))) {
                        unlink(public_path($subcategory->Image));
                    }
                }
                return Response::json(['message' => 'Sub Category Deleted.'], 200);

            } catch (\Exception $exception) {
                return Response::json(['message' => 'Some Error has Occurred'], 422);
            }
        } else {
            return Response::json(['message' => 'Sub Category Not Found.'], 404);
        }
    }

    public function getSubCategories(Request $request)
    {
        $page = 0;
        $sub = $request->input('subcategory_name');
        $cat = $request->input('category_id');
        $count = SubCategory::all()->count();
        $limit = $count;
        if (!empty($request->input('limit'))) {
            $limit = $request->input('limit');
        }
        if (!empty($request->input('page'))) {
            $page = ($request->input('page') - 1) * $request->input('limit');
        }


        if (empty($sub) && empty($cat)) {
            $subcategories = SubCategory::with('Category')->withCount('products')->offset($page)->limit($limit)->get();
        }
        if (!empty($sub) && empty($cat)) {
            $subcategories = SubCategory::where('SubCategoryName', 'like', "%$sub%")->with('Category')->withCount('products')->offset($page)->limit($limit)->get();
            $count = SubCategory::where('SubCategoryName', 'like', "%$sub%")->count();
        }
        if (empty($sub) && !empty($cat)) {
            $subcategories = SubCategory::whereHas('Category', function ($query) use ($cat) {
                $query->where('id', 'like', $cat);
            })->with('Category')->withCount('products')->offset($page)->limit($limit)->get();

            $count = SubCategory::whereHas('Category', function ($query) use ($cat) {
                $query->where('id', 'like', $cat);
            })->with('Category')->withCount('products')->count();
        }
        if (!empty($sub) && !empty($cat)) {
            $subcategories = SubCategory::where('SubCategoryName', 'like', "%$sub%")->whereHas('Category', function ($query) use ($cat) {
                $query->where('id', 'like', $cat);
            })->with('Category')->withCount('products')->offset($page)->limit($limit)->get();

            $count = SubCategory::where('SubCategoryName', 'like', "%$sub%")->whereHas('Category', function ($query) use ($cat) {
                $query->where('id', 'like', $cat);
            })->with('Category')->withCount('products')->count();
        }

        return Response::json(['sub_categories' => $subcategories, 'total_number' => $count, 'filtered' => $subcategories->count()], 200);
    }

    public function getSubCategoriesByCoaster(Request $request)
    {
        $page = 0;
        $sub = $request->input('subcategory_name');
        $cat = $request->input('category_id');
        $count = SubCategory::whereNotNull('SubCategoryCode')->count();
        $limit = $count;
        if (!empty($request->input('limit'))) {
            $limit = $request->input('limit');
        }
        if (!empty($request->input('page'))) {
            $page = ($request->input('page') - 1) * $request->input('limit');
        }


        if (empty($sub) && empty($cat)) {
            $subcategories = SubCategory::whereNotNull('SubCategoryCode')->with('Category')->withCount('products')->offset($page)->limit($limit)->get();
        }
        if (!empty($sub) && empty($cat)) {
            $subcategories = SubCategory::whereNotNull('SubCategoryCode')->where('SubCategoryName', 'like', "%$sub%")->with('Category')->withCount('products')->offset($page)->limit($limit)->get();
            $count = SubCategory::whereNotNull('SubCategoryCode')->where('SubCategoryName', 'like', "%$sub%")->count();
        }
        if (empty($sub) && !empty($cat)) {
            $subcategories = SubCategory::whereNotNull('SubCategoryCode')->whereHas('Category', function ($query) use ($cat) {
                $query->where('id', 'like', $cat);
            })->with('Category')->withCount('products')->offset($page)->limit($limit)->get();

            $count = SubCategory::whereNotNull('SubCategoryCode')->whereHas('Category', function ($query) use ($cat) {
                $query->where('id', 'like', $cat);
            })->with('Category')->withCount('products')->count();
        }
        if (!empty($sub) && !empty($cat)) {
            $subcategories = SubCategory::whereNotNull('SubCategoryCode')->where('SubCategoryName', 'like', "%$sub%")->whereHas('Category', function ($query) use ($cat) {
                $query->where('id', 'like', $cat);
            })->with('Category')->withCount('products')->offset($page)->limit($limit)->get();

            $count = SubCategory::whereNotNull('SubCategoryCode')->where('SubCategoryName', 'like', "%$sub%")->whereHas('Category', function ($query) use ($cat) {
                $query->where('id', 'like', $cat);
            })->with('Category')->withCount('products')->count();
        }

        return Response::json(['sub_categories' => $subcategories, 'total_number' => $count, 'filtered' => $subcategories->count()], 200);
    }
    public function newTest(){

    }
//    API CALLS from http://api.coasteramer.com/
//Start from here
    public function storeProductApiData()
    {
        $this->storeCategoryApiData();
        $this->storeStyleApiData();
        $this->storeCollectionApiData();
        $this->storeProductLineApiData();
        $this->storeGroupApiData();
        $this->storeProductInfoApiData();
        $products = Http::withHeaders([
            'keycode' => env('API_COASTERAMER_KEY'),
            'Accept' => 'application/json'
        ])->get('http://api.coasteramer.com/api/product/GetProductList');

//        $products=file_get_contents(public_path('response.json'));
        $productsDecode = json_decode($products);
        $i = 0;
        foreach ($productsDecode as $product) {
//            if($i<10){
//                $i++;
            $color = null;
            if (property_exists($product, 'FabricColor')) {
                $color = $product->FabricColor;
            }
            $cleaningCode = null;
            if (property_exists($product, 'FabricCleaningCode')) {
                $cleaningCode = $product->FabricCleaningCode;
            }
            $CollectionId = null;
            if (property_exists($product, 'CollectionCode')) {
                $CollectionId = CollectionModel::where('CollectionCode', $product->CollectionCode)->first()->id;
            }
            $GroupNumber = null;
            if (property_exists($product, 'GroupNumber')) {
                $GroupNumber = Group::where('GroupNumber', $product->GroupNumber)->first()->id;
            }
            $CatalogYear = null;
            if (property_exists($product, 'CatalogYear')) {
                $CatalogYear = $product->CatalogYear;
            }
            $CountryOfOrigin = null;
            if (property_exists($product, 'CountryOfOrigin')) {
                $CountryOfOrigin = $product->CountryOfOrigin;
            }
            $Description = null;
            if (property_exists($product, 'Description')) {
                $Description = $product->Description;
            }
            $StyleCode = null;
            if (property_exists($product, 'StyleCode')) {
                $StyleCode = Style::where('StyleCode', $product->StyleCode)->first()->id;
            }
            $CategoryId = null;
            if (property_exists($product, 'CategoryCode')) {
                $CategoryId = Category::where('CategoryCode', $product->CategoryCode)->first()->id;
            }
            $ProductLine = null;
            if (property_exists($product, 'ProductLineCode')) {
                if (ProductLine::where('ProductLineCode', $product->ProductLineCode)->first() != null) {
                    $ProductLine = ProductLine::where('ProductLineCode', $product->ProductLineCode)->first()->id;
                }
            }
            $SubCategoryCode = null;
            if (property_exists($product, 'SubcategoryCode')) {
                $SubCategoryCode = SubCategory::where('SubCategoryCode', $product->SubcategoryCode)->first()->id;
            }
            $PieceCode = null;
            if (property_exists($product, 'PieceCode')) {
                $PieceCode = Piece::where('PieceCode', $product->PieceCode)->first()->id;
            }
            $FinishColor = null;
            if (property_exists($product, 'FinishColor')) {
                $FinishColor = $product->FinishColor;
            }
            $Upc = null;
            if (property_exists($product, 'Upc')) {
                $Upc = $product->Upc;
            }
            $CatalogPage = null;
            if (property_exists($product, 'CatalogPage')) {
                $CatalogPage = $product->CatalogPage;
            }
            $productInfo = ProductInfo::where('ProductNumber', 'like', $product->ProductNumber)->first();
            try {
                $productCheck = Product::where('ProductNumber', $product->ProductNumber)->first();
                if ($productCheck != null) {
                    $productCheck->ProductNumber = $product->ProductNumber;
                    $productCheck->Name = $product->Name;
                    $productCheck->Description = $Description;
                    $productCheck->FabricColor = $color;
                    $productCheck->FinishColor = $FinishColor;
                    $productCheck->BoxWeight = $product->BoxWeight;
                    $productCheck->Cubes = $product->Cubes;
                    $productCheck->TypeOfPackaging = $product->TypeOfPackaging;
                    $productCheck->CatalogYear = $CatalogYear;
                    $productCheck->SubBrand = $product->SubBrand;
                    $productCheck->Upc = $Upc;
                    $productCheck->CountryOfOrigin = $CountryOfOrigin;
                    $productCheck->DesignerCollection = $product->DesignerCollection;
                    $productCheck->AssemblyRequired = $product->AssemblyRequired;
                    $productCheck->IsDiscontinued = $product->IsDiscontinued;
                    $productCheck->NumImages = $product->NumImages;
                    $productCheck->NumBoxes = $product->NumBoxes;
                    $productCheck->PackQty = $product->PackQty;
                    $productCheck->CatalogPage = $CatalogPage;
                    $productCheck->FabricCleaningCode = $cleaningCode;
                    $productCheck->NumHDImages = $product->NumHDImages;
                    $productCheck->NumNextGenImages = $product->NumNextGenImages;
                    $productCheck->StyleId = $StyleCode;
                    $productCheck->CollectionId = $CollectionId;
                    $productCheck->ProductLineId = $ProductLine;
                    $productCheck->GroupId = $GroupNumber;
                    $productCheck->CategoryId = $CategoryId;
                    $productCheck->SubCategoryId = $SubCategoryCode;
                    $productCheck->PieceId = $PieceCode;
                    $productCheck->BoxLength = $product->BoxSize->Length;
                    $productCheck->BoxWidth = $product->BoxSize->Width;
                    $productCheck->BoxHeight = $product->BoxSize->Height;

                    Measurement::where('ProductId',$productCheck)->delete();
                    foreach ($product->MeasurementList as $measurementList) {
                        $Length = null;
                        $Width = null;
                        $Height = null;
                        $SeatHeight = null;
                        $SeatWidth = null;
                        $SeatDepth = null;
                        $Weight = null;
                        $Depth = null;
                        $DeskClearance = null;
                        $Diameter = null;
                        if (property_exists($measurementList, 'Length')) {
                            $Length = $measurementList->Length;
                        }
                        if (property_exists($measurementList, 'Width')) {
                            $Width = $measurementList->Width;
                        }
                        if (property_exists($measurementList, 'Depth')) {
                            $Depth = $measurementList->Depth;
                        }
                        if (property_exists($measurementList, 'Height')) {
                            $Height = $measurementList->Height;
                        }
                        if (property_exists($measurementList, 'SeatHeight')) {
                            $SeatHeight = $measurementList->SeatHeight;
                        }
                        if (property_exists($measurementList, 'SeatWidth')) {
                            $SeatWidth = $measurementList->SeatWidth;
                        }
                        if (property_exists($measurementList, 'SeatDepth')) {
                            $SeatDepth = $measurementList->SeatDepth;
                        }
                        if (property_exists($measurementList, 'Weight')) {
                            $Weight = $measurementList->Weight;
                        }
                        if (property_exists($measurementList, 'DeskClearance')) {
                            $DeskClearance = $measurementList->DeskClearance;
                        }
                        if (property_exists($measurementList, 'Diameter')) {
                            $Diameter = $measurementList->Diameter;
                        }

                        Measurement::create([
                            'PieceName' => $measurementList->PieceName,
                            'Length' => $Length,
                            'Width' => $Width,
                            'Depth' => $Depth,
                            'Height' => $Height,
                            'SeatHeight' => $SeatHeight,
                            'SeatWidth' => $SeatWidth,
                            'SeatDepth' => $SeatDepth,
                            'Weight' => $Weight,
                            'ProductId' => $productCheck->id,
                            'DeskClearance' => $DeskClearance,
                            'Diameter' => $Diameter
                        ]);
                    }



                    Material::where('ProductId',$productCheck)->delete();

                    foreach ($product->MaterialList as $materialList) {
                        Material::create([
                            'Field' => $materialList->Field,
                            'Value' => $materialList->Value,
                            'ProductId' => $productCheck->id
                        ]);
                    }


                    AdditionalField::where('ProductId',$productCheck)->delete();

                    foreach ($product->AdditionalFieldList as $additionalFieldList) {
                        AdditionalField::create([
                            'Field' => $additionalFieldList->Field,
                            'Value' => $additionalFieldList->Value,
                            'ProductId' => $productCheck->id
                        ]);
                    }


                    RelatedProductList::where('ProductId',$productCheck)->delete();

                    foreach ($product->RelatedProductList as $relatedProductList) {
                        RelatedProductList::create([
                            'ProductNumber' => $relatedProductList,
                            'ProductId' => $productCheck->id
                        ]);
                    }


                    Component::where('ProductId',$productCheck)->delete();

                    if (property_exists($product, 'Components')) {
                        foreach ($product->Components as $component) {

                            Component::create([
                                'ProductNumber' => $product->ProductNumber,
                                'Name' => $component->Name,
                                'BoxWeight' => $component->BoxWeight,
                                'Cubes' => $component->Cubes,
                                'Qty' => $component->Qty,
                                'BoxLength' => $component->BoxSize->Length,
                                'BoxWidth' => $component->BoxSize->Width,
                                'BoxHeight' => $component->BoxSize->Height,
                                'ProductId' => $productCheck->id
                            ]);
                        }
                    }
//                    if ($product->NumNextGenImages != $productCheck->NumNextGenImages) {
                        foreach ($productCheck->nextGenImages as $img) {
//                            $tImg=explode('/',$img->name);
//                            $thumbnailImage=$tImg[0].'/thumbnail/'.$tImg[2];
                            if (file_exists(public_path($img->name))) {
                                unlink(public_path($img->name));
                            }
                            if(file_exists(public_path('thumbnail/'.$img->name))){
                                unlink(public_path('thumbnail/'.$img->name));
                            }
                            $img->delete();
                        }
                        $listImage = explode(',', $product->ListNextGenImages);
                        foreach ($listImage as $image) {
                            try {
                                if(!empty(str_replace(' ', '', $image))){
                                    $img = Image::make('https://assets.coastercenter.com/nextgenimages/' . str_replace(' ', '', $image));
                                    $image_resize = Image::make('https://assets.coastercenter.com/nextgenimages/' . str_replace(' ', '', $image));
                                    $image_resize->resize(300, null, function ($constraint) {
                                        $constraint->aspectRatio();
                                    });
                                    $extension = explode('.', $image);
                                    $tempName = explode('/', $image);
                                    $name = time() . uniqid() . $tempName[0] . '.' . $extension[1];
                                    $img->save(public_path('uploads/product/' . $name));
                                    $image_resize->save(public_path('thumbnail/uploads/product/' . $name));
                                    NextGenImage::create([
                                        'Name' => 'uploads/product/' . $name,
                                        'ProductId' => $productCheck->id
                                    ]);
                                }


                            } catch (\Exception $ex) {
//                                return Response::json(['error' => [
//                                    'message' => $ex->getMessage(),
//                                    'line' => $ex->getLine()
//                                ]], 422);
                            }
                        }
//                    }
                    $productCheck->save();
                    $check = false;

                    while ($check == false) {
                        try {
                            $productCheck->slug = Str::slug($productCheck->Name, '-');
                            $check = $productCheck->save();
                        } catch (\Exception $exception) {

                            $productCheck->slug = Str::slug($productCheck->Name, '-') . '-' . time() . uniqid();
                            $check = $productCheck->save();
                        }
                    }
                    //                    return Response::json(['message'=>'Product Details Updated'],200);

                } else {

                    $p = Product::create([
                        'ProductNumber' => $product->ProductNumber,
                        'ProductInfoId' => $productInfo == null ? null : $productInfo->id,
                        'Name' => $product->Name,
                        'Description' => $Description,
                        'FabricColor' => $color,
                        'FinishColor' => $FinishColor,
                        'BoxWeight' => $product->BoxWeight,
                        'Cubes' => $product->Cubes,
                        'TypeOfPackaging' => $product->TypeOfPackaging,
                        'CatalogYear' => $CatalogYear,
                        'SubBrand' => $product->SubBrand,
                        'Upc' => $Upc,
                        'CountryOfOrigin' => $CountryOfOrigin,
                        'DesignerCollection' => $product->DesignerCollection,
                        'AssemblyRequired' => $product->AssemblyRequired,
                        'IsDiscontinued' => $product->IsDiscontinued,
                        'NumImages' => $product->NumImages,
                        'NumBoxes' => $product->NumBoxes,
                        'PackQty' => $product->PackQty,
                        'CatalogPage' => $CatalogPage,
                        'FabricCleaningCode' => $cleaningCode,
                        'NumHDImages' => $product->NumHDImages,
                        'NumNextGenImages' => $product->NumNextGenImages,
                        'StyleId' => $StyleCode,
                        'CollectionId' => $CollectionId,
                        'ProductLineId' => $ProductLine,
                        'GroupId' => $GroupNumber,
                        'CategoryId' => $CategoryId,
                        'SubCategoryId' => $SubCategoryCode,
                        'PieceId' => $PieceCode,
                        'BoxLength' => $product->BoxSize->Length,
                        'BoxWidth' => $product->BoxSize->Width,
                        'BoxHeight' => $product->BoxSize->Height
                    ]);
                    foreach ($product->MeasurementList as $measurementList) {
                        $Length = null;
                        $Width = null;
                        $Height = null;
                        $SeatHeight = null;
                        $SeatWidth = null;
                        $SeatDepth = null;
                        $Weight = null;
                        $Depth = null;
                        $DeskClearance = null;
                        $Diameter = null;
                        if (property_exists($measurementList, 'Length')) {
                            $Length = $measurementList->Length;
                        }
                        if (property_exists($measurementList, 'Width')) {
                            $Width = $measurementList->Width;
                        }
                        if (property_exists($measurementList, 'Depth')) {
                            $Depth = $measurementList->Depth;
                        }
                        if (property_exists($measurementList, 'Height')) {
                            $Height = $measurementList->Height;
                        }
                        if (property_exists($measurementList, 'SeatHeight')) {
                            $SeatHeight = $measurementList->SeatHeight;
                        }
                        if (property_exists($measurementList, 'SeatWidth')) {
                            $SeatWidth = $measurementList->SeatWidth;
                        }
                        if (property_exists($measurementList, 'SeatDepth')) {
                            $SeatDepth = $measurementList->SeatDepth;
                        }
                        if (property_exists($measurementList, 'Weight')) {
                            $Weight = $measurementList->Weight;
                        }
                        if (property_exists($measurementList, 'DeskClearance')) {
                            $DeskClearance = $measurementList->DeskClearance;
                        }
                        if (property_exists($measurementList, 'Diameter')) {
                            $Diameter = $measurementList->Diameter;
                        }

                        Measurement::create([
                            'PieceName' => $measurementList->PieceName,
                            'Length' => $Length,
                            'Width' => $Width,
                            'Depth' => $Depth,
                            'Height' => $Height,
                            'SeatHeight' => $SeatHeight,
                            'SeatWidth' => $SeatWidth,
                            'SeatDepth' => $SeatDepth,
                            'Weight' => $Weight,
                            'ProductId' => $p->id,
                            'DeskClearance' => $DeskClearance,
                            'Diameter' => $Diameter
                        ]);
                    }
                    foreach ($product->MaterialList as $materialList) {
                        Material::create([
                            'Field' => $materialList->Field,
                            'Value' => $materialList->Value,
                            'ProductId' => $p->id
                        ]);
                    }
                    foreach ($product->AdditionalFieldList as $additionalFieldList) {
                        AdditionalField::create([
                            'Field' => $additionalFieldList->Field,
                            'Value' => $additionalFieldList->Value,
                            'ProductId' => $p->id
                        ]);
                    }


                    foreach ($product->RelatedProductList as $relatedProductList) {
                        RelatedProductList::create([
                            'ProductNumber' => $relatedProductList,
                            'ProductId' => $p->id
                        ]);
                    }
                    if (property_exists($product, 'Components')) {
                        foreach ($product->Components as $component) {

                            Component::create([
                                'ProductNumber' => $product->ProductNumber,
                                'Name' => $component->Name,
                                'BoxWeight' => $component->BoxWeight,
                                'Cubes' => $component->Cubes,
                                'Qty' => $component->Qty,
                                'BoxLength' => $component->BoxSize->Length,
                                'BoxWidth' => $component->BoxSize->Width,
                                'BoxHeight' => $component->BoxSize->Height,
                                'ProductId' => $p->id
                            ]);
                        }
                    }
                    if ($product->NumNextGenImages != 0) {
                        $listImage = explode(',', $product->ListNextGenImages);
                        foreach ($listImage as $image) {
                            try {
                                if(!empty(str_replace(' ', '', $image))){
                                    $img = Image::make('https://assets.coastercenter.com/nextgenimages/' . str_replace(' ', '', $image));
                                    $extension = explode('.', $image);
                                    $tempName = explode('/', $image);
                                    $name = time() . uniqid() . $tempName[0] . '.' . $extension[1];
                                    $image_resize = Image::make('https://assets.coastercenter.com/nextgenimages/' . str_replace(' ', '', $image));
                                    $image_resize->resize(300, null, function ($constraint) {
                                        $constraint->aspectRatio();
                                    });
                                    $img->save(public_path('uploads/product/' . $name));
                                    $image_resize->save(public_path('thumbnail/uploads/product/' . $name));

                                    NextGenImage::create([
                                        'Name' => 'uploads/product/' . $name,
                                        'ProductId' => $p->id
                                    ]);
                                }


                            } catch (\Exception $ex) {

                            }
                        }
                    }
                    $p->New = 1;
                    $p->save();
                    $check = false;
                    while ($check == false) {
                        try {
                            $p->slug = Str::slug($p->Name, '-');
                            $check = $p->save();
                        } catch (\Exception $exception) {

                            $p->slug = Str::slug($p->Name, '-') . '-' . time() . uniqid();
                            $check = $p->save();
                        }
                    }
                }
            } catch (\Exception $ex) {
                return Response::json(['error' => [
                    'message' => $ex->getMessage(),
                    'line' => $ex->getLine()
                ]], 422);
            }
        }
        $relatedProducts = RelatedProductList::whereNull('RelatedProductId')->get();
        if ($relatedProducts) {
            foreach ($relatedProducts as $relatedProduct) {

                $product = Product::where('ProductNumber', 'like', $relatedProduct->ProductNumber)->first();
                if ($product) {
                    $relatedProduct->RelatedProductId = $product->id;
                    $relatedProduct->save();
                }

            }
        }
//    }
        $relatedProducts=RelatedProductList::whereNull('RelatedProductId')->get();
        if($relatedProducts){
            foreach ($relatedProducts as $relatedProduct){

                $product=Product::where('ProductNumber','like',$relatedProduct->ProductNumber)->first();
                if($product){
                    $relatedProduct->RelatedProductId=$product->id;
                    $relatedProduct->save();
                }

            }
        }
        $this->storeWareHouse();
        $this->storeWareHouseInventory();
        $this->storeProductPrice();
        return Response::json(['message' => "Product saved"], 200);
    }

    public function storeProductInfoApiData()
    {
        $productInfos = Http::withHeaders([
            'keycode' => env('API_COASTERAMER_KEY'),
            'Accept' => 'application/json'
        ])->get('http://api.coasteramer.com/api/product/GetProductInfo');
        $productInfosDecode = json_decode($productInfos);
        foreach ($productInfosDecode as $productInfo) {
            try {
                $p = ProductInfo::create([
                    'ProductNumber' => $productInfo->ProductNumber,
                    'ProductName' => $productInfo->ProductName,
                    'Description' => $productInfo->Description
                ]);
                foreach ($productInfo->Highlights as $highlight) {
                    if (!empty($highlight)) {
                        Highlight::create([
                            'Name' => $highlight,
                            'ProductInfoId' => $p->id
                        ]);
                    }

                }
                foreach ($productInfo->Bullets as $bullet) {
                    if (!empty($bullet)) {

                        Bullet::create([
                            'Name' => $bullet,
                            'ProductInfoId' => $p->id
                        ]);
                    }
                }
                foreach ($productInfo->Features as $feature) {
                    if (!empty($highlight)) {

                        Feature::create([
                            'Name' => $feature,
                            'ProductInfoId' => $p->id
                        ]);
                    }
                }
            } catch (\Exception $ex) {
                $p = ProductInfo::where('ProductNumber', 'like', $productInfo->ProductNumber)->first();
                $p->ProductNumber = $productInfo->ProductNumber;
                $p->ProductName = $productInfo->ProductName;
                $p->Description = $productInfo->Description;
                $p->save();
                foreach ($p->highlights as $h) {
                    $h->delete();
                }
                foreach ($p->bullets as $h) {
                    $h->delete();
                }
                foreach ($p->features as $h) {
                    $h->delete();
                }
                foreach ($productInfo->Highlights as $highlight) {
                    Highlight::create([
                        'Name' => $highlight,
                        'ProductInfoId' => $p->id
                    ]);
                }
                foreach ($productInfo->Bullets as $bullet) {
                    Bullet::create([
                        'Name' => $bullet,
                        'ProductInfoId' => $p->id
                    ]);
                }
                foreach ($productInfo->Features as $feature) {
                    Feature::create([
                        'Name' => $feature,
                        'ProductInfoId' => $p->id
                    ]);
                }
            }
        }
    }
    public function storeProductPrice()
    {
        $productPrice = Http::withHeaders([
            'keycode' => env('API_COASTERAMER_KEY'),
            'Accept' => 'application/json'
        ])->get('http://api.coasteramer.com/api/product/GetPriceList?customerNumber='.env("CUSTOMER_ID"));
        $productPriceDecode = json_decode($productPrice);
        foreach ($productPriceDecode as $price) {

            try {

                $priceCheck=Pricing::where('PriceCode','like',$price->PriceCode)->first();
                if(empty($priceCheck)) {
                    $p = Pricing::create([
                        'PriceCode' => $price->PriceCode,
                    ]);
                }else{
                    $p=$priceCheck;
                }
                    foreach ($price->PriceList as $priceList) {
                        if(empty(ProductPrice::where('ProductNumber','=',$priceList->ProductNumber)->first())) {
//                            return $priceList->ProductNumber;
                            $pd=Product::where('ProductNumber', '=', $priceList->ProductNumber)->first();
                            if ($pd) {

                                $temp=new ProductPrice([
                                    'ProductNumber' => $priceList->ProductNumber,
                                    'ProductId' => Product::where('ProductNumber', '=', $priceList->ProductNumber)->first()->id,
                                    'Price' => $priceList->Price,
                                    'MAP' => $priceList->MAP,
                                    'PriceId' => $p->id
                                ]);
                                if($temp->save()){
                                    $pd->SalePrice=$priceList->Price;
                                    $pd->save();
                                }
                            }
                        }
                        else{
                            $pd=Product::where('ProductNumber', '=', $priceList->ProductNumber)->first();

                            if ($pd) {

                                $pl = ProductPrice::where('ProductNumber', '=', $priceList->ProductNumber)->first();
//                                $pl->ProductId = Product::where('ProductNumber', '=', $priceList->ProductNumber)->first()->id;
                                $pl->Price = $priceList->Price;
                                $pl->MAP = $priceList->MAP;
                                if($pl->save()){
                                    $pd->SalePrice=$priceList->Price;
                                    $pd->save();
                                }
                            }
                        }
                    }
            } catch (\Exception $ex) {
                return Response::json(['message'=>$ex->getMessage(),'line'=>$ex->getLine(),'exception'=>$ex],422);

            }

        }
        return Response::json(['message'=>'Price added.']);

    }
    public function storeProductPriceException()
    {
        $productPrice = Http::withHeaders([
            'keycode' => env('API_COASTERAMER_KEY'),
            'Accept' => 'application/json'
        ])->get('http://api.coasteramer.com/api/product/GetPriceExceptionList?customerNumber='.env("CUSTOMER_ID"));
        $productPriceDecode = json_decode($productPrice);
        foreach ($productPriceDecode as $price) {

            try {

                $priceCheck=PricingException::where('PriceExceptionCode','like',$price->PriceExceptionCode)->first();
                if(empty($priceCheck)) {
                    $p = Pricing::create([
                        'PriceExceptionCode' => $price->PriceExceptionCode,
                    ]);
                }else{
                    $p=$priceCheck;
                }


                foreach ($price->PriceList as $priceList) {
                    if(empty(PricingExceptionList::where('ProductNumber','=',$priceList->ProductNumber)->first())) {
//                            return $priceList->ProductNumber;
                        if (Product::where('ProductNumber', '=', $priceList->ProductNumber)->first()) {
                            PricingExceptionList::create([
                                'ProductNumber' => $priceList->ProductNumber,
                                'ProductId' => Product::where('ProductNumber', '=', $priceList->ProductNumber)->first()->id,
                                'Price' => $priceList->Price,
                                'MAP' => $priceList->MAP,
                                'PriceExceptionId' => $p->id
                            ]);
                        }
                    }
                    else{
                        if (Product::where('ProductNumber', '=', $priceList->ProductNumber)->first()) {

                            $pl = PricingExceptionList::where('ProductNumber', '=', $priceList->ProductNumber)->first();
//                                $pl->ProductId = Product::where('ProductNumber', '=', $priceList->ProductNumber)->first()->id;
                            $pl->Price = $priceList->Price;
                            $pl->MAP = $priceList->MAP;
                            $pl->save();
                        }
                    }
                }
            } catch (\Exception $ex) {
                return Response::json(['message'=>$ex->getMessage(),'line'=>$ex->getLine(),'exception'=>$ex],422);

            }

        }
        return Response::json(['message'=>'Price exception added.']);

    }

    public function storeCategoryApiData()
    {
        $categories = Http::withHeaders([
            'keycode' => env('API_COASTERAMER_KEY'),
            'Accept' => 'application/json'
        ])->get('http://api.coasteramer.com/api/product/GetCategoryList');
        $categoriesDecode = json_decode($categories);
        foreach ($categoriesDecode as $category) {
            $slug = Str::slug($category->CategoryName, '-');
            try {
                $cat = Category::create([
                    'CategoryCode' => $category->CategoryCode,
                    'CategoryName' => $category->CategoryName,
                    'slug' => $slug
                ]);

            } catch (\Exception $ex) {

                $cat = Category::where('CategoryCode', $category->CategoryCode)->first();
                $cat->CategoryName = $category->CategoryName;
                $cat->Slug = $slug;
                $cat->save();

            }
            foreach ($category->SubCategoryList as $subcategory) {
                $slug = Str::slug($subcategory->SubCategoryName, '-');
                try {
                    $subcat = SubCategory::create([
                        'SubCategoryCode' => $subcategory->SubCategoryCode,
                        'SubCategoryName' => $subcategory->SubCategoryName,
                        'CategoryId' => $cat->id,
                        'slug' => $slug
                    ]);
                } catch (\Exception $ex) {
                    $subcat = SubCategory::where('SubCategoryCode', $subcategory->SubCategoryCode)->first();
                    $subcat->SubCategoryName = $subcategory->SubCategoryName;
                    $subcat->slug = $slug;
                    $subcat->save();
                }

                foreach ($subcategory->PieceList as $piece) {
                    try {
                        Piece::create([
                            'PieceCode' => $piece->PieceCode,
                            'PieceName' => $piece->PieceName,
                            'SubCategoryId' => $subcat->id
                        ]);
                    } catch (\Exception $ex) {
                        $p = Piece::where('PieceCode', $piece->PieceCode)->first();
                        $p->PieceName = $piece->PieceName;
                        $p->save();
                    }
                }
            }

        }
        return Response::json('Category Saved.', 200);
    }

    public function storeStyleApiData()
    {
        $styles = Http::withHeaders([
            'keycode' => env('API_COASTERAMER_KEY'),
            'Accept' => 'application/json'
        ])->get('http://api.coasteramer.com/api/product/GetStyleList');
        $stylesDecode = json_decode($styles);
        foreach ($stylesDecode as $style) {
            try {
                Style::create([
                    'StyleCode' => $style->StyleCode,
                    'StyleName' => $style->StyleName,
                ]);

            } catch (\Exception $ex) {

                $s = Style::where('StyleCode', $style->StyleCode)->first();
                $s->StyleName = $style->StyleName;
                $s->save();

            }

        }
        return Response::json('Style Saved.');
    }

    public function storeCollectionApiData()
    {
        $collections = Http::withHeaders([
            'keycode' => env('API_COASTERAMER_KEY'),
            'Accept' => 'application/json'
        ])->get('http://api.coasteramer.com/api/product/GetCollectionList');
        $collectionsDecode = json_decode($collections);
        foreach ($collectionsDecode as $collection) {
            try {
                $c = CollectionModel::create([
                    'CollectionCode' => $collection->CollectionCode,
                    'CollectionName' => $collection->CollectionName,
                ]);
                if (property_exists($collection, 'ImageUrl')) {
                    $c->ImageUrl = $collection->ImageUrl;
                    $c->save();

                }
            } catch (\Exception $ex) {
                $c = CollectionModel::where('CollectionCode', $collection->CollectionCode)->first();
                $c->CollectionName = $collection->CollectionName;
                if (property_exists($collection, 'ImageUrl')) {
//
                    $c->ImageUrl = $collection->ImageUrl;
                }
                $c->save();
            }
        }
        return Response::json('Collection Saved.');
    }

    public function storeProductLineApiData()
    {
        $productLines = Http::withHeaders([
            'keycode' => env('API_COASTERAMER_KEY'),
            'Accept' => 'application/json'
        ])->get('http://api.coasteramer.com/api/product/GetProductLineList');
        $productLinesDecode = json_decode($productLines);
        foreach ($productLinesDecode as $productLine) {
            try {
                ProductLine::create([
                    'ProductLineCode' => $productLine->ProductLineCode,
                    'ProductLineName' => $productLine->ProductLineName,
                ]);
            } catch (\Exception $ex) {
                $p = ProductLine::where('ProductLineCode', $productLine->ProductLineCode)->first();
                $p->ProductLineName = $productLine->ProductLineName;
                $p->save();
            }
        }
        return Response::json('Product Line Saved.');
    }

    public function storeGroupApiData()
    {
        $groups = Http::withHeaders([
            'keycode' => env('API_COASTERAMER_KEY'),
            'Accept' => 'application/json'
        ])->get('http://api.coasteramer.com/api/product/GetGroupList');
        $groupsDecode = json_decode($groups);
        foreach ($groupsDecode as $group) {
            try {
                $g = Group::create([
                    'GroupNumber' => $group->GroupNumber,
                    'Coaster' => 1
                ]);
                if (property_exists($group, 'MainProductNumber')) {
                    $g->MainProductNumber = $group->MainProductNumber;
                    $g->save();
                }
            } catch (\Exception $ex) {
                $g2 = Group::where('GroupNumber', $group->GroupNumber)->first();
                if (property_exists($group, 'MainProductNumber')) {
                    $g2->MainProductNumber = $group->MainProductNumber;
                    $g2->save();

                }
            }
        }
        return Response::json('Group Saved.');
    }

    public function storeWareHouse()
    {
        $warehouses = Http::withHeaders([
            'keycode' => env('API_COASTERAMER_KEY'),
            'Accept' => 'application/json'
        ])->get('http://api.coasteramer.com/api/product/GetWarehouseList');
        $warehousesDecode = json_decode($warehouses);
        foreach ($warehousesDecode as $warehouse) {
            try {
                $w = Warehouse::create([
                    'WarehouseCode' => $warehouse->WarehouseCode,
                    'Name' => $warehouse->Name,
                    'Address1' => $warehouse->Address1,
                    'City' => $warehouse->City,
                    'State' => $warehouse->State,
                    'Zip' => $warehouse->Zip,
                    'Phone' => $warehouse->Phone,
                ]);
                if (property_exists($warehouse, 'Address2')) {
                    $w->Address2 = $warehouse->Address2;
                    $w->save();
                }
                if (property_exists($warehouse, 'Fax')) {
                    $w->Fax = $warehouse->Fax;
                    $w->save();

                }
            } catch (\Exception $ex) {
                $w = Warehouse::where('WarehouseCode', $warehouse->WarehouseCode)->first();
                $w->Name = $warehouse->Name;
                $w->Address1 = $warehouse->Address1;
                $w->City = $warehouse->City;
                $w->State = $warehouse->State;
                $w->Zip = $warehouse->Zip;
                $w->Phone = $warehouse->Phone;

                if (property_exists($warehouse, 'Address2')) {
                    $w->Address2 = $warehouse->Address2;
                }

                $w->save();
            }
        }
        return Response::json('Warehouse Saved.');
    }

    public function storeWareHouseInventory()
    {
        $warehouseInventories = Http::withHeaders([
            'keycode' => env('API_COASTERAMER_KEY'),
            'Accept' => 'application/json'
        ])->get('http://api.coasteramer.com/api/product/GetInventoryList');
        $warehouseInventoriesDecode = json_decode($warehouseInventories);

        foreach ($warehouseInventoriesDecode[0]->InventoryList as $inventory) {
            if (Product::where('ProductNumber', $inventory->ProductNumber)->first() != null) {

                try {
                    $w = WarehouseInventory::create([
                        'WarehouseId' => Warehouse::where('WarehouseCode', $warehouseInventoriesDecode[0]->WarehouseCode)->first()->id,
                        'QtyAvail' => $inventory->QtyAvail,
                        'ProductNumber' => $inventory->ProductNumber,
                        'ProductId' => Product::where('ProductNumber', $inventory->ProductNumber)->first()->id,
                    ]);
                    if (property_exists($inventory, 'Incoming')) {
                        foreach ($inventory->Incoming as $incoming) {
                            InventoryEta::create([
                                'Qty' => $incoming->Qty,
                                'Eta' => new Carbon(strtotime($incoming->Eta)),
                                'WarehouseInventoryId' => $w->id,
                            ]);
                        }
                    }

                } catch (\Exception $ex) {
                    $w = WarehouseInventory::where('ProductNumber', $inventory->ProductNumber)->first();
                    $w->QtyAvail = $inventory->QtyAvail;
                    $w->save();
                    try {
                        InventoryEta::where('WarehouseInventoryId', $w->id)->delete();

                    } catch (\Exception $ex) {

                    }
                    if (property_exists($inventory, 'Incoming')) {
                        foreach ($inventory->Incoming as $incoming) {
                            InventoryEta::create([
                                'Qty' => $incoming->Qty,
                                'Eta' => new Carbon(strtotime($incoming->Eta)),
                                'WarehouseInventoryId' => $w->id,
                            ]);
                        }
                    }

                }
            }
        }
        return Response::json('Warehouse Inventory Saved.');
    }

//API CALLS END
    public function changePassword(Request $request)
    {
        $rules = [
            'password' => 'required',
            'new_password' => ['required', new PasswordValidate],
            'confirm_new_password' => 'same:new_password',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors(), 'old_data' => $validator->valid()], 422);
        }
        $admin = Admin::find(Auth::guard('admin')->user()->id);
        if (Hash::check($request->input('new_password'), $admin->password)) {
            return Response::json(['message' => 'Please use different password you already used this password.'], 422);

        }
        if (Hash::check($request->input('password'), $admin->password)) {
            $admin->password = Hash::make($request->input('new_password'));
            $admin->save();
            return Response::json(['message' => 'Password Successfully Changed.'], 200);

        } else {
            return Response::json(['message' => 'Current password is incorrect.'], 422);
        }
    }

    public function addBanner(Request $request)
    {
        $rules = [
            'image' => 'required|mimes:jpeg,jpg,png',
            'title' => 'required',
            'details' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors(), 'old_data' => $validator->valid()], 422);
        } else {
            $image = $request->file('image');
            $imageName = uniqid() . time() . date('Y') . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('uploads/banners'), $imageName);

            Banner::create([
                'image' => 'uploads/banners/' . $imageName,
                'title' => $request->input('title'),
                'details' => $request->input('details')
            ]);
            return Response::json(['message' => 'Banner Added'], 200);

        }
    }

    public function deleteBanner(Request $request, $id)
    {
        $banner = Banner::find($id);
        if ($banner == null) {
            return Response::json(['message' => 'Banner not found.'], 404);
        } else {
            $image = $banner->image;
            if ($banner->delete()) {
                if (file_exists(public_path($image))) {
                    unlink(public_path($image));
                }
                return Response::json(['message' => 'Banner Deleted'], 200);
            }

        }
    }

    public function getAllBanner()
    {
        $banners = Banner::all();
        return Response::json(['banners' => $banners], 200);
    }

    public function addTestimonial(Request $request)
    {
        $rules = [
            'image' => 'required|mimes:jpeg,jpg,png',
            'username' => ['required', new AlphaSpace()],
            'description' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors(), 'old_data' => $validator->valid()], 422);
        } else {
            $image = $request->file('image');
            $imageName = uniqid() . time() . date('Y') . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('uploads/testimonials'), $imageName);

            Testimonial::create([
                'image' => 'uploads/testimonials/' . $imageName,
                'username' => $request->input('username'),
                'description' => $request->input('description')
            ]);
            return Response::json(['message' => 'Testimonial Added'], 200);

        }
    }

    public function deleteTestimonial(Request $request, $id)
    {
        $testimonial = Testimonial::find($id);
        if ($testimonial == null) {
            return Response::json(['message' => 'Testimonial not found.'], 422);
        } else {
            $image = $testimonial->image;
            if ($testimonial->delete()) {
                if (file_exists(public_path($image))) {
                    unlink(public_path($image));
                }
                return Response::json(['message' => 'Testimonial Deleted'], 200);
            }
        }
    }

    public function getAllTestimonial()
    {
        $testimonials = Testimonial::all();
        return Response::json(['testimonials' => $testimonials], 200);
    }

    public function addFooterColumnOne(Request $request)
    {
        $rules = [
            'logo' => 'required|mimes:jpeg,jpg,png',
            'footer_text' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors(), 'old_data' => $validator->valid()], 422);
        } else {
            $image = $request->file('logo');
            $imageName = uniqid() . time() . date('Y') . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('uploads/footer'), $imageName);
            $footer = Footer::where('column', 1)->first();
            if ($footer == null) {
                Footer::create([
                    'logo' => 'uploads/footer/' . $imageName,
                    'footer_text' => $request->input('footer_text'),
                    'column' => 1
                ]);
            } else {
                if (file_exists(public_path($footer->logo))) {
                    unlink(public_path($footer->logo));
                }
                $footer->logo = 'uploads/footer/' . $imageName;
                $footer->footer_text = $request->input('footer_text');
                $footer->save();
            }
            return Response::json(['message' => 'Footer Column One Added.'], 200);
        }
    }

    public function addFooterColumnTwo(Request $request)
    {
        $rules = [
            'title' => 'nullable',
            'text' => 'required',
            'link' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors(), 'old_data' => $validator->valid()], 422);
        } else {
            Footer::create([
                'title' => $request->input('title'),
                'text' => $request->input('text'),
                'link' => $request->input('link'),
                'column' => 2
            ]);
            return Response::json(['message' => 'Footer Column Two Added.'], 200);
        }
    }

    public function addFooterColumnThree(Request $request)
    {
        $rules = [
            'title' => 'nullable',
            'text' => 'required',
            'link' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors(), 'old_data' => $validator->valid()], 422);
        } else {

            Footer::create([
                'title' => $request->input('title'),
                'text' => $request->input('text'),
                'link' => $request->input('link'),
                'column' => 3
            ]);
            return Response::json(['message' => 'Footer Column Three Added.'], 200);
        }
    }

    public function deleteFooter(Request $request, $id)
    {
        $footer = Footer::find($id);
        if ($footer == null) {
            return Response::json(['message' => 'Footer not found.'], 404);
        } else {
            $image = $footer->logo;
            if ($footer->delete()) {
                if ($image != null) {
                    if (file_exists(public_path($image))) {
                        unlink(public_path($image));
                    }
                }
                return Response::json(['message' => 'Footer Details Deleted.'], 200);
            }
        }
    }

    public function getFirstFooter()
    {
        $footer = Footer::where('column', 1)->get();
        return Response::json(['footer' => $footer]);
    }

    public function getSecondFooter()
    {
        $footer = Footer::where('column', 2)->get();
        return Response::json(['footer' => $footer]);
    }

    public function getThirdFooter()
    {
        $footer = Footer::where('column', 3)->get();
        return Response::json(['footer' => $footer]);
    }

    public function addContactInformation(Request $request)
    {
        $rules = [
            'address' => 'required',
            'phone_no' => ['required', new Phone()],
            'email' => 'required|email',
            'map_address' => 'required'
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors(), 'old_data' => $validator->valid()], 422);
        } else {
            $ws = WebsiteSettings::first();
            if ($ws == null) {
                WebsiteSettings::create([
                    'address' => $request->input('address'),
                    'phone_no' => $request->input('phone_no'),
                    'email' => strtolower($request->input('email')),
                    'map_address' => $request->input('map_address')
                ]);
                return Response::json(['message' => 'Contact information saved.'], 200);
            }

            $ws->address = $request->input('address');
            $ws->phone_no = $request->input('phone_no');
            $ws->email = strtolower($request->input('email'));
            $ws->map_address = $request->input('map_address');
            $ws->save();
            return Response::json(['message' => 'Contact information saved.'], 200);
        }
    }

    public function getContactInformation()
    {
        $ws = WebsiteSettings::select('address', 'phone_no', 'email', 'map_address')->first();
        return Response::json(['contact_information' => $ws]);
    }

    public function addHours(Request $request)
    {
        $rules = [
            'start_time' => 'required|array|max:3',
            'start_time.*' => 'required',
            'start_time_twelve_hour' => 'required|array|max:3|array_size:start_time',
            'start_time_twelve_hour.*' => 'required',
            'end_time' => 'required|array|max:3|array_size:start_time',
            'end_time.*' => 'required|',
            'end_time_twelve_hour' => 'required|array|max:3|array_size:start_time',
            'end_time_twelve_hour.*' => 'required',

        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors(), 'old_data' => $validator->valid()], 422);
        } else {
            $count = 0;
            foreach ($request->input('start_time') as $st) {
                $ws = null;
                if ($count == 0) {
                    $ws = Hour::where('duration', 'mon-thur')->first();
                    $duration = 'mon-thur';
                }
                if ($count == 1) {
                    $ws = Hour::where('duration', 'fri')->first();
                    $duration = 'fri';
                }
                if ($count == 2) {
                    $ws = Hour::where('duration', 'sat-sun')->first();
                    $duration = 'sat-sun';
                }
                if ($ws == null) {
                    Hour::create([
                        'start_time' => $request->input('start_time')[$count],
                        'start_time_twelve_hour' => $request->input('start_time_twelve_hour')[$count],
                        'end_time' => $request->input('end_time')[$count],
                        'end_time_twelve_hour' => $request->input('end_time_twelve_hour')[$count],
                        'duration' => $duration
                    ]);
                } else {
                    $ws->start_time = $request->input('start_time')[$count];
                    $ws->start_time_twelve_hour = $request->input('start_time_twelve_hour')[$count];
                    $ws->end_time = $request->input('end_time')[$count];
                    $ws->end_time_twelve_hour = $request->input('end_time_twelve_hour')[$count];
                    $ws->duration = $duration;
                    $ws->save();
                }
                $count++;
            }

            return Response::json(['message' => 'Hours information saved.'], 200);
        }
    }

    public function getHours()
    {
        return Response::json(['hours' => Hour::all()]);
    }

    public function addSocialNetworks(Request $request)
    {
        $rules = [
            'name' => ['required', new AlphaSpace()],
            'link' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors(), 'old_data' => $validator->valid()], 422);
        } else {
            Social::create([
                'name' => $request->input('name'),
                'link' => $request->input('link')
            ]);
        }
        return Response::json(['message' => 'Social Network Added.'], 200);
    }

    public function getAllSocial()
    {
        return Response::json(['social' => Social::all()], 200);
    }

    public function deleteSocialNetwork(Request $request, $id)
    {
        $social = Social::find($id);
        if ($social == null) {
            return Response::json(['message' => 'Social Network Not Found.'], 200);
        } else {
            $social->delete();
            return Response::json(['message' => 'Social Network Deleted.'], 200);
        }
    }

    public function addWeekendSpecial(Request $request)
    {
        $rules = [
            'text' => 'nullable',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors(), 'old_data' => $validator->valid()], 422);
        } else {
            $ws = WebsiteSettings::first();
            if ($ws == null) {
                WebsiteSettings::create([
                    'weekend_special' => $request->input('text'),
                ]);
                return Response::json(['message' => 'Weekend special updated.'], 200);
            }
            $ws->weekend_special = $request->input('text');
            $ws->save();
            return Response::json(['message' => 'Weekend special updated.'], 200);

        }
    }

    public function getWeekendSpecial()
    {
        return Response::json(['weekend_special' => $ws = WebsiteSettings::first()->weekend_special]);
    }

    public function addTitle(Request $request)
    {
        $rules = [
            'title' => 'nullable',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors(), 'old_data' => $validator->valid()], 422);
        } else {
            $ws = WebsiteSettings::first();
            if ($ws == null) {
                WebsiteSettings::create([
                    'title' => $request->input('title'),
                ]);
                return Response::json(['message' => 'Site Title Updated.'], 200);

            }
            $ws->title = $request->input('title');
            $ws->save();
            return Response::json(['message' => 'Site Title Updated.'], 200);

        }
    }

    public function getTitle()
    {
        return Response::json(['title' => $ws = WebsiteSettings::first()->title]);
    }

    public function addApiKey(Request $request)
    {

        $rules = [
            'stripe_sk' => 'nullable',
            'stripe_pk' => 'nullable',
            'paypal_sk' => 'nullable',
            'paypal_pk' => 'nullable',
            'stripe_wh' => 'nullable',
            'paypal_wh' => 'nullable',
            'paypal_email' => 'nullable',
        ];

        $validator = Validator::make($request->all(), $rules);

        $api = ApiKey::first();
        if ($api == null) {
            ApiKey::create([
                'stripe_sk' => $request->input('stripe_sk'),
                'stripe_pk' => $request->input('stripe_pk'),
                'paypal_sk' => $request->input('paypal_sk'),
                'paypal_pk' => $request->input('paypal_pk'),
                'stripe_wh' => $request->input('stripe_wh'),
                'paypal_wh' => $request->input('paypal_wh'),
                'paypal_email' => $request->input('paypal_email'),
            ]);
        } else {
            $api->stripe_sk = $request->input('stripe_sk');
            $api->stripe_pk = $request->input('stripe_pk');
            $api->paypal_sk = $request->input('paypal_sk');
            $api->paypal_pk = $request->input('paypal_pk');
            $api->stripe_wh = $request->input('stripe_wh');
            $api->paypal_wh = $request->input('paypal_wh');
            $api->paypal_email = strtolower($request->input('paypal_email'));
            $api->save();
        }
        return Response::json(['message' => 'Api Keys Updated.'], 200);

    }

    public function getApi()
    {
        return Response::json(['api' => ApiKey::first()]);
    }

    public function addHeader(Request $request)
    {
        $rules = [
            'address' => 'nullable',
            'logo' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);
        $header = Header::first();
        $logoName = null;
        if ($request->hasFile('logo')) {
            $logo = $request->file('logo');
            $logoName = time() . uniqid() . '.' . $logo->getClientOriginalExtension();
            $logo->move(public_path('uploads/website'), $logoName);
            if ($header == null) {
                Header::create([
                    'address' => $request->input('address'),
                    'logo' => 'uploads/website/' . $logoName
                ]);
            } else {

                if (file_exists(public_path($header->logo))) {
                    unlink(public_path($header->logo));
                }
                $header->address = $request->input('address');
                $header->logo = 'uploads/website/' . $logoName;
                $header->save();
            }
        }
        return Response::json(['message' => 'Logo Updated.'], 200);

    }

    public function addDeliveryFees(Request $request)
    {
        $rules = [
            'fees' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
        ];
        $validator = Validator::make($request->all(), $rules);

        $ws = WebsiteSettings::first();
        if ($ws == null) {
            WebsiteSettings::create([
                'delivery_fees' => $request->input('fees'),
                'tax' => $request->input('tax'),
            ]);
            return Response::json(['message' => 'Delivery fees updated.'], 200);
        }
        $ws->delivery_fees = $request->input('fees');
        $ws->tax = $request->input('tax');
        $ws->save();
        return Response::json(['message' => 'Delivery fees and Tax updated.'], 200);
    }
    public function addPrice(Request $request)
    {
        $rules = [
            'promotion' => ['nullable'],
            'price' => ['nullable'],
            ];
        $validator = Validator::make($request->all(), $rules);
        $ws = WebsiteSettings::first();

        if($request->input('promotion')){
            if ($ws == null) {
                WebsiteSettings::create([
                    'promotion' =>  $request->input('promotion'),
                ]);
            }else{
                $ws->promotion=$request->input('promotion');
                $ws->save();

            }
        }
        if($request->input('price')){
            if ($ws == null) {
                WebsiteSettings::create([
                    'price' =>  $request->input('price'),
                ]);
            }
            else{
                $ws->price=$request->input('price');
                $ws->save();
            }
        }
        return Response::json(['message' => 'Price details updated.'], 200);
    }

    public function getDeliveryFees()
    {
        return Response::json(['delivery_fee' => WebsiteSettings::first()->delivery_fees,'tax' => WebsiteSettings::first()->tax,'promotion' => WebsiteSettings::first()->promotion,'price' => WebsiteSettings::first()->price]);

    }

    public function getHeader()
    {
        return Response::json(['logo' => Header::first()->logo]);

    }

    public function getPermission()
    {
        return Response::json(['permissions' => Permission::all()]);
    }

    public function addSubAdmin(Request $request)
    {
        $rules = [
            'name' => ['required', new AlphaSpace()],
            'email' => 'email|required',
            'password' => ['required', new PasswordValidate],
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'required'
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors(), 'old_data' => $validator->valid()], 422);
        }
        $admin = Admin::create([
            'name' => $request->input('name'),
            'email' => strtolower($request->input('email')),
            'password' => Hash::make($request->input('password')),
            'sub_admin' => true,
            'super_admin' => false
        ]);
        foreach ($request->input('permissions') as $permission) {
            $admin->permissions()->attach($permission);
        }
        return Response::json(['message' => 'Sub admin added.'], 200);
    }

    public function editSubAdmin(Request $request, $id)
    {
        $rules = [
            'name' => ['required', new AlphaSpace()],
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'required'
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors(), 'old_data' => $validator->valid()], 422);
        }

        $admin = Admin::find($id);
        $superAdmin = $this->checkSuperAdmin($admin);
        if ($superAdmin) {
            return Response::json(['message' => 'Unauthorized'], 401);
        }
        if ($admin == null) {
            return Response::json(['message' => 'Admin not found'], 404);

        }
        if ($request->input('email') != null) {

            if ($request->input('email') != $admin->email) {
                $admin->email = strtolower($request->input('email'));
            }
        } else {
            return Response::json(['errors' => ['email' => ['Email is required.']], 'old_data' => $validator->valid()], 400);
        }
        $admin->name = $request->input('name');
        $admin->sub_admin = true;
        $admin->super_admin = false;
        $admin->save();
        $permissions = Permission::all();
        foreach ($permissions as $permission) {
            try {
                $admin->permissions()->detach($permission->id);
            } catch (\Exception $ex) {

            }
        }
        foreach ($request->input('permissions') as $permission) {
            $admin->permissions()->attach($permission);
        }
        $this->revokeAllToken($admin);

        return Response::json(['message' => 'Sub admin updated.']);
    }

    public function updateSubAdminPassword(Request $request, $id)
    {
        $rules = [
            'password' => ['required', new PasswordValidate],
            'confirm_password' => 'required|same:password'
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors(), 'old_data' => $validator->valid()], 422);
        }
        $admin = Admin::find($id);

        if ($admin == null) {
            return Response::json(['message' => 'Admin not found'], 422);

        }
        $superAdmin = $this->checkSuperAdmin($admin);
        if ($superAdmin) {
            return Response::json(['message' => 'Unauthorized'], 401);
        }
        $admin->password = Hash::make($request->input('password'));
        $admin->save();
        $this->revokeAllToken($admin);

        return Response::json(['message' => 'Sub admin password updated.'], 200);
    }

    public function getSubAdmin(Request $request)
    {
        $admin = Admin::find(Auth::guard('admin')->user()->id);
        $limit = Admin::where('id', '!=', $admin->id)->where('sub_admin', '=', 1)->count();
        $page = 0;

        if (!empty($request->input('page')) && !empty($limit)) {
            $page = (($request->input('page') - 1) * $limit);
        }

        if (!empty($request->input('name'))) {
            $subAdmin = Admin::with('permissions')
                ->where('id', '!=', $admin->id)->where('sub_admin', '=', 1)
                ->where('name', 'like', '%' . $request->input('name') . '%')
                ->offset($page)->limit($limit)
                ->get();

            $count = Admin::where('id', '!=', $admin->id)->where('sub_admin', '=', 1)
                ->where('name', 'like', '%' . $request->input('name') . '%')->count();
        } else {

            $subAdmin = Admin::with('permissions')
                ->where('id', '!=', $admin->id)
                ->where('sub_admin', '=', 1)
                ->offset($page)->limit($limit)
                ->get();
            $count = Admin::where('id', '!=', $admin->id)
                ->where('sub_admin', '=', 1)->count();
        }

        return Response::json(['sub_admin' => $subAdmin, 'total_number' => $count]);
    }

    public function getSubAdminById(Request $request, $id)
    {

        $subAdmin = Admin::with('permissions')->where('sub_admin', 1)->where('id', $id)->first();
        if ($subAdmin) {
            return Response::json(['sub_admin' => $subAdmin]);
        } else {
            return Response::json(['sub_admin' => []]);

        }
    }

    public function deleteSubAdminById(Request $request, $id)
    {

        $subAdmin = Admin::with('permissions')->where('sub_admin', 1)->where('id', $id)->delete();
        if ($subAdmin) {
            return Response::json(['message' => 'Sub admin deleted.'], 200);
        } else {
            return Response::json(['message' => 'Sub admin not found.'], 422);

        }
    }

    public function blockUser(Request $request, $id)
    {
//        $rules=[
//            'block'=>'required',
//        ];
//        $validator=Validator::make($request->all(),$rules) ;
//        if ($validator->fails()) {
//            return Response::json(['errors'=>$validator->errors(),'old_data'=>$validator->valid()],400);
//        }
        $user = User::find($id);
        if ($user == null) {
            return Response::json(['message' => 'User not found.'], 404);
        }
        $user->blocked = !($user->blocked);
        $user->save();
        $this->revokeAllToken($user);
        if ($user->blocked == 0) {
            return Response::json(['message' => 'User unblocked.'], 200);
        }
        if ($user->blocked == 1) {
            return Response::json(['message' => 'User blocked.'], 200);
        }

    }

    public function checkSuperAdmin(Admin $admin)
    {
        if ($admin->super_admin == 1) {
            return true;
        } else {
            return false;
        }
    }
    public function revokeAllToken($admin)
    {
        $adminTokens = $admin->tokens;
        foreach ($adminTokens as $token) {
            $token->revoke();
        }
        return Response::json(['message' => "Tokens revoked."], 200);
    }

    public function removeProduct(Request $request, $id)
    {
        $product = Product::find($id);

        if ($product == null) {
            return Response::json(['message' => 'Product not found.'], 404);
        } else {
            if(empty($product->ProductNumber)){
                  $style = $product->style;
              $collection = $product->collection;
              $productLine = $product->productLine;
              $group = $product->group;
                $piece = $product->piece;
            }

            $images = $product->nextGenImages;

            foreach ($images as $image) {
                if (file_exists(public_path($image->name))) {
                    unlink(public_path($image->name));
                }
                $image->delete();
            }
            $featured = $product->FeaturedImage;
            if (!empty($featured)) {
                if (file_exists(public_path($featured))) {
                    unlink(public_path($featured));
                }
                $featured->delete();
            }
            $delete = $product->delete();
            if ($delete) {
                if(empty($product->ProductNumber)) {
                    if (!empty($piece)) {
                        $piece->delete();
                    }
                    if (!empty($style)) {
                        $style->delete();
                    }
                    if (!empty($collection)) {
                        $collection->delete();
                    }
                    if (!empty($productLine)) {
                        $productLine->delete();
                    }
                    if (!empty($group)) {
                        $group->delete();
                    }
                }

            }
            return Response::json(['message' => 'Product deleted.'], 200);
        }
    }

    public function logout(Request $request)
    {
        $token = $request->user()->token();
        $token->revoke();
        $response = ['message' => 'You have been successfully logged out!'];
        return Response::json($response, 200);
    }

    public function checkLoggedIn()
    {
        if (Auth::guard('admin')->check()) {
            return Response::json(['message' => true], 200);
        } else {
            return Response::json(['message' => false], 200);
        }
    }
    public function changePriceOfSelectedProducts(Request $request)
    {
        $rules = [
            'products' => 'required|array|min:1',
            'products.*' => 'required',
//          'price' => 'required|numeric|min:0|max:100',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors(), 'old_data' => $validator->valid()], 422);
        } else {
            $products = Product::findMany($request->input('products'));
            if ($products == null) {
                return Response::json(['message' => 'Products not found.'], 404);
            }
            foreach ($products as $product) {
                $product->PromotionCheck = !($product->PromotionCheck);
//              $product->Promotion = $request->input('price');
                $product->save();
            }
            return Response::json(['message' => 'Price updated for selected products.'], 200);

        }

    }

    public function changePriceOfProductsWithCategory(Request $request)
    {
        $rules = [
            'category' => 'required|exists:categories,id',
//            'price' => 'required|numeric|min:0|max:100',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors(), 'old_data' => $validator->valid()], 422);
        } else {
            $category_id = $request->input('category');
            $products = Category::find($category_id)->products;
            if ($products == null) {
                return Response::json(['message' => 'Products not found.'], 422);
            }
            foreach ($products as $product) {
                $product->PromotionCheck = !($product->PromotionCheck);

//                $product->Promotion = $request->input('price');
                $product->save();
            }
            return Response::json(['message' => 'Price updated for selected products.'], 200);
        }
    }

    public function changePriceOfProductsWithSubCategory(Request $request)
    {
        $rules = [
            'subcategory' => 'required|exists:sub_categories,id',
//            'price' => 'required|numeric|min:0|max:100',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors(), 'old_data' => $validator->valid()], 422);
        } else {
            $subcategory_id = $request->input('subcategory');
            $products = SubCategory::find($subcategory_id)->products;
            if ($products == null) {
                return Response::json(['message' => 'Products not found.'], 404);
            }
            foreach ($products as $product) {
                $product->PromotionCheck = !($product->PromotionCheck);

//                $product->Promotion = $request->input('price');
                $product->save();
            }
            return Response::json(['message' => 'Price updated for selected products.'], 200);
        }
    }

    public function productsProvidedByCoaster(Request $request)
    {
        $category_name = $request->input('category_id');
        $subcategory_name = $request->input('subcategory_id');
        $product_name = $request->input('product_name');
        $product_number = $request->input('product_number');
        $style = $request->input('style_id');
        $material = $request->input('material');
        $color = $request->input('color');
        $warehouse = $request->input('warehouse');
        $type = $request->input('type');
        $page = 0;
        $limit = Product::whereNotNull('ProductNumber')->get()->count();
        $count = Product::whereNotNull('ProductNumber')->get()->count();
        $sort = ['id', 'asc'];
        if ($request->input('sort')) {
            $s = $request->input('sort');
            if ($s == 1) {
                $sort = ['id', 'asc'];
            }
            if ($s == 2) {
                $sort = ['id', 'asc'];

            }
            if ($s == 3) {
                $sort = ['id', 'desc'];
            }
            if ($s == 4) {
                $sort = ['SalePrice', 'asc'];
            }
            if ($s == 5) {
                $sort = ['SalePrice', 'desc'];
            }
        }
        if (!empty($request->input('limit'))) {
            $limit = $request->input('limit');
            if ($request->input('page')) {
                $page = ($request->input('page') - 1) * $limit;
            }
        }


        $where = '';
// If category id
        $b = 0;
        if (!empty($category_name)) {
            if ($where == '') {
                $where .= " CategoryId = $category_name ";
            } else {
                $where .= " and CategoryId = $category_name ";
            }
            $b = 1;
        }
        if (!empty($product_number)) {
            if ($where == '') {
                $where .= " ProductNumber like '$product_number' ";
            } else {
                $where .= " and ProductNumber like '$product_number' ";
            }
            $b = 1;
        }
//        if sub category
        if (!empty($subcategory_name)) {
            if ($where == '') {
                $where .= " SubCategoryId = $subcategory_name ";
            } else {
                $where .= " and SubCategoryId = $subcategory_name ";
            }
            $b = 1;

        }
//        if hide
        $a = 0;
        if ($type == "1") {

            if ($where == '') {
                $where .= " Hide = 1 ";
            } else {
                $where .= " and Hide = 1  ";
            }
            $a = 1;
        }
        if ($type == "2") {

            if ($where == '') {
                $where .= " Hide = 0 ";
            } else {
                $where .= " and Hide = 0 ";
            }
            $a = 1;
        }
        if ($type == "3") {

            if ($where == '') {
                $where .= " New = 1 ";
            } else {
                $where .= " and New =  1 ";
            }
            $a = 1;
        }
        if ($type == "4") {

            if ($where == '') {
                $where .= " Hot = 1 ";
            } else {
                $where .= " and Hot =  1 ";
            }
            $a = 1;
        }
        if ($type == "5") {

            if ($where == '') {
                $where .= " Hot = 0 ";
            } else {
                $where .= " and Hot =  0 ";
            }
            $a = 1;
        }
        if ($type == "6") {

            if ($where == '') {
                $where .= " PromotionCheck = 0 ";
            } else {
                $where .= " and PromotionCheck =  0 ";
            }
            $a = 1;
        }
        if ($type == "7") {

            if ($where == '') {
                $where .= " PromotionCheck = 1 ";
            } else {
                $where .= " and PromotionCheck =  1 ";
            }
            $a = 1;
        }
        if ($type == "8") {

            if ($where == '') {
                $where .= " Featured = 1 ";
            } else {
                $where .= " and Featured =  1 ";
            }
            $a = 1;
        }
//          if  product
        if (!empty($product_name)) {

            if ($where == '') {
                $where .= " Name like '%$product_name%' ";
            } else {
                $where .= " and Name like '%$product_name%' ";
            }
            $b = 1;
        }
//          if style
        if (!empty($style)) {
            if ($where == '') {
                $where .= " StyleId = $style ";
            } else {
                $where .= " and StyleId = $style ";
            }
            $b = 1;
        }
        if (!empty($color)) {
            if ($where == '') {
                $where .= " (FabricColor like '%$color%' or FinishColor like '%$color%')";
            } else {
                $where .= " and (FabricColor like '%$color%' or FinishColor like '%$color%')";
            }
            $b = 1;

        }
        if (!empty($material) && empty($warehouse)) {
            if ($where == '') {
                $products = Product::whereNotNull('ProductNumber')
                    ->whereHas('materials', function ($query) use ($material) {
                        $query->where('Value', 'like', "%$material%");
                    })->offset($page)->limit($limit)
                    ->orderBy($sort[0], $sort[1])
                    ->with(self::getRelationProduct())
                    ->get();
                $count = Product::whereNotNull('ProductNumber')
                    ->whereHas('materials', function ($query) use ($material) {
                        $query->where('Value', 'like', "%$material%");
                    })->count();
            } else {
                $products = Product::whereNotNull('ProductNumber')
                    ->whereRaw($where)
                    ->whereHas('materials', function ($query) use ($material) {
                        $query->where('Value', 'like', "%$material%");
                    })->offset($page)->limit($limit)
                    ->orderBy($sort[0], $sort[1])
                    ->with(self::getRelationProduct())
                    ->get();
                $count = Product::whereNotNull('ProductNumber')
                    ->whereRaw($where)
                    ->whereHas('materials', function ($query) use ($material) {
                        $query->where('Value', 'like', "%$material%");
                    })->count();
            }


        }
        if (empty($material) && !empty($warehouse)) {
            if ($where != '') {

                $products = Product::whereNotNull('ProductNumber')
                    ->whereHas('inventory', function ($query) use ($warehouse) {
                        $query->where('WarehouseId', '=', $warehouse);
                    })
                    ->whereRaw($where)
                    ->offset($page)->limit($limit)
                    ->orderBy($sort[0], $sort[1])
                    ->with(self::getRelationProduct())
                    ->get();
                $count = Product::whereNotNull('ProductNumber')
                    ->whereHas('inventory', function ($query) use ($warehouse) {
                        $query->where('WarehouseId', '=', $warehouse);
                    })
                    ->whereRaw($where)->count();
            } else {
                $products = Product::whereNotNull('ProductNumber')
                    ->whereHas('inventory', function ($query) use ($warehouse) {
                        $query->where('WarehouseId', 'like', $warehouse);
                    })
                    ->offset($page)->limit($limit)
                    ->orderBy($sort[0], $sort[1])
                    ->with(self::getRelationProduct())
                    ->get();
                $count = Product::whereNotNull('ProductNumber')
                    ->whereHas('inventory', function ($query) use ($warehouse) {
                        $query->where('WarehouseId', 'like', $warehouse);
                    })->count();
            }

        }
        if (!empty($material) && !empty($warehouse)) {
            if ($where != '') {

                $products = Product::whereNotNull('ProductNumber')
                    ->whereHas('inventory', function ($query) use ($warehouse) {
                        $query->where('WarehouseId', 'like', $warehouse);
                    })
                    ->whereHas('materials', function ($query) use ($material) {
                        $query->where('Value', 'like', "%$material%");
                    })
                    ->whereRaw($where)
                    ->offset($page)->limit($limit)
                    ->orderBy($sort[0], $sort[1])
                    ->with(self::getRelationProduct())
                    ->get();
                $count = Product::whereNotNull('ProductNumber')
                    ->whereHas('inventory', function ($query) use ($warehouse) {
                        $query->where('WarehouseId', 'like', $warehouse);
                    })
                    ->whereHas('materials', function ($query) use ($material) {
                        $query->where('Value', 'like', "%$material%");
                    })
                    ->whereRaw($where)->count();
            } else {
                $products = Product::whereNotNull('ProductNumber')
                    ->whereHas('inventory', function ($query) use ($warehouse) {
                        $query->where('WarehouseId', 'like', $warehouse);
                    })
                    ->whereHas('materials', function ($query) use ($material) {
                        $query->where('Value', 'like', "%$material%");
                    })
                    ->offset($page)->limit($limit)
                    ->orderBy($sort[0], $sort[1])
                    ->with(self::getRelationProduct())
                    ->get();

                $count = Product::whereNotNull('ProductNumber')
                    ->whereHas('inventory', function ($query) use ($warehouse) {
                        $query->where('WarehouseId', 'like', $warehouse);
                    })
                    ->whereHas('materials', function ($query) use ($material) {
                        $query->where('Value', 'like', "%$material%");
                    })->count();
            }

        }
        if (empty($material) && empty($warehouse)) {
            if ($where != '') {
                $products = Product::whereNotNull('ProductNumber')
                    ->whereRaw($where)
                    ->offset($page)->limit($limit)
                    ->orderBy($sort[0], $sort[1])
                    ->with(
                        self::getRelationProduct())
                    ->get();
                $count = Product::whereNotNull('ProductNumber')
                    ->whereRaw($where)->count();

            } else {
                $products = Product::whereNotNull('ProductNumber')
                    ->offset($page)->limit($limit)
                    ->orderBy($sort[0], $sort[1])
                    ->with(self::getRelationProduct())
                    ->get();
                $count = Product::whereNotNull('ProductNumber')->count();
            }
//            if($a==1 && $b==0){
//                $count=Product::whereNotNull('ProductNumber')
//                    ->whereRaw($where)
//                    ->count();
//            }
//            if($a==1 && $b==1){
//                $count=$products->count();
//            }


        }
//        $products=ConfigController::price($products);

        return Response::json([
            'products' => $products,
            'total_number' => $count,
            'filtered' => $products->count()]);
    }

    public function productsAddedByAdmin(Request $request)
    {
        $category_name = $request->input('category_id');
        $subcategory_name = $request->input('subcategory_id');
        $product_name = $request->input('product_name');
        $style = $request->input('style_id');
        $material = $request->input('material');
        $color = $request->input('color');
        $warehouse = $request->input('warehouse');
        $type = $request->input('type');
        $page = 0;
        $limit = Product::whereNull('ProductNumber')->get()->count();
        $count = Product::whereNull('ProductNumber')->get()->count();
        $sort = ['id', 'asc'];
        if ($request->input('sort')) {
            $s = $request->input('sort');
            if ($s == 1) {
                $sort = ['id', 'asc'];
            }
            if ($s == 2) {
                $sort = ['id', 'asc'];

            }
            if ($s == 3) {
                $sort = ['id', 'desc'];
            }
            if ($s == 4) {
                $sort = ['SalePrice', 'asc'];
            }
            if ($s == 5) {
                $sort = ['SalePrice', 'desc'];
            }
        }
        if (!empty($request->input('limit'))) {
            $limit = $request->input('limit');
            if ($request->input('page')) {
                $page = ($request->input('page') - 1) * $limit;
            }
        }


        $where = '';
// If category id
        $b = 0;
        if (!empty($category_name)) {
            if ($where == '') {
                $where .= " CategoryId = $category_name ";
            } else {
                $where .= " and CategoryId = $category_name ";
            }
            $b = 1;
        }
//        if sub category
        if (!empty($subcategory_name)) {
            if ($where == '') {
                $where .= " SubCategoryId = $subcategory_name ";
            } else {
                $where .= " and SubCategoryId = $subcategory_name ";
            }
            $b = 1;

        }
//        if hide
        $a = 0;
        if ($type == "1") {

            if ($where == '') {
                $where .= " Hide = 1 ";
            } else {
                $where .= " and Hide = 1  ";
            }
            $a = 1;
        }
        if ($type == "2") {

            if ($where == '') {
                $where .= " Hide = 0 ";
            } else {
                $where .= " and Hide = 0 ";
            }
            $a = 1;
        }
        if ($type == "3") {

            if ($where == '') {
                $where .= " New = 1 ";
            } else {
                $where .= " and New =  1 ";
            }
            $a = 1;
        }
        if ($type == "4") {

            if ($where == '') {
                $where .= " Hot = 1 ";
            } else {
                $where .= " and Hot =  1 ";
            }
            $a = 1;
        }
        if ($type == "5") {

            if ($where == '') {
                $where .= " Hot = 0 ";
            } else {
                $where .= " and Hot =  0 ";
            }
            $a = 1;
        }
        if ($type == "6") {

            if ($where == '') {
                $where .= " PromotionCheck = 0 ";
            } else {
                $where .= " and PromotionCheck =  0 ";
            }
            $a = 1;
        }
        if ($type == "7") {

            if ($where == '') {
                $where .= " PromotionCheck = 1 ";
            } else {
                $where .= " and PromotionCheck =  1 ";
            }
            $a = 1;
        }
        if ($type == "8") {

            if ($where == '') {
                $where .= " Featured = 1 ";
            } else {
                $where .= " and Featured =  1 ";
            }
            $a = 1;
        }
//          if  product
        if (!empty($product_name)) {
//            $product_name=str_replace('"','\"',$product_name);
            if ($where == '') {
                $where .= " Name like '%$product_name%' ";
            } else {
                $where .= " and Name like '%$product_name%' ";
            }
            $b = 1;
        }
//          if style
        if (!empty($style)) {
            if ($where == '') {
                $where .= " StyleId = $style ";
            } else {
                $where .= " and StyleId = $style ";
            }
            $b = 1;
        }
        if (!empty($color)) {
            if ($where == '') {
                $where .= " (FabricColor like '%$color%' or FinishColor like '%$color%')";
            } else {
                $where .= " and (FabricColor like '%$color%' or FinishColor like '%$color%')";
            }
            $b = 1;

        }
        if (!empty($material) && empty($warehouse)) {
            if ($where == '') {
                $products = Product::whereNull('ProductNumber')
                    ->whereHas('materials', function ($query) use ($material) {
                        $query->where('Value', 'like', "%$material%");
                    })->offset($page)->limit($limit)
                    ->orderBy($sort[0], $sort[1])
                    ->with(self::getRelationProduct())
                    ->get();
                $count = Product::whereNull('ProductNumber')
                    ->whereHas('materials', function ($query) use ($material) {
                        $query->where('Value', 'like', "%$material%");
                    })->count();
            } else {
                $products = Product::whereNull('ProductNumber')
                    ->whereRaw($where)
                    ->whereHas('materials', function ($query) use ($material) {
                        $query->where('Value', 'like', "%$material%");
                    })->offset($page)->limit($limit)
                    ->orderBy($sort[0], $sort[1])
                    ->with(self::getRelationProduct())
                    ->get();
                $count = Product::whereNull('ProductNumber')
                    ->whereRaw($where)
                    ->whereHas('materials', function ($query) use ($material) {
                        $query->where('Value', 'like', "%$material%");
                    })->count();
            }


        }
        if (empty($material) && !empty($warehouse)) {
            if ($where != '') {

                $products = Product::whereNull('ProductNumber')
                    ->whereHas('inventory', function ($query) use ($warehouse) {
                        $query->where('WarehouseId', '=', $warehouse);
                    })
                    ->whereRaw($where)
                    ->offset($page)->limit($limit)
                    ->orderBy($sort[0], $sort[1])
                    ->with(self::getRelationProduct())
                    ->get();
                $count = Product::whereNull('ProductNumber')
                    ->whereHas('inventory', function ($query) use ($warehouse) {
                        $query->where('WarehouseId', 'like', $warehouse);
                    })
                    ->whereRaw($where)->count();
            } else {
                $products = Product::whereNull('ProductNumber')
                    ->whereHas('inventory', function ($query) use ($warehouse) {
                        $query->where('WarehouseId', 'like', $warehouse);
                    })
                    ->offset($page)->limit($limit)
                    ->orderBy($sort[0], $sort[1])
                    ->with(self::getRelationProduct())
                    ->get();
                $count = Product::whereNull('ProductNumber')
                    ->whereHas('inventory', function ($query) use ($warehouse) {
                        $query->where('WarehouseId', 'like', $warehouse);
                    })->count();
            }

        }
        if (!empty($material) && !empty($warehouse)) {
            if ($where != '') {

                $products = Product::whereNull('ProductNumber')
                    ->whereHas('inventory', function ($query) use ($warehouse) {
                        $query->where('WarehouseId', 'like', $warehouse);
                    })
                    ->whereHas('materials', function ($query) use ($material) {
                        $query->where('Value', 'like', "%$material%");
                    })
                    ->whereRaw($where)
                    ->offset($page)->limit($limit)
                    ->orderBy($sort[0], $sort[1])
                    ->with(self::getRelationProduct())
                    ->get();
                $count = Product::whereNull('ProductNumber')
                    ->whereHas('inventory', function ($query) use ($warehouse) {
                        $query->where('WarehouseId', 'like', $warehouse);
                    })
                    ->whereHas('materials', function ($query) use ($material) {
                        $query->where('Value', 'like', "%$material%");
                    })
                    ->whereRaw($where)->count();
            } else {
                $products = Product::whereNull('ProductNumber')
                    ->whereHas('inventory', function ($query) use ($warehouse) {
                        $query->where('WarehouseId', 'like', $warehouse);
                    })
                    ->whereHas('materials', function ($query) use ($material) {
                        $query->where('Value', 'like', "%$material%");
                    })
                    ->offset($page)->limit($limit)
                    ->orderBy($sort[0], $sort[1])
                    ->with(self::getRelationProduct())
                    ->get();

                $count = Product::whereNull('ProductNumber')
                    ->whereHas('inventory', function ($query) use ($warehouse) {
                        $query->where('WarehouseId', 'like', $warehouse);
                    })
                    ->whereHas('materials', function ($query) use ($material) {
                        $query->where('Value', 'like', "%$material%");
                    })->count();
            }

        }
        if (empty($material) && empty($warehouse)) {
            if ($where != '') {
                $products = Product::whereNull('ProductNumber')
                    ->whereRaw($where)
                    ->offset($page)->limit($limit)
                    ->orderBy($sort[0], $sort[1])
                    ->with(self::getRelationProduct())
                    ->get();
                $count = Product::whereNull('ProductNumber')
                    ->whereRaw($where)->count();
            } else {
                $products = Product::whereNull('ProductNumber')
                    ->offset($page)->limit($limit)
                    ->orderBy($sort[0], $sort[1])
                    ->with(self::getRelationProduct())
                    ->get();
                $count = Product::whereNull('ProductNumber')->count();
            }

        }
//        $products=ConfigController::price($products);

        return Response::json([
            'products' => $products,
            'total_number' => $count,
            'filtered' => $products->count()]);
    }
    public static function getRelationProduct(){
        return ['measurements'
            , 'materials'
            , 'additionalFields'
            , 'relatedProducts'
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
            ,'ratingUser'
            ,'ratingUser.user'
        ];
    }
    public function productRules()
    {
        return [
            'name' => 'required',
            'description' => 'required',
            'fabric_color' => 'nullable',
            'finish_color' => 'nullable',
            'box_weight' => 'required',
            'cubes' => 'required',
            'type_of_packing' => 'required',
            'catalog_year' => 'required',
            'sub_brand' => 'nullable',
            'upc' => 'required',
            'country_of_origin' => 'required',
            'designer_collection' => 'nullable',
            'assembly_required' => 'required',
            'is_discontinued' => 'nullable',
            'num_images' => 'nullable',
            'num_boxes' => 'required',
//          'pack_qty'=>'required',
            'catalog_page' => 'nullable',
            'fabric_cleaning_code' => 'nullable',
            'num_hd_images' => 'nullable',
            'num_next_gen_images' => 'nullable',
            'style_name' => 'required|unique:styles,StyleName',
//          'collection_id'=>'required|exists:collection_models,id',
//          'product_line_id'=>'required|exists:product_lines,id',
            'product_line_id' => 'nullable',
            'group_number' => 'required|unique:groups,GroupNumber',
            'category_id' => 'required|exists:categories,id',
            'subcategory_id' => 'required|exists:sub_categories,id',
//          'piece_id'=>'required|exists:pieces,id',
            'images' => 'required|array|min:1',
            'images.*' => 'image|mimes:jpeg,jpg,png',
            'kit' => 'required',
            'box_size_length' => 'required',
            'box_size_width' => 'required',
            'box_size_height' => 'required',
            'measurements' => 'required|array|max:1',
            'measurements.*' => 'required',
            'measurements.*.piece_name' => 'required',
            'measurements.*.length' => 'required',
            'measurements.*.width' => 'required',
            'measurements.*.depth' => 'required',
            'measurements.*.height' => 'required',
            'measurements.*.diameter' => 'required',
            'measurements.*.depth_open' => 'required',
            'measurements.*.height_open' => 'required',
            'measurements.*.seat_width' => 'required',
            'measurements.*.seat_depth' => 'required',
            'measurements.*.seat_height' => 'required',
            'measurements.*.arm_height' => 'required',
            'measurements.*.desk_clearance' => 'required',
            'measurements.*.shelf_distance' => 'required',
//          'measurements.*.weight'=>'required',
            'materials' => 'nullable|array|min:1',
            'materials.*' => 'nullable',
            'materials.*.field' => 'nullable',
            'materials.*.value' => 'nullable',
            'additional_field_list' => 'nullable|array|min:1',
            'additional_field_list.*' => 'nullable',
            'additional_field_list.*.field' => 'nullable',
            'additional_field_list.*.value' => 'nullable',
            'related_product_list' => 'nullable|array|min:1',
            'related_product_list.*' => 'nullable',
            'related_product_list.*.id' => 'nullable',
            'components' => 'nullable|array|min:1',
            'components.*' => 'nullable',
            'components.*.name' => 'nullable',
            'components.*.box_weight' => 'nullable',
            'components.*.cubes' => 'nullable',
            'components.*.box_size_length' => 'nullable',
            'components.*.box_size_width' => 'nullable',
            'components.*.box_size_height' => 'nullable',
            'components.*.qty' => 'nullable',
            'warehouse_id' => 'required|exists:warehouses,id',
            'qty' => 'required',
            'price' => 'required',
            'featured' => 'nullable',
            'featured_image' => 'nullable|mimes:jpeg,jpg,png',
            'group_name' => 'required',
            'piece' => 'required',
            'collection_name' => 'required',
//          'promotion'=>'nullable|min:0|max:100'
            'promotion'=>'nullable'
        ];
    }

    public function storeProduct(Request $request)
    {
        $rules = $this->productRules();
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors()], 422);
        }
        try {
            $group = Group::create([
                'GroupNumber' => $request->input('group_number'),
                'GroupName' => $request->input('group_name')
            ]);
            $style = Style::create([
                'StyleName' => $request->input('style_name')
            ]);
            $piece = Piece::create([
                'PieceName' => $request->input('piece'),
                'SubCategoryId' => $request->input('subcategory_id')
            ]);
            $collection = CollectionModel::create([
                'CollectionName' => $request->input('collection_name')
            ]);
            $assembly_required = $request->input('assembly_required');
            if ($assembly_required == 'true') {
                $assembly_required = 1;
            } else {
                $assembly_required = 0;
            }
            $is_discontinued = $request->input('is_discontinued');
            if ($is_discontinued == 'true') {
                $is_discontinued = 1;
            } else {
                $is_discontinued = 0;
            }
            $featured = $request->input('featured');
            if ($featured == 'true') {
                $featured = 1;
            } else {
                $featured = 0;
            }
            $promotion = $request->input('promotion');
            if ($promotion == 'true') {
                $promotion= 1;
            } else {
                $promotion = 0;
            }
            $product = Product::create([
                'Name' => $request->input('name'),
                'Description' => $request->input('description'),
                'FabricColor' => $request->input('fabric_color'),
                'FinishColor' => $request->input('finish_color'),
                'BoxWeight' => $request->input('box_weight'),
                'Cubes' => $request->input('cubes'),
                'TypeOfPackaging' => $request->input('type_of_packing'),
                'CatalogYear' => $request->input('catalog_year'),
                'SubBrand' => $request->input('sub_brand'),
                'KitType' => $request->input('kit'),
                'Upc' => $request->input('upc'),
                'CountryOfOrigin' => $request->input('country_of_origin'),
                'DesignerCollection' => $request->input('designer_collection'),
                'AssemblyRequired' => $assembly_required,
                'IsDiscontinued' => $is_discontinued,
//              'NumImages' => $request->input('name'),
                'NumBoxes' => $request->input('num_boxes'),
                'PackQty' => $request->input('pack_qty'),
                'CatalogPage' => $request->input('catalog_page'),
                'FabricCleaningCode' => $request->input('fabric_cleaning_code'),
//              'NumHDImages' => $product->NumHDImages,
//              'NumNextGenImages' => $product->NumNextGenImages,
                'StyleId' => $style->id,
                'ProductLineId' => $request->input('product_line_id'),
                'GroupId' => $group->id,
                'CategoryId' => $request->input('category_id'),
                'SubCategoryId' => $request->input('subcategory_id'),
                'PieceId' => $piece->id,
                'Featured' => $featured,
                'BoxLength' => $request->input('box_size_length'),
                'BoxWidth' => $request->input('box_size_width'),
                'BoxHeight' => $request->input('box_size_height'),
                'RoomName' => $request->input('room_name'),
                'WoodFinish' => $request->input('wood_finish'),
                'ChemicalList' => $request->input('chemical_list'),
//                'Promotion' => $request->input('promotion'),
                'PromotionCheck' => $promotion,
                'SalePrice' => $request->input('price'),
                'CollectionId' => $collection->id
            ]);
            foreach ($request->input('measurements') as $measurement) {
                Measurement::create([
                    'PieceName' => $measurement['piece_name'],
                    'Length' => $measurement['length'],
                    'Width' => $measurement['width'],
                    'Depth' => $measurement['depth'],
                    'Height' => $measurement['height'],
                    'Diameter' => $measurement['diameter'],
                    'DepthOpen' => $measurement['depth_open'],
                    'HeightOpen' => $measurement['height_open'],
                    'SeatWidth' => $measurement['seat_width'],
                    'SeatDepth' => $measurement['seat_depth'],
                    'SeatHeight' => $measurement['seat_height'],
                    'ArmHeight' => $measurement['arm_height'],
//                    'Weight' => $measurement['weight'],
                    'DeskClearance' => $measurement['desk_clearance'],
                    'ShelfDistance' => $measurement['shelf_distance'],
                    'ProductId' => $product['id']
                ]);
            }
            if (!empty($request->input('materials'))) {
                foreach ($request->input('materials') as $material) {
                    Material::create([
                        'Field' => "Material",
                        'Value' => $material['value'],
                        'ProductId' => $product->id
                    ]);
                }
            }

            if (!empty($request->input('additional_fields'))) {
                foreach ($request->input('additional_fields') as $additionalField) {
                    AdditionalField::create([
                        'Field' => $additionalField['field'],
                        'Value' => $additionalField['value'],
                        'ProductId' => $product->id
                    ]);
                }
            }
            if (!empty($request->input('related_product_list'))) {

                foreach ($request->input('related_product_list') as $relatedProduct) {
                    RelatedProductList::create([
                        'RelatedProductId' => $relatedProduct['id'],
                        'ProductId' => $product->id
                    ]);
                }
            }
            if (!empty($request->input('components'))) {
                foreach ($request->input('components') as $component) {

                    Component::create([
//                    'ProductNumber' => $product->ProductNumber,
                        'Name' => $component['name'],
                        'BoxWeight' => $component['box_weight'],
                        'Cubes' => $component['cubes'],
                        'Qty' => $component['qty'],
                        'BoxLength' => $component['box_size_length'],
                        'BoxWidth' => $component['box_size_width'],
                        'BoxHeight' => $component['box_size_height'],
                        'ProductId' => $product->id
                    ]);
                }
            }
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $name = time() . uniqid() . '.' . $image->getClientOriginalExtension();
                    $image->move(public_path('uploads/product'), $name);
                    NextGenImage::create([
                        'Name' => 'uploads/product/' . $name,
                        'ProductId' => $product->id
                    ]);
                }
            }
            if ($request->file('featured_image')) {
                $f_image = $request->file('featured_image');
                $f_name = time() . uniqid() . '.' . $f_image->getClientOriginalExtension();
                $product->FeaturedImage = 'uploads/product/' . $f_name;
                $f_image->move(public_path('uploads/product'), $f_name);
                $product->save();
            }
            $w = WarehouseInventory::create([
                'WarehouseId' => $request->input('warehouse_id'),
                'QtyAvail' => $request->input('qty'),
                'ProductId' => $product->id,
            ]);
            $check = false;
            $i = 0;

            while ($check == false) {
                try {
                    $product->slug = Str::slug($product->Name, '-');
                    $check = $product->save();
                } catch (\Exception $exception) {

                    $product->slug = Str::slug($product->Name, '-') . '-' . time() . uniqid();
                    $check = $product->save();
                }
            }
            return Response::json(['message' => 'Product Added Successfully', 'data' => $product], 200);
        } catch (\Exception $ex) {
            return Response::json(['message' => 'Failed to add product.', 'error' => $ex], 422);
        }

    }

    public function editProduct(Request $request, $id)
    {

        $rules = $this->productRules();
        unset($rules['group_number']);
        unset($rules['style_name']);

        $product = Product::find($id);
        if ($product != null) {
            if (!empty($product->nextGenImages)) {
                unset($rules['images']);
                unset($rules['images.*']);
            }
        }
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors()], 422);
        }
        if ($product == null) {
            return Response::json(['message' => 'Product not found'], 404);
        } else {
            $style = $product->style;
            $collection = $product->collection;
//            $productLine=$product->productLine;
            $group = $product->group;
            $piece = $product->piece;
            if (!empty($style)) {
                if(!Style::where('StyleName',$request->input('style_name'))){
                    $style->StyleName = $request->input('style_name');

                }
                $style->save();

            } else {
                $style = Style::create([
                    'StyleName' => $request->input('style_name')
                ]);
            }
            if (!empty($collection)) {
                $collection->CollectionName = $request->input('collection_name');
                $collection->save();
            } else {
                $collection = CollectionModel::create([
                    'CollectionName' => $request->input('collection_name')
                ]);
            }
//            if(!empty($productLine)){
//                $productLine->delete();
//            }
            if (!empty($group)) {
                if(!Group::where('GroupNumber',$request->input('group_number'))) {
                    $group->GroupNumber = $request->input('group_number');
                }
                $group->GroupName = $request->input('group_name');
                $group->save();
            } else {
                $group = Group::create([
                    'GroupNumber' => $request->input('group_number'),
                    'GroupName' => $request->input('group_name')
                ]);
            }

            if (!empty($piece)) {
                $piece->PieceName = $request->input('piece');
                $piece->SubCategoryId = $request->input('subcategory_id');
                $piece->save();
            } else {
                $piece = Piece::create([
                    'PieceName' => $request->input('piece'),
                    'SubCategoryId' => $request->input('subcategory_id')
                ]);
            }

            $assembly_required = $request->input('assembly_required');
            if ($assembly_required == 'true') {
                $assembly_required = 1;
            } else {
                $assembly_required = 0;
            }
            $is_discontinued = $request->input('is_discontinued');
            if ($is_discontinued == 'true') {
                $is_discontinued = 1;
            } else {
                $is_discontinued = 0;
            }
            $featured = $request->input('featured');
            if ($featured == 'true') {
                $featured = 1;
            } else {
                $featured = 0;
            }
            $promotion = $request->input('promotion');
            if ($promotion == 'true') {
                $promotion= 1;
            } else {
                $promotion = 0;
            }
            $product->Name = $request->input('name');
            $product->Description = $request->input('description');
            $product->FabricColor = $request->input('fabric_color');
            $product->FinishColor = $request->input('finish_color');
            $product->BoxWeight = $request->input('box_weight');
            $product->Cubes = $request->input('cubes');
            $product->TypeOfPackaging = $request->input('type_of_packing');
            $product->CatalogYear = $request->input('catalog_year');
            $product->SubBrand = $request->input('sub_brand');
            $product->KitType = $request->input('kit');
            $product->Upc = $request->input('upc');
            $product->CountryOfOrigin = $request->input('country_of_origin');
            $product->DesignerCollection = $request->input('designer_collection');
            $product->AssemblyRequired = $assembly_required;
            $product->IsDiscontinued = $is_discontinued;
//          $product->NumImages= $request->input('name');
            $product->NumBoxes = $request->input('num_boxes');
            $product->PackQty = $request->input('pack_qty');
            $product->CatalogPage = $request->input('catalog_page');
            $product->FabricCleaningCode = $request->input('fabric_cleaning_code');
//          $product->NumHDImages= $product->NumHDImages;
//          $product->NumNextGenImages= $product->NumNextGenImages;
            $product->StyleId = $style->id;
            $product->CollectionId = $collection->id;
//          $product->ProductLineId= $request->input('product_line_id');
            $product->GroupId = $group->id;
            $product->CategoryId = $request->input('category_id');
            $product->SubCategoryId = $request->input('subcategory_id');
            $product->PieceId = $piece->id;
            $product->Featured = $featured;
            $product->BoxLength = $request->input('box_size_length');
            $product->BoxWidth = $request->input('box_size_width');
            $product->BoxHeight = $request->input('box_size_height');
            $product->RoomName = $request->input('room_name');
            $product->WoodFinish = $request->input('wood_finish');
            $product->ChemicalList = $request->input('chemical_list');
//            $product->Promotion = $request->input('promotion');
            $product->PromotionCheck = $promotion;
            $product->SalePrice = $request->input('price');
            $product->save();
            if (!empty($request->input('measurements'))) {

                foreach ($product->measurements as $mes) {
                    $mes->delete();
                }
                foreach ($request->input('measurements') as $measurement) {
                    Measurement::create([
                        'PieceName' => $measurement['piece_name'],
                        'Length' => $measurement['length'],
                        'Width' => $measurement['width'],
                        'Depth' => $measurement['depth'],
                        'Height' => $measurement['height'],
                        'Diameter' => $measurement['diameter'],
                        'DepthOpen' => $measurement['depth_open'],
                        'HeightOpen' => $measurement['height_open'],
                        'SeatWidth' => $measurement['seat_width'],
                        'SeatDepth' => $measurement['seat_depth'],
                        'SeatHeight' => $measurement['seat_height'],
                        'ArmHeight' => $measurement['arm_height'],
//                    'Weight' => $measurement['weight'],
                        'DeskClearance' => $measurement['desk_clearance'],
                        'ShelfDistance' => $measurement['shelf_distance'],
                        'ProductId' => $product['id']
                    ]);
                }

            }
            if (!empty($request->input('materials'))) {
                foreach ($product->materials as $mat) {
                    $mat->delete();
                }
                foreach ($request->input('materials') as $material) {
                    Material::create([
                        'Field' => "Material",
                        'Value' => $material['value'],
                        'ProductId' => $product->id
                    ]);
                }
            }
            if (!empty($request->input('additional_fields'))) {
                foreach ($product->additionalFields as $additionalField) {
                    $additionalField->delete();
                }
                foreach ($request->input('additional_fields') as $additionalField) {
                    AdditionalField::create([
                        'Field' => $additionalField['field'],
                        'Value' => $additionalField['value'],
                        'ProductId' => $product->id
                    ]);
                }
            }
            if (!empty($request->input('related_product_list'))) {
                if ($product->relatedProducts) {
                    foreach ($product->relatedProducts as $relatedProduct) {
                        $relatedProduct->delete();
                    }
                }

                foreach ($request->input('related_product_list') as $relatedProduct) {
                    RelatedProductList::create([
                        'RelatedProductId' => $relatedProduct['id'],
                        'ProductId' => $product->id
                    ]);
                }
            }
            if (!empty($request->input('components'))) {
                foreach ($product->components as $component) {
                    $component->delete();
                }
                foreach ($request->input('components') as $component) {
                    Component::create([
//                    'ProductNumber' => $product->ProductNumber,
                        'Name' => $component['name'],
                        'BoxWeight' => $component['box_weight'],
                        'Cubes' => $component['cubes'],
                        'Qty' => $component['qty'],
                        'BoxLength' => $component['box_size_length'],
                        'BoxWidth' => $component['box_size_width'],
                        'BoxHeight' => $component['box_size_height'],
                        'ProductId' => $product->id
                    ]);
                }
            }
            if ($request->hasFile('images')) {
//                foreach ($product->nextGenImages as $img){
//                    if(file_exists(public_path($img))){
//                        unlink(public_path($img));
//                    }
//                    $img->delete();
//                }
                foreach ($request->file('images') as $image) {
                    $name = time() . uniqid() . '.' . $image->getClientOriginalExtension();
                    $image->move(public_path('uploads/product'), $name);
                    NextGenImage::create([
                        'Name' => 'uploads/product/' . $name,
                        'ProductId' => $product->id
                    ]);
                }
            }
            if ($request->file('featured_image')) {
                if ($product->FeaturedImage) {
                    if (file_exists(public_path($product->FeaturedImage))) {
                        unlink(public_path($product->FeaturedImage));
                    }
                }
                $f_image = $request->file('featured_image');
                $f_name = time() . uniqid() . '.' . $f_image->getClientOriginalExtension();
                $product->FeaturedImage = 'uploads/product/' . $f_name;
                $f_image->move(public_path('uploads/product'), $f_name);
                $product->save();
            } else {
                if (!empty($product->FeaturedImage)) {
                    if (file_exists(public_path($product->FeaturedImage))) {
                        unlink(public_path($product->FeaturedImage));
                    }
                }
                $product->FeaturedImage = null;
                $product->save();
            }
            $product->inventory->QtyAvail = $request->input('qty');
            $product->inventory->save();
            $check=false;
            while ($check == false) {

                try {
                    $product->slug = Str::slug($product->Name, '-');
                    $check = $product->save();
                } catch (\Exception $exception) {


                    $product->slug = Str::slug($product->Name, '-') . '-' . time() . uniqid();
                    $check = $product->save();
                }
            }
            return Response::json(['message' => 'Product Details Updated'], 200);
        }
    }

    public function deleteImageById($id)
    {
        $img = NextGenImage::find($id);

        if (!empty($img)) {
            if (file_exists(public_path($img->name))) {
                unlink(public_path($img->name));
                $img->delete();
            }else{
                $img->delete();
            }
            return Response::json(['message' => 'Image Deleted.'], 200);

        }
        return Response::json(['message' => 'Image not found.'], 422);

    }

    public function dashboardCount()
    {
        return Response::json([
            'categories' => Category::all()->count(),
            'products' => Product::whereNotNull('ProductNumber')->count(),
            'adminproducts' => Product::whereNull('ProductNumber')->count(),
            'subcategories' => SubCategory::all()->count(),
            'users' => User::all()->count(),
        ]);
    }

    public function subCategoryByCategory(Request $request)
    {

        $subcategory = SubCategory::where('CategoryId', $request->input('id'))->get();
        return Response::json(['subcategory' => $subcategory]);
    }

    public function submitWarehouse(Request $request)
    {
        $rules = [
            'name' => ['required', new AlphaSpace()],
            'address1' => 'required',
            'address2' => 'nullable',
            'city' => 'required',
            'state' => ['required', new AlphaSpace()],
            'zip' => ['required', new Zip()],
            'phone' => ['required', new Phone()],
            'fax' => 'nullable',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors()], 422);
        }
        Warehouse::create([
            'Name' => $request->input('name'),
            'Address1' => $request->input('address1'),
            'Address2' => $request->input('address2'),
            'City' => $request->input('city'),
            'State' => $request->input('state'),
            'Zip' => $request->input('zip'),
            'Phone' => $request->input('phone'),
            'Fax' => $request->input('fax'),
        ]);
        return Response::json(['message' => "Warehouse added."], 200);
    }

    public function updateWarehouse(Request $request, $id)
    {
        $rules = [
            'name' => ['required', new AlphaSpace()],
            'address1' => 'required',
            'address2' => 'nullable',
            'city' => 'required',
            'state' => ['required', new AlphaSpace()],
            'zip' => ['required', new Zip()],
            'phone' => ['required', new Phone()],
            'fax' => 'nullable',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors()], 422);
        }
        $warehouse = Warehouse::find($id);

        $warehouse->Name = $request->input('name');
        $warehouse->Address1 = $request->input('address1');
        $warehouse->Address2 = $request->input('address2');
        $warehouse->City = $request->input('city');
        $warehouse->State = $request->input('state');
        $warehouse->Zip = $request->input('zip');
        $warehouse->Phone = $request->input('phone');
        $warehouse->Fax = $request->input('fax');
        $warehouse->save();
        return Response::json(['message' => "Warehouse Detail Updated."], 200);
    }

    public function getWarehouses(Request $request)
    {
        $warehouses = Warehouse::all();
        $count = $warehouses->count();
        $page = 0;
        $limit = 0;
        if ($request->input('limit')) {
            $limit = $request->input('limit');
            $page = ($request->input('page') - 1) * $limit;
            $warehouses = Warehouse::offset($page)->limit($limit)->get();
            if ($request->input('name')) {
                $warehouses = Warehouse::where('Name', 'like', '%' . $request->input('name') . '%')->offset($page)->limit($limit)->get();
                $count = $warehouses->count();
            }
        }
        if ($request->input('name')) {
            $warehouses = Warehouse::where('Name', 'like', '%' . $request->input('name') . '%')->get();
            $count = $warehouses->count();
        }
        return Response::json([
            'warehouses' => $warehouses,
            'total_number' => $count
        ]);

    }

    public function deleteWarehouse(Request $request, $id)
    {

        try {
            $warehouse = Warehouse::find($id);
            $warehouse->delete();
            return Response::json([
                'message' => "Warehouse detail deleted.",
            ]);
        } catch (\Exception $ex) {
            return Response::json([
                'message' => "Warehouse detail cannot be deleted.",
            ], 422);
        }

    }

    public function getWarehouseById(Request $request, $id)
    {
        return Response::json([
            'warehouse' => Warehouse::find($id)
        ]);
    }

    public function getStyle(Request $request)
    {
        $styles = Style::all();
        return Response::json(['styles' => $styles]);
    }

    public function getStyleByCoaster(Request $request)
    {
        $count = Style::whereNotNull('StyleCode')->count();;
        $limit = Style::whereNotNull('StyleCode')->count();
        $offset = 0;
        if (!empty($request->input('limit'))) {
            $limit = $request->input('limit');
        }
        if (!empty($request->input('page'))) {
            $offset = ($request->input('page') - 1) * $limit;
        }
        $styles = Style::whereNotNull('StyleCode')->offset($offset)->limit($limit)->orderBy('id', 'asc')->get();

        return Response::json(['styles' => $styles, 'total_number' => $count, 'filtered' => $styles->count()]);
    }

    public function getCollectionByCoaster(Request $request)
    {
        $count = CollectionModel::whereNotNull('CollectionCode')->count();;
        $limit = CollectionModel::whereNotNull('CollectionCode')->count();
        $offset = 0;
        if (!empty($request->input('limit'))) {
            $limit = $request->input('limit');
        }
        if (!empty($request->input('page'))) {
            $offset = ($request->input('page') - 1) * $limit;
        }
        $collections = CollectionModel::whereNotNull('CollectionCode')->offset($offset)->limit($limit)->orderBy('id', 'asc')->get();

        return Response::json(['collections' => $collections, 'total_number' => $count, 'filtered' => $collections->count()]);
    }

    public function getGroupByCoaster(Request $request)
    {

        $count = Group::where('Coaster', '=', 1)->count();
        $limit = Group::where('Coaster', '=', 1)->count();
        $offset = 0;
        if (!empty($request->input('limit'))) {
            $limit = $request->input('limit');
        }
        if (!empty($request->input('page'))) {
            $offset = ($request->input('page') - 1) * $limit;
        }
        $groups = Group::where('Coaster', '=', 1)->offset($offset)->limit($limit)->orderBy('id', 'asc')->get();

        return Response::json(['groups' => $groups, 'total_number' => $count, 'filtered' => $groups->count()]);
    }

    public function getWarehouseByCoaster(Request $request)
    {

        $count = Warehouse::whereNotNull('WarehouseCode')->count();
        $limit = Warehouse::whereNotNull('WarehouseCode')->count();
        $offset = 0;
        if (!empty($request->input('limit'))) {
            $limit = $request->input('limit');
        }
        if (!empty($request->input('page'))) {
            $offset = ($request->input('page') - 1) * $limit;
        }
        $name = $request->input('name');
        if ($name) {

            $warehouses = Warehouse::whereNotNull('WarehouseCode')->where('Name', 'like', '%' . $request->input('name') . '%')->offset($offset)->limit($limit)->get();

            $count = Warehouse::whereNotNull('WarehouseCode')->where('Name', 'like', '%' . $request->input('name') . '%')->count();
        } else {
            $warehouses = Warehouse::whereNotNull('WarehouseCode')->offset($offset)->limit($limit)->orderBy('id', 'asc')->get();

        }
//        dd($warehouses);
        return Response::json(['warehouses' => $warehouses, 'total_number' => $count, 'filtered' => $warehouses->count()]);
    }
//    public function getInventoryByCoaster(Request $request){
//
//        $count=WarehouseInventory::whereNotNull('ProductNumber')->count();
//        $limit=WarehouseInventory::whereNotNull('ProductNumber')->count();
//        $offset=0;
//        if(!empty($request->input('limit'))){
//            $limit=$request->input('limit');
//        }
//        if(!empty($request->input('page'))){
//            $offset=($request->input('page')-1)*$limit;
//        }
//        $warehouses=WarehouseInventory::whereNotNull('ProductNumber')->with('warehouse')->offset($offset)->limit($limit)->orderBy('id','asc')->get();
//
//        return Response::json(['inventories'=>$warehouses,'total_number'=>$count,'filtered'=>$warehouses->count()]);
//    }
    public function getInventoryByCoaster(Request $request)
    {

        $count = WarehouseInventory::whereNotNull('ProductNumber')->where('WarehouseId', $request->input('id'))->count();
        $limit = $count;
        $offset = 0;
        if (!empty($request->input('limit'))) {
            $limit = $request->input('limit');
        }
        if (!empty($request->input('page'))) {
            $offset = ($request->input('page') - 1) * $limit;
        }
//        $warehouses=WarehouseInventory::whereNotNull('ProductNumber')->with('warehouse')->offset($offset)->limit($limit)->orderBy('id','asc')->get();
        $warehouses = Warehouse::whereNotNull('WarehouseCode')->where('id', $request->input('id'))->with(['inventories' => function ($query) use ($offset, $limit) {
            $query->with('eta')->offset($offset)->limit($limit);
        },])->orderBy('id', 'asc')->first();

        return Response::json(['inventories' => $warehouses, 'total_number' => $count, 'filtered' => $warehouses->count()]);
    }

    public function getProductName(Request $request)
    {

        if ($request->input('id')) {
            $products = Product::select('id', 'Name')->where('id', '!=', $request->input('id'))->get();
        } else {
            $products = Product::select('id', 'Name')->get();
        }
        return Response::json(['products' => $products]);

    }

    public function getProductById($id)
    {

        $product = Product::where('id', 'like', $id)
            ->with(
                self::getRelationProduct()
        )->first();

        return Response::json([
            'product' => $product
        ]);
    }

    public function getMaterial(Request $request)
    {
        return Response::json([
            'material' => Material::select('Value')->groupBy('Value')->get()
        ]);
    }

    public function getColor(Request $request)
    {
        $color1 = Product::select('FabricColor as color')->whereNotNull('FabricColor')->groupBy('FabricColor')->get();
        $color2 = Product::select('FinishColor as color')->whereNotNull('FinishColor')->groupBy('FinishColor')->get();
        $color = $color1->toBase()->merge($color2);
        return Response::json([
            'color' => $color->unique()->flatten()
        ]);
    }

    public function getProductInfo(Request $request)
    {
        return Response::json([
            'product_info' => ProductInfo::whereNotNull('ProductNumber')->with('highlights', 'bullets', 'features')->get()
        ]);
    }

    public function statusProduct(Request $request)
    {
        $request->validate([
            'product_id' => ['required', 'array', 'min:1'],
            'product_id.*' => ['required','exists:products,id'],
            'type'=>['required']
        ]);
        $productIds = $request->input('product_id');

        foreach ($productIds as $id) {
            $product = Product::find($id);
            if ($product) {
                if($request->input('type')==='hide') {
                    $product->Hide = !($product->Hide);
                }
                if($request->input('type')==='hot') {
                    $product->Hot = !($product->Hot);
                }
                if($request->input('type')==='featured') {
                    $product->Featured = !($product->Featured);
                }
                if($request->input('type')==='new') {
                    $product->New = !($product->New);
                }
                $product->save();
            }
        }
        return Response::json(['message' => 'Product Status Updated.']);
    }

//    public function newProduct(Request $request)
//    {
//        $request->validate([
//            'product_id' => ['required', 'array', 'min:1'],
//            'product_id.*' => ['required','exists:products,id']
//        ]);
//        $productIds = $request->input('product_id');
//
//        foreach ($productIds as $id) {
//            $product = Product::find($id);
//            if ($product) {
//                $product->New = !($product->New);
//                $product->save();
//            }
//        }
//        return Response::json(['message' => 'Product New Arrival Status Updated.']);
//    }
//    public function featuredProduct(Request $request)
//    {
//        $request->validate([
//            'product_id' => ['required', 'array', 'min:1'],
//            'product_id.*' => ['required','exists:products,id']
//        ]);
//        $productIds = $request->input('product_id');
//
//        foreach ($productIds as $id) {
//            $product = Product::find($id);
//            if ($product) {
//                $product->Featured = !($product->Featured);
//                $product->save();
//            }
//        }
//        return Response::json(['message' => 'Product Featured Status Updated.']);
//    }
//
//    public function hot(Request $request)
//    {
//        $request->validate([
//            'product_id' => ['required', 'array', 'min:1'],
//            'product_id.*' => ['required','exists:products,id']
//        ]);
//        $productIds = $request->input('product_id');
//
//        foreach ($productIds as $id) {
//            $product = Product::find($id);
//            if ($product) {
//                $product->Hot = !($product->Hot);
//                $product->save();
//            }
//        }
//        return Response::json(['message' => 'Product Hot Status Updated.']);
//    }
    public function getPriceList(Request $request){
        $code=Pricing::first()->id;
            if($request->input('price_code')){
                $code=$request->input('price_code');
            }
        $count=ProductPrice::whereNotNull('ProductNumber')->whereHas('priceCode',function ($q) use ($code)
        {
            $q->where('id',$code);
        })->count();
        $limit=$count;
        $page=0;
        if($request->input('page') && $request->input('limit')){
            $limit=$request->input('limit');
            $page=($request->input('page')-1)*$limit;
        }

        $prices=Pricing::where('id',$code)->with(['priceList'=>function($q) use ($limit,$page){

            $q->offset($page)->limit($limit);

        }])->get();
        return Response::json(['prices'=>$prices,'total_number'=>$count]);
    }
    public function getPriceCodeList(Request $request){
        $count=Pricing::all()->count();
        $limit=$count;
        $page=0;
        if($request->input('page') && $request->input('limit')){
            $limit=$request->input('limit');
            $page=($request->input('page')-1)*$limit;
        }
        $prices=Pricing::withCount('priceList')->offset($page)->limit($limit)->get();
        return Response::json(['prices'=>$prices,'total_number'=>$count]);
    }
    public function getCoupons(Request $request){
        $limit=10;
        $page=0;
        $total=Coupon::all()->count();
        if($request->limit){
            $limit=$request->limit;
        }
        if($request->page){
            $page=($request->page-1)*$limit;
        }
        $coupons=Coupon::offset($page)->limit($limit)->get();
        return Response::json(['coupons'=>$coupons?$coupons:null,'total_number'=>$total,'filtered'=>$coupons->count()]);
    }
    public function getCouponById(Request $request,$id){
        $coupon=Coupon::find($id);
        return Response::json(['coupons'=>$coupon?$coupon:null]);
    }
    public function addCoupon(Request $request){
        $validatedData=$request->validate([
            'name'=>'required',
            'code'=>'required|unique:coupons,code',
            'from'=>'required|date',
            'to'=>'required|date|after_or_equal:now',
            'discount'=>'required|numeric|min:1|max:100',
            'max_usage'=>'required|numeric|min:1',
            'max_usage_per_user'=>'nullable|numeric|min:1'
        ]);

            $coupon=Coupon::create($validatedData);

       return Response::json(['message'=>'Coupon Added Successfully']);
    }
    public function updateCoupon(Request $request,$id){
        $validatedData=$request->validate([
            'name'=>'required',
            'code'=>['required',new Unique('coupons','code',$id)],
            'from'=>'required|date',
            'to'=>'required|date|after_or_equal:now',
            'discount'=>'required|numeric|min:1|max:100',
            'max_usage'=>'required|numeric|min:1',
            'max_usage_per_user'=>'nullable|numeric|min:1'
        ]);
        $coupon=Coupon::find($id)->update($validatedData);

       return Response::json(['message'=>'Coupon Updated Successfully']);
    }
    public function deleteCoupon($id){
            $coupon=Coupon::find($id);
            if($coupon){
                $coupon->delete();
                return Response::json(['message'=>'Coupon deleted.']);
            }
                return Response::json(['message'=>'Coupon not found.']);
    }
    public function assignCoupon(Request $request){
        $request->validate([
            'coupon_id'=>'required|exists:coupons,id',
            'user_id'=>'required|array|min:1',
            'user_id.*'=>'exists:users,id'
        ]);
        try {
            $coupon=Coupon::find($request->coupon_id);
            if(!$coupon){
                return Response::json(['message'=>'Coupon not found.']);
            }
            $coupon->users()->attach($request->user_id);
            return Response::json(['message'=>'Coupon assigned.']);
        }catch (\Exception $exception){
            return Response::json(['message'=>'Coupon assigning failed']);
        }
    }
    public function getOrders(Request $request){
        $page=0;
        $limit=Order::all()->count();
        if(!empty($request->limit) && !empty($request->page)){
            $limit=$request->limit;
            $page=($request->page-1)*$limit;
        }
        $where=" id != 0";
        $userWhere=" id != 0";
        $productWhere=" id != 0";
        $cityWhere=" id != 0";
        if($request->input('order_number')){
            $orderNumber=$request->input('order_number');
            $where.="  and id = $orderNumber";
        }
        if($request->input('status')){
            $status=$request->input('status');
            $where.="  and status = '$status'";
        }
        if($request->input('username')){
            $username=$request->input('username');
            $userWhere.=" and display_name like '%$username%'";
        }
        if($request->input('product_name')){
            $productName=$request->input('product_name');
            $productWhere.=" and Name like '%$productName%'";
        }
        if($request->input('city_name')){
            $cityName=$request->input('city_name');
            $cityWhere.=" and city like '%$cityName%'";
        }
        $orders=Order::whereRaw($where)->whereHas('user',function($query) use ($userWhere){
                    $query->whereRaw($userWhere);
                })->whereHas('items.product',function($query) use ($productWhere){
                    $query->whereRaw($productWhere);
                })->whereHas('address',function($query) use ($cityWhere) {
                        $query->whereRaw($cityWhere);})
            ->with(['user','items.product','address'])->withCount('items')->limit($limit)->offset($page)->get();


        $total=Order::whereRaw($where)->whereHas('user',function($query) use ($userWhere){
            $query->whereRaw($userWhere);
        })->whereHas('items.product',function($query) use ($productWhere){
            $query->whereRaw($productWhere);
        })->whereHas('address',function($query) use ($cityWhere) {
            $query->whereRaw($cityWhere);})
            ->count();

        return Response::json([
            'orders'=>$orders,
            'total_number'=>$total,
            'filtered'=>$orders->count()
        ]);
    }
    public function getOrderById($id){
        $order=Order::where('id',$id)->with('items.product.nextGenImages','address')->withCount('items')->first();
        return Response::json([
            'order'=>$order,
        ]);
    }
    public function cancelOrder(Request $request,$id){
        $request->validate([
            'status'=>'required'
        ]);
        $order=Order::where('id',$id)->first();
        if(!empty($order)){
            if($request->status=='cancelled'){
                $order->status=$request->status;
                $order->cancelled_by='admin';
            }else{
                $order->status=$request->status;
                $order->cancelled_by=null;
            }
            $order->save();
        }
        return Response::json(['message'=>"Order Status Updated."]);
    }
    public function getAllUsers(Request $request){
        $total=User::all()->count();
        $limit=$total;
        $page=0;
        $where=' id != 0';
        if($request->limit && $request->page){
            $limit=$request->limit;
            $page=($request->page-1)*$limit;
        }
        if($request->input('username')){
            $username=$request->input('username');
            $where.=" and display_name like '%$username%'";
        }
        $users=User::whereRaw($where)->withCount('orders')->with(['orders'=>function($query){
            $query->withCount('items');
        },'orders.items.product.nextGenImages'])->limit($limit)->offset($page)->get();
        return Response::json(['users'=>$users,'total_number'=>$total,'filtered'=>$users->count()]);
    }
    public function getUserById(Request $request,$id){
        $user=User::where('id',$id)->withCount('orders')->with(['orders'=>function($query){
            $query->withCount('items');
        },'orders.items.product.nextGenImages'])->first();
        return Response::json(['user'=>$user]);
    }
}

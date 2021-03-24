<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Route;
//      Guest routes
        Route::middleware('guest:user')->group(function (){
            Route::prefix('user')->group(function (){
                Route::post('login',[UserController::class,'login']);
                Route::post('registration',[UserController::class,'register']);
        });
//      Admin
        Route::prefix('admin')->group(function (){
                Route::post('login',[AdminController::class,'login'])->name('admin.login');
                Route::post('password-reset',[AdminController::class,'resetPassword']);
                Route::post('verify-token/{token?}',[AdminController::class,'verifyForgotPasswordToken']);
                Route::post('verify-code',[AdminController::class,'verifyCode']);
//              Route::post('password-reset-update-code/{email}',[AdminController::class,'verifyCodeUpdate']);
                Route::post('password-reset-update',[AdminController::class,'resetPasswordUpdate']);
            });
        });
//      User routes
        Route::middleware('auth:user')->prefix('user')->group(function (){
                Route::post('is-logged-in',[UserController::class,'checkLoggedIn']);
                Route::post('logout',[UserController::class,'logout']);
                Route::get('get-billing-address',[UserController::class,'getBillingAddress']);
                Route::post('store-billing-address',[UserController::class,'storeBillingAddress']);
                Route::put('update-billing-address',[UserController::class,'updateBillingAddress']);
                Route::get('get-shipping-address',[UserController::class,'getShippingAddress']);
                Route::post('store-shipping-address',[UserController::class,'storeShippingAddress']);
                Route::put('update-shipping-address',[UserController::class,'updateShippingAddress']);
                Route::put('update-details',[UserController::class,'updateYourProfile']);
                Route::put('update-password',[UserController::class,'updateYourPassword']);
                Route::prefix('cart')->group(function (){
                    Route::post('',[UserController::class,'cart']);
                    Route::get('{coupon?}',[UserController::class,'getCart']);
                    Route::post('/delete',[UserController::class,'cartDelete']);
                    Route::post('/empty',[UserController::class,'cartEmpty']);
                });
                Route::prefix('wishlist')->group(function (){
                    Route::post('',[UserController::class,'wishlist']);
                    Route::get('',[UserController::class,'getWishlist']);
                    Route::post('/delete',[UserController::class,'wishlistDelete']);
                    Route::post('/empty',[UserController::class,'wishlistEmpty']);
                });
                Route::prefix('order')->group(function (){
                    Route::post('',[UserController::class,'createOrder']);
                    Route::get('',[UserController::class,'getOrders']);
                });
            Route::post('rate',[UserController::class,'rateProduct']);

        });
//      Admin routes
        Route::middleware(['auth:admin','permission'])->prefix('admin')->group(function (){
            Route::get('count',[AdminController::class,'dashboardCount']);
            Route::post('logout',[AdminController::class,'logout']);
            Route::post('is-logged-in',[AdminController::class,'checkLoggedIn']);
//      API CALLS
            Route::get('/store-product-data',[AdminController::class,'storeProductApiData']);
            Route::get('/store-category-data',[AdminController::class,'storeCategoryApiData']);
            Route::get('/store-style-data',[AdminController::class,'storeStyleApiData']);
            Route::get('/store-collection-data',[AdminController::class,'storeCollectionApiData']);
            Route::get('/store-product-line-data',[AdminController::class,'storeProductLineApiData']);
            Route::get('/store-group-data',[AdminController::class,'storeGroupApiData']);
            Route::get('/store-warehouse-data',[AdminController::class,'storeWareHouse']);
            Route::get('/store-warehouse-inventory-data',[AdminController::class,'storeWareHouseInventory']);
            Route::get('/store-product-info-data',[AdminController::class,'storeProductInfoApiData']);
            Route::get('/store-product-price',[AdminController::class,'storeProductPrice']);
            Route::get('/store-product-price-exception',[AdminController::class,'storeProductPriceException']);
//      Warehouse
            Route::prefix('warehouse')->group(function (){
                Route::post('',[AdminController::class,'submitWarehouse']);
                Route::post('update/{id}',[AdminController::class,'updateWarehouse']);
                Route::post('get',[AdminController::class,'getWarehouses']);
                Route::delete('delete/{id}',[AdminController::class,'deleteWarehouse']);
                Route::post('/coaster',[AdminController::class,'getWarehouseByCoaster']);
                Route::post('/name',[AdminController::class,'getWarehouseName']);
                Route::post('{id}',[AdminController::class,'getWarehouseById']);
        });
//      Category routes
        Route::prefix('category')->group(function (){
                Route::post('',[AdminController::class,'storeCategory'])->middleware('scope:add-new-categories');
                Route::post('update/{id}',[AdminController::class,'updateCategory'])->middleware('scope:edit-categories');
                Route::post('get',[AdminController::class,'getCategories']);
                Route::delete('delete/{id}',[AdminController::class,'deleteCategory']);
                Route::post('coaster',[AdminController::class,'getCategoriesByCoaster']);
                Route::post('name',[AdminController::class,'getCategoriesByCoasterName']);
        });
//      Sub Category routes
        Route::prefix('sub-category')->group(function (){
                Route::post('',[AdminController::class,'storeSubCategory'])->middleware('scope:add-new-subcategories');
                Route::post('update/{id}',[AdminController::class,'updateSubCategory'])->middleware('scope:edit-subcategories');
                Route::post('get',[AdminController::class,'getSubCategories']);
                Route::post('coaster',[AdminController::class,'getSubCategoriesByCoaster']);
                Route::delete('delete/{id}',[AdminController::class,'deleteSubCategory']);
                Route::post('/category',[AdminController::class,'subCategoryByCategory']);
                Route::post('name',[AdminController::class,'getSubCategoriesByCoasterName']);
        });
//      Product
        Route::prefix('product')->group(function (){
                Route::post('/store',[AdminController::class,'storeProduct'])->middleware('scope:add-new-products');
                Route::delete('/delete/{id}',[AdminController::class,'removeProduct'])->middleware('scope:remove-products');
                Route::post('selected-product-price',[AdminController::class,'changePriceOfSelectedProducts'])->middleware('scope:edit-product');
                Route::post('selected-product-category-price',[AdminController::class,'changePriceOfProductsWithCategory'])->middleware('scope:edit-product');
                Route::post('selected-product-subcategory-price',[AdminController::class,'changePriceOfProductsWithSubCategory'])->middleware('scope:edit-product');
                Route::post('edit/{id}',[AdminController::class,'editProduct'])->middleware('scope:edit-product');
                Route::post('/coaster',[AdminController::class,'productsProvidedByCoaster']);
                Route::post('/',[AdminController::class,'productsAddedByAdmin']);
                Route::post('name',[AdminController::class,'getProductName']);
                Route::post('hide',[AdminController::class,'hideProduct']);
                Route::post('new',[AdminController::class,'newProduct']);
                Route::post('hot',[AdminController::class,'hot']);
                Route::post('/{id}',[AdminController::class,'getProductById']);
                Route::delete('/image/{id}',[AdminController::class,'deleteImageById']);
        });
//      Price List
            Route::post('price-list',[AdminController::class,'getPriceList']);
            Route::post('price-code',[AdminController::class,'getPriceCodeList']);
//      Style
        Route::post('style',[AdminController::class,'getStyle']);
        Route::post('add-price',[AdminController::class,'addPrice']);
        Route::post('style/coaster',[AdminController::class,'getStyleByCoaster']);
//      Collection
        Route::post('collection/coaster',[AdminController::class,'getCollectionByCoaster']);
//      Group
        Route::post('group/coaster',[AdminController::class,'getGroupByCoaster']);
//      Warehouse inventories
        Route::post('inventories/coaster',[AdminController::class,'getInventoryByCoaster']);
        Route::post('product-info/coaster',[AdminController::class,'getProductInfo']);
//      Change password admin
        Route::post('change-password',[AdminController::class,'changePassword']);
//      Materials
        Route::post('material',[AdminController::class,'getMaterial']);
//      Color
        Route::post('color',[AdminController::class,'getColor']);
//      Get Permission
        Route::get('/permissions',[AdminController::class,'getPermission']);
//      Website Settings
        Route::middleware('scope:edit-site')->group(function (){
//      Update logo
        Route::post('/logo',[AdminController::class,'addHeader']);
//      Contact Information
        Route::post('add-contact-information',[AdminController::class,'addContactInformation']);
//      Banners
        Route::post('add-banner',[AdminController::class,'addBanner']);
        Route::post('delete-banner/{id}',[AdminController::class,'deleteBanner']);
//      Testimonial
        Route::post('add-testimonial',[AdminController::class,'addTestimonial']);
        Route::post('delete-testimonial/{id}',[AdminController::class,'deleteTestimonial']);
//      Footer
        Route::post('add-footer-first',[AdminController::class,'addFooterColumnOne']);
        Route::post('add-footer-second',[AdminController::class,'addFooterColumnTwo']);
        Route::post('add-footer-third',[AdminController::class,'addFooterColumnThree']);
        Route::post('delete-footer/{id}',[AdminController::class,'deleteFooter']);
//      Weekend Special
        Route::post('add-weekend-special',[AdminController::class,'addWeekendSpecial']);
//      Delivery Fees
        Route::post('delivery-fees',[AdminController::class,'addDeliveryFees']) ;
//      Title
        Route::post('title',[AdminController::class,'addTitle']);
//      Hours
        Route::post('add-hours',[AdminController::class,'addHours']);
//      Social Network
        Route::post('add-social',[AdminController::class,'addSocialNetworks']);
        Route::post('delete-social/{id}',[AdminController::class,'deleteSocialNetwork']);
//      Store api key and PayPal email
        Route::post('api',[AdminController::class,'addApiKey']);
});
//      Sub Admin
        Route::post('sub-admin',[AdminController::class,'addSubAdmin'])->middleware('scope:add-new-sub-admin');
        Route::post('edit-sub-admin/{id}',[AdminController::class,'editSubAdmin'])->middleware('scope:edit-sub-admin');
        Route::post('update-sub-admin-password/{id}',[AdminController::class,'updateSubAdminPassword'])->middleware('scope:edit-sub-admin');
        Route::post('sub-admin/get',[AdminController::class,'getSubAdmin']);
        Route::post('sub-admin/{id}',[AdminController::class,'getSubAdminById']);
        Route::delete('sub-admin/delete/{id}',[AdminController::class,'deleteSubAdminById'])->middleware('scope:remove-sub-admin');
//      Block User
        Route::post('block-user/{id}',[AdminController::class,'blockUser'])->middleware('scope:user-block');
//      Coupon
        Route::prefix('coupon')->group(function (){
            Route::get('',[AdminController::class,'getCoupons']);
            Route::post('',[AdminController::class,'addCoupon']);
            Route::post('update/{id}',[AdminController::class,'updateCoupon']);
            Route::post('assign_user',[AdminController::class,'assignCoupon']);
            Route::get('{id}',[AdminController::class,'getCouponById']);
            Route::delete('{id}',[AdminController::class,'deleteCoupon']);
        });
        Route::prefix('order')->group(function (){
            Route::get('',[AdminController::class,'getOrders']);
        });
});
//      Site Getter
        Route::get('/logo',[AdminController::class,'getHeader']);
        Route::get('weekend-special',[AdminController::class,'getWeekendSpecial']);
        Route::get('get-social',[AdminController::class,'getAllSocial']);
        Route::get('api',[AdminController::class,'getApi']);
        Route::get('get-footer-first',[AdminController::class,'getFirstFooter']);
        Route::get('get-footer-second',[AdminController::class,'getSecondFooter']);
        Route::get('get-footer-third',[AdminController::class,'getThirdFooter']);
        Route::get('get-testimonials',[AdminController::class,'getAllTestimonial']);
        Route::get('get-banners',[AdminController::class,'getAllBanner']);
        Route::get('hours',[AdminController::class,'getHours']);
        Route::get('delivery-fees',[AdminController::class,'getDeliveryFees']);
        Route::get('title',[AdminController::class,'getTitle']);
        Route::post('products',[UserController::class,'getProducts']);
        Route::get('contact-information',[AdminController::class,'getContactInformation']) ;
//      Route::get('sub-category',[AdminController::class,'getSubCategories']);
        Route::post('category',[UserController::class,'getCategories']);
        Route::fallback(function(){
                return response()->json(
                    ['message' => 'Invalid Route.'], 404);
        });

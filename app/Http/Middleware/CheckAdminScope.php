<?php

namespace App\Http\Middleware;

use App\Models\Permission;
use Closure;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\Request;

class CheckAdminScope
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {

//        if(auth()->guard('admin')->user()->sub_admin==1 && auth()->guard('admin')->user()->super_admin==0){
//
//        $userPermissions = auth()->guard('admin')->user()->permissions;
//        if (!empty($userPermissions)) {
//            foreach ($userPermissions as $permission){
//
//                $request->request->add([
//                    'scope' => $permission->name
//                ]);
//            }
//
//        }
//    }else{
////            $userPermissions = Permission::all();
////            if (!empty($userPermissions)) {
////                foreach ($userPermissions as $permission){
//
//                    $request->request->add([
//                        'scope' => "*"
//                    ]);
////                }
////
////            }
//        }
        return $next($request);

    }
}

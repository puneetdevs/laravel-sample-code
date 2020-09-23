<?php

namespace App\Http\Middleware;

use Closure;
use Auth;
use App\Models\Role;
use App\Models\StaffRole;
use App\Models\User;
class UserRole
{
    /**
     * Check Role is User then access key otherwise return with error
     * handle
     *
     * @param  mixed $request
     * @param  mixed $next
     *
     * @return void
     */
    public function handle($request, Closure $next)
    {
        if (Auth::check()) {
            #Get User role here
            $role = Role::whereIn('role',['User','Admin','Super Admin'])->pluck('id')->toArray();

            #Check User Role is User or not
            $check_role = StaffRole::where('staff',Auth::user()->id)->whereIn('role',$role)->first();
            
            if(!$check_role){
                return response()->json(['message' => 'You are not authorized user to access this page.'], 403);
            }else{
                return $next($request);
            }
        }
        return $next($request);
    }
}

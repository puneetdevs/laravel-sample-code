<?php

namespace App\Http\Middleware;

use Closure;
use Auth;
use App\Models\Role;
use App\Models\StaffRole;
class SuperAdminRole
{
    /**
     * Check Role is Admin then access key otherwise return with error
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
            #Get Super-Admin role here
            $role = Role::where('role','Super Admin')->first();
            #Check User Role is Super-Admin or not
            $check_role = StaffRole::where('staff',Auth::user()->id)->where('role',$role->id)->first();
            
            if(!$check_role){
                return response()->json(['message' => 'You are not authorized user to access this page.'], 403);
            }else{
                return $next($request);
            }
        }
        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Auth;
use App\Models\Role;
use App\Models\StaffRole;
use App\Models\User;
class CompanyAdminRole
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
            #Get Super-Admin/Admin role here
            $role = Role::whereIn('role',['Super Admin','Admin','User'])->pluck('id')->toArray();
            #Check User Role is Super-Admin or not
            $check_role = StaffRole::where('staff',Auth::user()->id)->whereIn('role',$role)->first();
            $technician_check = User::where('id',$check_role->staff)->first();

            #Check Admin and super admin role
            if( ($check_role->role)==2 || ($check_role->role)==1 )
            {
                return $next($request);
            }
            #check user role with office access
            else if( ($check_role->role)==3 && $technician_check->office==1 )
            {
                return $next($request);
            }
            else
            {
                return response()->json(['message' => 'You are not authorized user to access this page.'], 403);
            }
            
        }
        return $next($request);
    }
}

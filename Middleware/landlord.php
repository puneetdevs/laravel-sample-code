<?php

namespace App\Http\Middleware;

use Closure;
use Auth;
use App\Models\Role;
use App\Models\StaffRole;
class landlord
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    
    public function handle($request, Closure $next)
    {
        if (Auth::check()) {
            #Get Super-Admin role here
            $role = Role::where('role','Super Admin')->first();
            #Check User Role is Super-Admin or not
            $check_role = StaffRole::where('staff',Auth::user()->id)->where('role',$role->id)->first();

            if(!$check_role){
                $tenantId = Auth::user()->company_id;
                \Landlord::addTenant('company_id', $tenantId); // Different column name, but same concept
            }
        }
        return $next($request);
    }
}

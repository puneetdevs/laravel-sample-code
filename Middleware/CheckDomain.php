<?php

namespace App\Http\Middleware;

use Closure;
use Auth;
use App\Models\User;
use App\Models\StaffRole;
class CheckDomain
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
        #Check headers
        $domain_number = $request->header('domain-number');
        $is_production = $request->header('is-production');
        #if is production
        if($is_production){
            if($is_production == 'true'){
                #Check domain is not empty if is-production header is true
                if($domain_number){
                    #Check if user logged in then check domain number in auth
                    if (Auth::check()) {
                        if(Auth::user()->domain_number == $domain_number){
                            return $next($request);
                        }else{
                            return response()->json(['message' => 'You are not authorized user for this domain.'], 403);
                        }
                    }else{
                        #If User not logged in then check domain number is exist in user table or not
                        $user = User::where('domain_number',$domain_number)->first();
                        if($user){
                            return $next($request);
                        }else{
                            return response()->json(['message' => 'You are not authorized user for this domain.'], 403);
                        }
                    }
                }else{
                    if (Auth::check()) {
                        if(StaffRole::where('staff',Auth::user()->id)->where('role',1)->first()){
                            return $next($request);
                        }else{
                            return response()->json(['message' => 'You are not authorized user for this domain.'], 403);
                        }
                    }else{
                        return response()->json(['message' => 'Please send valid domain name.'], 403);
                    }
                }
            }else{
                return $next($request);
            }
        }
        return response()->json(['message' => 'Please send domain number and is production.'], 403);
    }
}

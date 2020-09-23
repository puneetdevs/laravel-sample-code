<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\ApiController;
use App\Models\User;
use App\Models\StaffRole;
use App\Models\Company;
use App\Models\File;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Api\User\PasswordRequest;
use App\Http\Requests\Api\User\PasswordAdminRequest;
use Illuminate\Support\Facades\Hash;
use Validator;
use Carbon\Carbon;


/**
 * Passport
 *
 * @Resource("Passport", uri="/login")
 */

class PassportController extends ApiController
{
    
    public $successStatus = 200;

    /**
    * login api
    *
    * @return \Illuminate\Http\Response
    */
    public function login(Request $request){

        if (!filter_var($request->username, FILTER_VALIDATE_EMAIL)) {
           $column='username';
        }
        else
        {
            $column='email';
        }

        if(Auth::guard('web')->attempt([$column => $request->username, 'password' => $request->password])){
            $user = User::where($column,$request->username)->first();
            if($user){
                $domain_number = $request->header('domain-number');
                $is_production = $request->header('is-production');
                $website='';$address='';$phone='';
                $role = StaffRole::where('staff',$user->id)->first();
                if($is_production == 'true'){
                    if($domain_number){
                        //Company Check here
                        if($role && ($role->role == 2 || $role->role == 3)){
                            $company = Company::where('domain_number',$domain_number)->where('id',$user->company_id)->first();
                            
                            if(!$company){
                                return response()->json([MESSAGE=>'You are not authorized user for this domain.'], 403);
                            }
                        //Super Admin check here
                        }else if($role && $role->role == 1){
                            return response()->json([MESSAGE=>'You are not authorized user for this domain.'], 403);
                        }
                    }else{
                        if($role && $role->role == 2){
                            return response()->json([MESSAGE=>'You are not authorized user for this domain.'], 403);
                        }else if($role && $role->role == 3){
                            return response()->json([MESSAGE=>'You are not authorized user for this domain.'], 403);
                        }
                    }
                }
                
                $pst_number='';$gst_number='';
                $company = Company::where('id',$user->company_id)->first();
                if(!empty($company))
                {
                    $address=$company->address;
                    $pst_number=$company->pst_number;
                    $gst_number=$company->gst_number;
                    $phone=$company->phone;
                    
                    $website=$company->website;
                    if(empty($website))
                    {
                        $website=$company->url;
                    }
                }


                //Check Status here If user Deactivate then return error message
                if($user->active == 0){
                    return response()->json(['error' => 'You are deactivated by Admin. Please contact with admin.'], 401);
                }
                $tokenResult = $user->createToken('Personal Access Token');
                $token = $tokenResult->token;
                $token->save();

                //User Image set here
                $image = env('APP_URL').'/storage/user.png';
                $image_data = File::where('id',$user->pic_id)->first();
                if($image_data){
                    $file_path = storage_path($image_data->path);
                    if (file_exists($file_path)) {
                        $image = env('APP_URL').'/storage/'.$image_data->path;
                    }
                }

                //Company Logo
                $base64 = null;
                $company_logo = env('APP_URL').'/storage/user.png';
                $path = public_path('/storage/user.png');
                if (file_exists($path)) {
                    $type = pathinfo($path, PATHINFO_EXTENSION);
                    $content_data = file_get_contents($path);
                    $base64 = 'data:image/' . $type . ';base64,' . base64_encode($content_data);
                }
                $company_user = User::where('users.company_id',$user->company_id)
                                ->join('staff_roles','users.id', '=', 'staff_roles.staff')
                                ->where('staff_roles.role',2)
                                ->select('users.*')->first();
                if($company_user){
                    $logo = File::where('id',$company_user->company_logo)->first();
                    if($logo){
                        $file_path = storage_path($logo->path);
                        if (file_exists($file_path)) {
                            $company_logo = env('APP_URL').'/storage/'.$logo->path;
                            $type = pathinfo($file_path, PATHINFO_EXTENSION);
                            $content_data = file_get_contents($file_path);
                            $base64 = 'data:image/' . $type . ';base64,' . base64_encode($content_data);
                        }
                    }
                } 

                return response()->json([
                    'image' => $image,
                    'company_logo' => $company_logo,
                    'company_logo_base64' => $base64,
                    'userid' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'company_id' => $user->company_id,
                    'company_website' => $website,
                    'company_address' => $address,
                    'companyname' => $user->name,
                    'phone' => $phone,
                    'name' => $user->first_name.' '.$user->last_name, 
                    'technician' => $user->technician,
                    'office' => $user->office,
                    'company' => $role->role == 2 ? 1 : 0,
                    'access_token' => $tokenResult->accessToken,
                    'pst_number' => $pst_number,
                    'gst_number' => $gst_number,
                    'token_type' => 'Bearer',
                    'expires_at' => Carbon::parse(
                        $tokenResult->token->expires_at
                    )->toDateTimeString()
                ]);
            }
        }
        return response()->json(['error'=>'Wrong credentials. Please enter valid username and password.'], 401);
        
    }

    /**
     * Get Logo for login api company 
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function getLogo(Request $request){
        $domain_number = $request->header('domain-number');
        $is_production = $request->header('is-production');
        if($is_production == 'true' ){
            if(!empty($domain_number)){
                $company = Company::where('domain_number',$domain_number)->first();
                if(!$company){
                    return response()->json([MESSAGE=>'Domain name is invalid.'], 406);
                }
                    $company_logo = env('APP_URL').'storage/company_dummy_logo.png';
                    $logo = File::where('id',$company->logo)->first();
                    if($logo){
                        $file_path = storage_path($logo->path);
                        if (file_exists($file_path)) {
                            $company_logo = env('APP_URL').'/storage/'.$logo->path;
                        }
                    }
                    return response()->json([
                        'name' => $company->name,
                        'company_logo' => $company_logo
                    ]);
            }
        }
            return response()->json([
                'name' => '',
                'company_logo' => ''
            ]);
        
       
    }

    /**
     * Change Logged In User Password
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function changePassword(PasswordRequest $request)
    {
        try {
            $data = $request->all();
            $user = Auth::user();
            $user_id = $user->id;
            $user_password = $user->password;
            $old_password = $data['old_password'];

            if (!Hash::check($old_password, $user_password)) {
                return response()->json([SUCCESS => false, MESSAGE => 'The specified password does not match the database password.', 'data' => []], 422);
            }

            if ($old_password == $data['new_password']) {
                return response()->json([SUCCESS => false, MESSAGE => 'New password must be different from old password.', 'data' => []], 422);
            }

            $new_passsword = bcrypt($data['new_password']);

            $user_obj = User::where('id', $user_id)->update(['password' => $new_passsword]);

            if ($user_obj > 0) {
                return response()->json([SUCCESS => true, MESSAGE => 'Your password has been set successfully. Please login now.', 'data' => []], 200);
            } else {
                return response()->json([SUCCESS => false, MESSAGE => "Something was wrong.", 'data' => []], 500);
            }
        } catch (\Exception $e) {
            report($e);

            return response()->json([SUCCESS => false, MESSAGE => $e->getMessage(), 'data' => []], 500);
        }
    }

    /**
     * Change Admin Password
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function changeAdminDetail(PasswordAdminRequest $request)
    {
        try {
            $data = $request->all();
            $user = user::where('id',$request->user_id)->first();
            $user_data['first_name'] =  $data['first_name'];
            $user_data['last_name'] =  $data['last_name'];
            $request->has('new_password') && !empty($request->new_password) ? $user_data['password'] = bcrypt($data['new_password']) : '';
            $user_obj = User::where('id', $user->id)->update($user_data);

            if ($user_obj > 0) {
                return response()->json([SUCCESS => true, MESSAGE => 'Admin details has been change successfully.', 'data' => []], 200);
            } else {
                return response()->json([SUCCESS => false, MESSAGE => "Something was wrong.", 'data' => []], 500);
            }
        } catch (\Exception $e) {
            report($e);

            return response()->json([SUCCESS => false, MESSAGE => $e->getMessage(), 'data' => []], 500);
        }
    }
}

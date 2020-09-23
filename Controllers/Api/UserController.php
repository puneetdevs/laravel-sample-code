<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\ApiController;
use App\Models\User;
use App\Models\Role;
use App\Models\StaffRole;
use App\Models\File;
use App\Models\Company;
use App\Models\EmailNotification;
use App\Jobs\SendUserRegistrationEmail;
use App\Jobs\SendUserPasswordChangeEmail;
use App\Http\Requests\Api\User\Store;
use App\Http\Requests\Api\User\Update;
use App\Http\Requests\Api\User\ImageUpload;
use App\Transformers\UserTransformer;
use Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Helpers\Helper;
use App\Repositories\UserRepository;

/**
 * user
 *
 * @Resource("user", uri="/users")
 */

class UserController extends ApiController
{
    /*Construct here define user repository */
    public function __construct(UserRepository $userRepository){
        $this->userRepository = $userRepository;
    }
    
    /**
     * Get User Listing
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function index(Request $request)
    {
        #Set Sort by & Sort by Column
        $sortBy = Helper::setSortByValue($request);
        
        #Set Per Page Record
        $per_page = Helper::setPerPage($request);

        #Get User Role
        $role = Role::where('role','User')->first();
        #Check User role is user/staff
        $user = User::where('company_id',Auth::user()->company_id);
        #Check the User is technician or not
        if($request->has('technician') && !empty($request->technician)){   
            $technician =  $request->technician == 'true' ? 1 :0;  
            $user->where('technician',$technician);
        }
           
        $user->whereHas('staffrole', function($query) use($role){
            $query->where('role',$role->id);
        });

        if($request->has('active') && !empty($request->active)){   
            $active =  $request->active == 'true' ? 1 :0;  
            $user->where('active',$active);
        }

        #Search Filter Add here
        $columns_search = ['users.first_name','users.last_name'];
        if($request->has('q') && !empty($request->q)){
            $user->where(function ($query) use($columns_search, $request) {
                foreach($columns_search as $column) {
                    $query->orWhere($column, 'LIKE', '%' . $request->q . '%');
                }
            });
        }

        return $this->response->paginator($user->orderBy($sortBy['column'], $sortBy['order'])->paginate($per_page), new UserTransformer());
    }

    /**
     * Get Single User Detail
     *
     * @param  mixed $request
     * @param  mixed $user
     *
     * @return void
     */
    public function show(Request $request, $user)
    {
        $user = user::where('id',$user)->first();
        if($user)
        {
            return $this->response->item($user, new UserTransformer());
        }
        return $this->response->errorInternal('User not found. Please try again.');
    }

    /**
     * Add Users
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function store(Store $request)
    {
        $model=new User; 
        $requested_data = $request->all();
        $orignal_password = $requested_data['password'];
        $requested_data['name'] = Auth::user()->name;
        $requested_data['password'] = bcrypt($requested_data['password']);
        $requested_data['domain_number'] = Auth::user()->domain_number;
        $requested_data['company_id'] = Auth::user()->company_id;
        $model->fill($requested_data);
        if ($model->save()) {
            #Save Role
            $role = Role::where('role','User')->first();
            $data['staff'] =  $model->id;
            $data['role'] =  ($role) ? $role->id : '3';
            StaffRole::create($data);

            #Send Email Notification
            $this->sendUserRegisterEmail($model->toArray(), $orignal_password);
            User::where('id',$model->id)->update(['password'=> $requested_data['password']]);
            return $this->response->item($model, new UserTransformer());
        } 
        return $this->response->errorInternal('Error occurred while saving user.');
    }
 
    /**
     * Update User Detail
     *
     * @param  mixed $request
     * @param  mixed $user
     *
     * @return void
     */
    public function update(Update $request, $get_user_id)
    {
        $requested_data = $request->all();
        $passcode = '';
        if($request->has('password') && !empty($request->password)){
            $passcode = $requested_data['password'];
            $requested_data['password'] = bcrypt($requested_data['password']);
            
            if($get_user_id == Auth::user()->id){
                //Match current password here
                $user_detail=User::where('id',$requested_data['id'])->first();
                if(!Hash::check(($requested_data['currentpassword']), $user_detail->password))
                {
                    return response()->json(['error' => 'Current password is not correct.'], 401);
                }
            }
            
        }else{
            unset($requested_data['password']);
            unset($requested_data['confirmPassword']);
            
        }
        if($request->has('pic_id') && empty($request->pic_id)){
            unset($requested_data['pic_id']);
        }
        
        unset($requested_data['currentpassword']);
        
        User::where('id',$requested_data['id'])->update($requested_data);
        $user = User::where('id',$requested_data['id'])->first();
        $role = StaffRole::where(STAFF,$user->id)->first();
        if(!empty($passcode) && ($role->role!=1)){  
            $this->sendPasswordChangeEmail($user->toArray(),$passcode);
        }
        return $this->response->item($user, new UserTransformer());
    }

    /**
     * send Password Change Email
     *
     * @param  mixed $user_detail
     * @param  mixed $orignal_password
     *
     * @return void
     */
    public function sendPasswordChangeEmail($user_detail,$orignal_password){
        //Send Confirmation Email 
        $user_detail['password'] = $orignal_password;
        $company = Company::where('id',$user_detail['company_id'])->first();
        $user_detail['url'] = $company->url;
        $user_detail['company_name'] = $company->name;
        $company_logo = env('APP_URL').'/storage/company_dummy_logo.png';
        $logo = File::where('id',$company->logo)->first();
        if($logo){
            $file_path = storage_path($logo->path);
            if (file_exists($file_path)) {
                $company_logo = env('APP_URL').'/storage/'.$logo->path;
            }
        }
        $user_detail['company_logo'] = $company_logo;
        #Get user register email content here
        $template = EmailNotification::where('template_name','CHANGE-USER-PASSWORD-BY-ADMIN')->first();
        SendUserPasswordChangeEmail::dispatch($user_detail,$template);
    }

    /**
     * destroy
     *
     * @param  mixed $request
     * @param  mixed $user
     *
     * @return void
     */
    public function destroy(Request $request, $user)
    {
        
    }

    /**
     * Send User Register Email
     *
     * @param  mixed $user_detail
     * @param  mixed $orignal_password
     *
     * @return void
     */
    private function sendUserRegisterEmail($user_detail, $orignal_password)
    {
        //Send Confirmation Email 
        $user_detail['password'] = $orignal_password;
        $company = Company::where('id',$user_detail['company_id'])->first();
        $user_detail['url'] = $company->url;
        $user_detail['company_name'] = $company->name;
        $company_logo = env('APP_URL').'/storage/company_dummy_logo.png';
        $logo = File::where('id',$company->logo)->first();
        if($logo){
            $file_path = storage_path($logo->path);
            if (file_exists($file_path)) {
                $company_logo = env('APP_URL').'/storage/'.$logo->path;
            }
        }
        $user_detail['company_logo'] = $company_logo;
        #Get user register email content here
        $template = EmailNotification::where('template_name','USER-REGISTER')->first();
        SendUserRegistrationEmail::dispatch($user_detail,$template);
    }

    /**
     * Uploade Profile Image
     *
     * @param  mixed $user_detail
     * @param  mixed $orignal_password
     *
     * @return void
     */
    public function uploadProfileImage(ImageUpload $request)
    {
        $request_data =  $request->all();
        $request_data['upload_by'] = Auth::user()->id;
        $request_data['file_type'] = 'image';
        $request_data['object_type'] = 'user_profile';
        $request->has('id') ? $request_data['object_id'] = $request->id : '';
        $file_uploaded = $this->uploadImage($request, $request_data);
        if($file_uploaded){
            user::where('id',$request->id)->update(['pic_id' => $file_uploaded->id ]);
            $file_uploaded['path'] = env('APP_URL').'/storage/'.$file_uploaded['path'];
            return response()->json(['success' => true, MESSAGE => 'Image has been uploaded.', 'data' => $file_uploaded], 200);
        }
        return $this->response->errorInternal('Error occurred while saving files.');
        
    }

    /**
     * upload Image
     *
     * @param  mixed $request
     * @param  mixed $request_data
     *
     * @return void
     */
    public function uploadImage($request, $request_data){
        $file = $request->file('image');
        $destinationPath = 'public/images/';

        $file_orignal_name = $file->getClientOriginalName();
        $ext = $file->getClientOriginalExtension();
        $file_name = str_replace('.'.$ext,"", $file_orignal_name ).time().'.'.$ext;
        $file_name = str_replace(' ', '-', $file_name);
        $uploaded = Storage::put($destinationPath.$file_name, (string) file_get_contents($file), 'public');
       
        if($uploaded) {
            $file_path = 'app/'.$destinationPath.$file_name;
            $request_data['file_name'] = $file_orignal_name;
            $request_data['path'] = $file_path;
            if($image = File::create($request_data)){
                return $image;
            }
            return false;
        }
        return false;
    }

    /**
     * get Staff Reports
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function getStaffReports(Request $request)
    {
        $reporttype=$request->report_type;
        if(!empty($reporttype))
        {
            switch($reporttype)
            {
                case "timedetails":
                $staffreport = $this->userRepository->getStaffTimeDetailReport($request);
                
                break;

                case "timesummary":
                $staffreport = $this->userRepository->getStaffTimeDetailReport($request);
                break;

                case "printpayroll":
                $staffreport = $this->userRepository->getStaffPrintpayrollReport($request);
                break;

                default:
                return $this->response->errorInternal('Please send valid report type.');
                break;

            }
            return $this->response->array([STATUS => 200, 'data' => $staffreport]);
        }
        return $this->response->errorInternal('Please send print type.');
    }

    /**
     * limited Access Check
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function limitedAccessCheck(Request $request)
    {
        //Set api key's here
        $limited_access=array('place_listing','place_type_search','single_place_detail','street_type_listing','cities_listing','province_listing','create_workorder',
        'workorder_listing','single_workorder_detail','workorder_type_listing','workorder_status_listing','place_in_contact_listing',
        'add_workorder_time','workorder_time_listing','delete_workorder_time','create_workorder_part','delete_workorder_parts',
        'workorder_parts_listing','add_workorder_schedule','workorder_schedule_listing','delete_workorder_schedule','get_purchase_order_in_workorder',
        'update_workorder','get_management_listing','get_management_detail','get_place_management_type_list','add_place_in_management',
        'get_management_in_place','update_place_management_isdefault','delete_place_management','contact_listing','single_contact_detail',
        'contact_type_listing','add_place_in_contact','get_contact_in_place','update_place_contact_isdefault','delete_place_contact',
        'add_device_in_place','device_in_place_list','delete_device_in_place','update_placeinfo_in_placedevice','single_placeinfo_in_placedevice',
        'add_area_in_placedevice','update_area_in_placedevice','delete_area_in_placedevice','area_list_in_placedevice','single_area_in_placedevice',
        'add_device_in_placedevice','update_device_in_placedevice','delete_device_in_placedevice','get_devices_in_placedevice',
        'get_single_device_in_placedevice','device_type_listing','add_inspection_in_device','inspection_list_in_places','delete_inspection_in_places',
        'update_inspection_in_place','single_inspection_detail_in_place','add_area_in_inspection','update_area_in_inspection',
        'delete_area_in_inspection','get_area_in_inspection','area_detail_in_inspection','add_device_in_inspection','update_device_in_inspection',
        'delete_device_in_inspection','device_list_in_inspection','device_detail_in_inspection','update_inspection_device_checks','deficiency_listing',
        'update_deficiency_device_in_inspection','get_deficiency_device_detail_inspection','inspection_status_listing','inspection_print',
        'form_template_field_details','form_template','inspection_device_form_in_inspection','add_new_monitoring','monitoring_listing',
        'single_monitoring_detail','update_monitoring','delete_monitoring','get_place_file_type','upload_place_file','get_place_files',
        'delete_place_file','inspection_type_list','inspection_listing','create_management','single_management_detail','get_place_in_management',
        'add_management_in_contact','delete_management_contact','get_contact_in_management','update_management_contact_isdefault',
        'upload_management_files','get_management_file_listing','delete_management_file','create_new_contact','get_place_in_contact',
        'get_management_in_contact','workorder_listing','monitoring_report','create_monitoring_invoice','part_listing','time_code_pagewise','create_place',
        'update_contact','place_communication_report','invoice_payment_in_management','quotes_listing','place_communication_type_list',
        'area_listing','staff_listing');

        $role = Role::whereIn('role',['User'])->pluck('id')->toArray();
        
        //Check User Role is Super-Admin or not
        $check_role = StaffRole::where(STAFF,Auth::user()->id)->whereIn('role',$role)->first();

        $technician_check = User::where('id',$check_role->staff)->first();

        if(!empty($request->api_key))
        {
            //Check if the technician has office full access or limited access
            if($technician_check->office==0 && !in_array($request->api_key,$limited_access))
            {
                return $this->response->array([STATUS => 460, MESSAGE => 'You are not allowed to access this api.']);
            }
            return $this->response->array([STATUS => 200, MESSAGE => 'You can access this api.']);        
        }
        return $this->response->errorInternal('Please send api key.');
    }

}

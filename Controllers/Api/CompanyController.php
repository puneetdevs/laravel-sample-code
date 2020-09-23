<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\ApiController;
use App\Models\Company;
use App\Models\StaffRole;
use App\Models\User;
use App\Models\File;
use App\Models\AppSetting;
use App\Jobs\SendRegistrationEmail;
use App\Transformers\CompanyTransformer;
use App\Http\Requests\Api\Companies\Index;
use App\Http\Requests\Api\Companies\Show;
use App\Http\Requests\Api\Companies\Create;
use App\Http\Requests\Api\Companies\Store;
use App\Http\Requests\Api\Companies\Edit;
use App\Http\Requests\Api\Companies\Update;
use App\Http\Requests\Api\Companies\Destroy;
use App\Http\Requests\Api\Companies\AdminStatus;
use App\Http\Requests\Api\Companies\UpdateProfile;
use App\Http\Requests\Api\User\ImageUpload;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Auth;
use DB;
use App\Helpers\Helper;
use App\Models\EmailNotification;


/**
 * Company
 *
 * @Resource("Company", uri="/companies")
 */

class CompanyController extends ApiController
{
    
    /**
     * Get Company listing with user detail
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function index(Index $request)
    {   
        //Set Sort by & Sort by Column
        $sortBy = Helper::setSortByValue($request);
        //Set Per Page Record
        $per_page = Helper::setPerPage($request);
        //query start here
        $company = Company::select('*');
        $columns_search = ['companies.name','companies.city','companies.province','companies.phone'];
        if($request->has('q') && !empty($request->q)){
            $company->where(function ($query) use($columns_search, $request) {
                foreach($columns_search as $column) {
                    $query->orWhere($column, 'LIKE', '%' . $request->q . '%');
                }
                $query->orWhereHas('user',function($q) use($request){
                    $q->where('username', 'LIKE', '%' . $request->q . '%');
                });
            });
        }
        $company->select('companies.*','staff_roles.staff','staff_roles.role', DB::raw('(users.created_at) date,(users.name) user_name,(users.id) user_id'))
                ->join('users', 'companies.id', '=', 'users.company_id')
                ->join('staff_roles', 'users.id', '=', 'staff_roles.staff');
                
            $company->where('staff_roles.role',2);
        
        return $this->response->paginator($company->orderBy($sortBy['column'], $sortBy['order'])->paginate($per_page), new CompanyTransformer());
    }

    /**
     * Show Single Company Detail
     *
     * @param  mixed $request
     * @param  mixed $company
     *
     * @return void
     */
    public function show(Show $request, $company)
    {
        $compnay = Company::where('id',$company)->first();
        if($company){
            return $this->response->item($compnay, new CompanyTransformer());  
        }
        return response()->json([MESSAGE=>'Company not found. Please try again.'], 404);
    }

    /**
     * Create Company
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function store(Store $request)
    {   
        //Save company Detail here
        $model=new Company;
        $requested_data = $request->all();
        $requested_data['created_by'] = Auth::user()->id;
        $model->fill($requested_data);
        if ($model->save()) {
            //Create Domain number and URL and update on companies table
            $requested_data['domain_number'] = $this->createDomainNumber($model->id);
            $requested_data['url'] = env('URL_PREFIX').$requested_data['domain_number'].'.'.env('FRONTEND_URL');
            Company::where('id',$model->id)->update(['domain_number' => $requested_data['domain_number'], 'url' => $requested_data['url']]);
            $model->url = $requested_data['url'];
            $model->domain_number = $requested_data['domain_number'] ;
            //Create Company Admin Here
            $requested_data['id'] = $model->id;
            $company_admin = $this->createCompanyAdmin($requested_data);
            $requested_data['reset_url'] = $requested_data['url'].'/auth/'.app('auth.password.broker')->createToken($company_admin).'/reset-password';
            //Send Registered Email notification to user_email 
            if($company_admin){
                $requested_data['user_id'] = $company_admin->id;
                $requested_data['website_name'] = Auth::user()->name;
                //Get Company register email content here
                $template = EmailNotification::where('template_name','COMPANY-REGISTER')->first();
                $this->dispatch(new SendRegistrationEmail($requested_data,$template));
            }
            //Set Time zone
            $this->SetDefaultTimeZone($model->id,$request);
            return $this->response->item($model, new CompanyTransformer());
        } else {
              return $this->response->errorInternal('Error occurred while saving company.');
        }
    }

    /**
     * Set Default Time Zone and setting for New Company
     *
     * @param  mixed $company_id
     * @param  mixed $request
     *
     * @return void
     */
    private function SetDefaultTimeZone($company_id,$request){
        $winterization = AppSetting::where('company_id',0)->first();
        $request_data['company_id'] = $company_id;
        $request_data['company_name'] = $request->name;
        $request_data['company_email'] = $request->email;
        $request_data['winterization'] = $winterization->winterization;
        $request_data['term_and_condition'] = $winterization->term_and_condition;
        $request_data['timezone_id'] = $winterization->timezone_id;
        $model=new AppSetting;
        $model->fill($request_data);
        $model->save();
    }   

    
    /**
     * Create Domain Number
     *
     * @param  mixed $id
     *
     * @return void
     */
    private function createDomainNumber($id)
    {
        $id_length = strlen($id);
        switch ($id_length) {
            case 1:
                $random_string = $this->getRandomString(5).$id;
                break;
            case 2:
                $random_string = $this->getRandomString(4).$id;
                break;
            case 3:
                $random_string = $this->getRandomString(3).$id;
                break;
            case 4:
                $random_string = $this->getRandomString(2).$id;
                break;
            case 5:
                $random_string = $this->getRandomString(1).$id;
                break;
            case 6:
                $random_string = $id;
                break;
            default:
                $random_string = $id;
        }
        return $random_string;
    }

    /**
     * Get Random String
     *
     * @param  mixed $n
     *
     * @return void
     */
    private function getRandomString($n) { 
        $characters = 'abcdefghijklmnopqrstuvwxyz'; 
        $randomString = ''; 
        for ($i = 0; $i < $n; $i++) { 
            $index = rand(0, strlen($characters) - 1); 
            $randomString .= $characters[$index]; 
        } 
        return $randomString; 
    } 
 
    /**
     * Update Company detail with user detail 
     *
     * @param  mixed $request
     * @param  mixed $company
     *
     * @return void
     */
    public function update(Update $request,$company)
    {
        $input_data = $request->only('id','name','email','website','comments','address', PHONE, 'city', 'province', 'country', 'country_code', 'zip', 'logo','pst_number','gst_number');
        if (Company::where('id',$company)->update($input_data)) {
            $request_data = $request->all();
            $data['name'] = $request_data['name'];
            $data['first_name'] = $request_data['first_name'];
            $data['last_name'] = $request_data['last_name'];
            !empty($request_data['logo']) ? $data['company_logo'] = $request_data['logo'] : '';
            !empty($request_data['image']) ? $data['pic_id'] = $request_data['image'] : '';
            User::where('company_id',$company)->first()->update($data);
            $company = Company::where('id',$company)->first();
            return $this->response->item($company, new CompanyTransformer());
        } else {
             return $this->response->errorInternal('Error occurred while saving Company');
        }
    }

    public function destroy(Destroy $request, $company)
    {
       
    }

    /**
     * Create Company Admin in Users table
     *
     * @param  mixed $company_model
     *
     * @return void
     */
    private function createCompanyAdmin($requested_data){
        $user['company_id'] = $requested_data['id'];
        $user['username'] = $requested_data['username'];
        $user['name'] = $requested_data['name'];
        $user['first_name'] = $requested_data['first_name'];
        $user['last_name'] = $requested_data['last_name'];
        $user['email'] = $requested_data['user_email'];
        $user['active'] = 1;
        $user['domain_number'] = $requested_data['domain_number'];
        $user['phone'] = $requested_data['phone'];
        isset($requested_data['logo']) ? $user['company_logo'] = $requested_data['logo'] : '';
        isset($requested_data['office'])? $user['office'] = $requested_data['office'] : '';
        $user_data = User::create($user);
        if($user_data){
            $role['staff'] = $user_data->id;
            $role['role'] = 2;
            StaffRole::create($role);
        }
        return $user_data;
    }
    
    /**
     * Change Company Admin Status
     *
     * @return void
     */
    public function changeCompanyAdminStatus(AdminStatus $request){
        //Get User Detail
        $user = User::where('id',$request->id)->first();
        if($user){
            Company::where('id',$user->company_id)->update(['status' => $request->status]);
            //update User Status
            User::where('company_id',$user->company_id)->whereNotNull('company_id')->update(['active' => $request->status]);
            return $this->response->array(['status' => 200, "message" => 'User status updated successfully.']);
        }
        return $this->response->errorInternal('User not found. Please try again.');
    }

    /**
     * Uploade Company Logo
     *
     * @param  mixed $user_detail
     * @param  mixed $orignal_password
     *
     * @return void
     */
    public function uploadCompanyLogo(ImageUpload $request)
    {
        $request_data =  $request->all();
        $request_data['upload_by'] = Auth::user()->id;
        $request_data['file_type'] = 'image';
        $request_data['object_type'] = 'company_logo';
        $request->has('id') ? $request_data['object_id'] = $request->id : '';
        $file_uploaded = $this->uploadImage($request, $request_data);
        if($file_uploaded){
            $file_uploaded['path'] = env('APP_URL').'/storage/'.$file_uploaded['path'];
            return response()->json(['success' => true, 'message' => 'Compnay logo has been uploaded', 'data' => $file_uploaded], 200);
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
        $destinationPath = 'public/logo/';

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
     * get Company Detail
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function getCompanyDetail(Request $request)
    {
        $company = Company::where('id',Auth::user()->company_id)->first();
        if($company){
            return $this->response->item($company, new CompanyTransformer());  
        }
        return $this->response->errorInternal('Company not found, please try again.');
    }

    /**
     * update Company Detail
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function updateCompanyDetail(UpdateProfile $request)
    {
        $input_data = $request->only('id','email','website','comments','address', PHONE, 'city', 'province', 'country', 'country_code', 'zip','logo','pst_number','gst_number');
        
        if (Company::where('id',Auth::user()->company_id)->update($input_data)) {
            $request_data = $request->all();

           
            !empty($request_data['logo']) ? $data['company_logo'] = $request_data['logo'] : '';
            if(!empty($data))
            {
                User::where('company_id',Auth::user()->company_id)->first()->update($data);
            }

            $company = Company::where('id',Auth::user()->company_id)->first();
            return $this->response->item($company, new CompanyTransformer());
        } else {
             return $this->response->errorInternal('Error occurred while saving Company');
        }

    }

}

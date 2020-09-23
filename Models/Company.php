<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
/**
   @property varchar $name name
@property varchar $address address
@property varchar $email email
@property int $phone phone
@property varchar $city city
@property varchar $province province
@property varchar $country country
@property varchar $zip zip
@property tinyint $status status
@property int $created_by created by
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
@property varchar $url url
@property varchar $website url
@property varchar $logo logo
   
 */
class Company extends Model 
{
    
    /**
    * Database table name
    */
    protected $table = 'companies';

    /**
    * Mass assignable columns
    */
    protected $fillable=[
        'logo',
        'name',
        'email',
        'address',
        'phone',
        'city',
        'province',
        'country',
        'zip',
        'status',
        'created_by',
        'url',
        'logo',
        'domain_number',
        'country_code',
        'comments',
        'website',
        'pst_number',
        'gst_number',
    ];

    //protected $appends = ['timezone'];

    /**
    * Date time columns.
    */
    protected $dates=[];

    //Company relation with User
    public function user(){
        return $this->hasOne(User::class,'company_id','id');
    } 

    //Company relation with File
    public function file(){
        return $this->hasOne(File::class,'id','logo');
    } 

    //Company relation with Staff Role
    public function staffrole(){
        return $this->hasOne(StaffRole::class,'staff','id');
    } 

    public function getTimezoneAttribute()
    {
        $companyId = $this->id;
        $appSettings = AppSetting::select('timezone_id')->with('timezone')->where('company_id', $companyId)->first();
        if($appSettings){
            return $appSettings->timezone->timezone;
        }
        return 'America/Los_Angeles';
    }

}
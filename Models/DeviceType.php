<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Torzer\Awesome\Landlord\BelongsToTenants;
/**
   @property int $company_id company id
@property varchar $device_type device type
@property int $extra_info extra info
@property tinyint $status status
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class DeviceType extends Model 
{
    use BelongsToTenants;
    public $tenantColumns = ['company_id'];
    /**
    * Database table name
    */
    protected $table = 'device_types';
    

    /**
    * Mass assignable columns
    */
    protected $fillable=[
        'company_id',
        'device_type',
        'extra_info',
        'status',
        'form_template_id'
    ];

    /**
    * Date time columns.
    */
    protected $dates=[];

    //Form Template relation with Device Type
    public function formtemplate(){
        return $this->hasOne(FormTemplate::class,'id','form_template_id');
    } 


}
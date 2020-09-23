<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Torzer\Awesome\Landlord\BelongsToTenants;
/**
   @property int $company_id company id
@property varchar $manufacturer manufacturer
@property varchar $model model
@property int $device_type device type
@property smallint $extra_info extra info
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class ManufacturersModel extends Model 
{
    use BelongsToTenants;
    public $tenantColumns = ['company_id'];
    /**
    * Database table name
    */
    protected $table = 'manufacturers_models';

    /**
    * Mass assignable columns
    */
    protected $fillable=[
        'company_id',
        'manufacturer',
        'model',
        'device_type',
        'extra_info',
        'status'
    ];

    /**
    * Date time columns.
    */
    protected $dates=[];

    //Device Manufacturer relation with Manufacturers Model
    public function device(){
        return $this->hasOne(DeviceManufacturer::class,'id','device_type');
    } 


}
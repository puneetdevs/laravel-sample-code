<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Torzer\Awesome\Landlord\BelongsToTenants;
/**
   @property int $company_id company id
@property int $inspection_device_id inspection device id
@property tinyint $defective defective
@property int $created_by created by
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class InspectionDeviceDefectiveDetail extends Model 
{
    use SoftDeletes;
    use BelongsToTenants;
    public $tenantColumns = ['company_id'];
    /**
    * Database table name
    */
    protected $table = 'inspection_device_defective_details';

    /**
    * Mass assignable columns
    */
    protected $fillable=['company_id',
'inspection_device_id',
'defective',
'created_by'];

    /**
    * Date time columns.
    */
    protected $dates=[];




}
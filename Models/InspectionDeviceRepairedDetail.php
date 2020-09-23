<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Torzer\Awesome\Landlord\BelongsToTenants;
/**
   @property int $inspection_device_id inspection device id
@property tinyint $repaired repaired
@property int $created_by created by
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class InspectionDeviceRepairedDetail extends Model 
{
    use SoftDeletes;
    use BelongsToTenants;
    public $tenantColumns = ['company_id'];
    /**
    * Database table name
    */
    protected $table = 'inspection_device_repaired_detail';

    /**
    * Mass assignable columns
    */
    protected $fillable=['company_id',
    'inspection_device_id',
    'repaired',
    'created_by'];

    /**
    * Date time columns.
    */
    protected $dates=[];




}
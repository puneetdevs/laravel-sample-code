<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Torzer\Awesome\Landlord\BelongsToTenants;
/**
   @property int $company_id company id
@property int $inspection_devices_id inspection devices id
@property date $created_date created date
@property varchar $ext ext
@property int $file_id file id
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class InspectionDeviceDeficiencyFile extends Model 
{
    use SoftDeletes;
    use BelongsToTenants;
    public $tenantColumns = ['company_id'];
    /**
    * Database table name
    */
    protected $table = 'inspection_device_deficiency_files';

    /**
    * Mass assignable columns
    */
    protected $fillable=['company_id',
        'inspection_devices_id',
        'created_date',
        'ext',
        'file_id'
    ];

    /**
    * Date time columns.
    */
    protected $dates=['created_date'];

    //File relation with Deficiency File
    public function file(){
        return $this->hasOne(File::class,'id','file_id');
    }
}
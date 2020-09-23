<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Torzer\Awesome\Landlord\BelongsToTenants;
/**
   @property int $company_id company id
@property varchar $identifier identifier
@property int $area_id area id
@property int $inspection_id inspection id
@property varchar $location location
@property int $device_type_id device type id
@property tinyint $tested tested
@property tinyint $no_access no access
@property int $deficiency_type_id deficiency type id
@property varchar $deficiency_detail deficiency detail
@property tinyint $repaired repaired
@property longtext $note note
@property datetime $tested_on tested on
@property int $place_inspection_device_id place inspection device id
@property tinyint $deleted deleted
@property longtext $details details
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class InspectionDevice extends Model 
{
    use SoftDeletes;
    use BelongsToTenants;
    public $tenantColumns = ['company_id'];
    /**
    * Database table name
    */
    protected $table = 'inspection_devices';

    /**
    * Mass assignable columns
    */
    protected $fillable=['company_id',
        'temporary_uid',
        'identifier',
        'area_id',
        'inspection_id',
        'device_number',
        'location',
        'device_type_id',
        'tested',
        'no_access',
        'deficiency_type_id',
        'deficiency_detail',
        'repaired',
        'defective',
        'note',
        'tested_on',
        'place_inspection_device_id',
        'deleted',
        'details',
        'old_details',
        'form_id'
    ];

    /**
    * Date time columns.
    */
    protected $dates=['tested_on'];

    public function getDetailsAttribute($value)
    {
        if( $value && !empty($value)){
            return unserialize($value);
        }
        return $value;
    }

    //Inspection Area with  Inspection Device
    public function inspectionarea(){
        return $this->hasOne(InspectionArea::class,'id','area_id');
    }

    //Device Type with  Inspection Device
    public function devicetype(){
        return $this->hasOne(DeviceType::class,'id','device_type_id');
    }

    //Deficiency Type with  Inspection Device
    public function deficiencytype(){
        return $this->hasOne(DeficiencyType::class,'id','deficiency_type_id');
    }

    //Form template with  Inspection Device
    public function formtemplate(){
        return $this->hasOne(FormTemplate::class,'id','form_id');
    }

    //Tested Detail with  Inspection Device
    public function testeddetail(){
        return $this->hasOne(InspectionDeviceTestedDetail::class,'inspection_device_id','id')->latest();
    }

    //No Access Detail with  Inspection Device
    public function noaccessdetail(){
        return $this->hasOne(InspectionDeviceNoaccessDetail::class,'inspection_device_id','id')->latest();
    }

    //Repaired Detail with  Inspection Device
    public function repaireddetail(){
        return $this->hasOne(InspectionDeviceRepairedDetail::class,'inspection_device_id','id')->latest();
    }

    //Repaired Detail with  Inspection Device
    public function defectivedetail(){
        return $this->hasOne(InspectionDeviceDefectiveDetail::class,'inspection_device_id','id')->latest();
    }

    //file relation with Inspection Device
    public function deficiencyfile(){
        return $this->hasMany(InspectionDeviceDeficiencyFile::class,'inspection_devices_id','id');
    }

    //inspection device notes relation with Inspection Device
    public function inspectiondevicenotes(){
        return $this->hasMany(InspectionDeviceNote::class,'inspection_device_id','id');
    }

}
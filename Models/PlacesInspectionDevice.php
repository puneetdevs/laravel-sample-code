<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Torzer\Awesome\Landlord\BelongsToTenants;
/**
   @property int $company_id company id
@property varchar $identifier identifier
@property int $area_id area id
@property varchar $location location
@property int $device_type_id device type id
@property longtext $note note
@property int $places_inspection_id places inspection id
@property tinyint $deleted deleted
@property longtext $details details
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class PlacesInspectionDevice extends Model 
{
    use SoftDeletes;
    use BelongsToTenants;
    public $tenantColumns = ['company_id'];
    /**
    * Database table name
    */
    protected $table = 'places_inspection_devices';

    /**
    * Mass assignable columns
    */
    protected $fillable=['company_id',
        'identifier',
        'area_id',
        'location',
        'device_type_id',
        'note',
        'places_inspection_id',
        'deleted',
        'form_id',
        'details'
    ];

    /**
    * Date time columns.
    */
    protected $dates=[];

    //Place Inspection Area with Place Inspection Device
    public function placesinspectionarea(){
        return $this->hasOne(PlacesInspectionArea::class,'id','area_id');
    }
    
    //Place Inspection Area with Place Inspection Device
    public function devicetype(){
        return $this->hasOne(DeviceType::class,'id','device_type_id');
    }

}
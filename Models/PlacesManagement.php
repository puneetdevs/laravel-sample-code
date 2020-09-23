<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Torzer\Awesome\Landlord\BelongsToTenants;
use Illuminate\Database\Eloquent\SoftDeletes;
/**
   @property int $company_id company id
@property int $place_id place id
@property int $management_id management id
@property int $type_id type id
@property tinyint $is_default is default
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class PlacesManagement extends Model 
{
    use SoftDeletes;
    use BelongsToTenants;
    public $tenantColumns = ['company_id'];
    /**
    * Database table name
    */
    protected $table = 'places_management';

    /**
    * Mass assignable columns
    */
    protected $fillable=[
        'company_id',
        'place_id',
        'management_id',
        'type_id',
        'is_default'
    ];

    /**
    * Date time columns.
    */
    protected $dates=[];

    //Management relation with Place management
    public function management(){
        return $this->hasOne(Management::class,'id','management_id');
    } 

    //Place relation with Place Management
    public function place(){
        return $this->hasOne(Place::class,'id','place_id');
    } 

    //Type relation with Place management
    public function type(){
        return $this->hasOne(PlacesManagementType::class,'id','type_id');
    } 

    //Invoice with Place management
    public function invoice(){
        return $this->hasMany(Invoice::class,'management_id','id');
    }

    public function payment(){
        return $this->hasMany(Payment::class,'management_id','id');
    }
    
}
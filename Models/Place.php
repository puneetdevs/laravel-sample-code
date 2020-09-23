<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Torzer\Awesome\Landlord\BelongsToTenants;
/**
@property int $company_id company id
@property varchar $number number
@property varchar $name name
@property varchar $suite suite
@property varchar $street_number street number
@property varchar $street_name street name
@property int $street_type_id street type id
@property int $city_id city id
@property int $state_id state id
@property int $country_id country id
@property varchar $zip zip
@property varchar $district district
@property varchar $zone zone
@property int $place_type_id place type id
@property varchar $phone phone
@property varchar $fax fax
@property varchar $key key
@property longtext $notes notes
@property tinyint $on_hold on hold
@property tinyint $active active
@property int $bartec_number bartec number
@property tinyint $cod cod
@property tinyint $no_work no work
@property longtext $special_instructions special instructions
@property longtext $technician_information technician information
@property tinyint $winterize winterize
@property tinyint $collections collections
@property tinyint $alert alert
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
 */
class Place extends Model 
{
    use BelongsToTenants;
    public $tenantColumns = ['company_id'];
    /**
    * Database table name
    */
    protected $table = 'places';

    /**
    * Mass assignable columns
    */
    protected $fillable=[
        'company_id',
        'number',
        'name',
        'suite',
        'street_number',
        'street_name',
        'street_type_id',
        'city_id',
        'state_id',
        'country_id',
        'zip',
        'district',
        'zone',
        'place_type_id',
        'phone',
        'fax',
        'key',
        'notes',
        'on_hold',
        'active',
        'bartec_number',
        'cod',
        'no_work',
        'special_instructions',
        'technician_information',
        'winterize',
        'collections',
        'alert',
        'is_default'
    ];

    /**
    * Date time columns.
    */
    protected $dates=[];

    //City relation with Place
    public function city(){
        return $this->hasOne(City::class,'id','city_id');
    } 
    //Place Type with Place
    public function type(){
        return $this->hasOne(StreetType::class,'id','street_type_id');
    } 

    //Place Type with Place
    public function placetype(){
        return $this->hasOne(PlaceType::class,'id','place_type_id');
    } 

    //Street relation with Place
    public function street(){
        return $this->hasOne(StreetType::class,'id','street_type_id');
    } 

    //Place Management relation with Place
    public function management(){
        return $this->hasMany(PlacesManagement::class,'place_id','id');
    } 

    //Place Contact relation with Place
    public function contact(){
        return $this->hasMany(PlacesContact::class,'place_id','id');
    } 
    
    //Monitoring relation with Place
    public function monitoring(){
        return $this->hasMany(Monitoring::class,'place_id','id');
    } 

}
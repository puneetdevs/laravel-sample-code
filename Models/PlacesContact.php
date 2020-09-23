<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Torzer\Awesome\Landlord\BelongsToTenants;
use Illuminate\Database\Eloquent\SoftDeletes;
/**
@property int $company_id company id
@property int $place_id place id
@property int $contact_id contact id
@property int $type_id type id
@property tinyint $is_default is default
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class PlacesContact extends Model 
{
    use SoftDeletes;
    use BelongsToTenants;
    public $tenantColumns = ['company_id'];
    /**
    * Database table name
    */
    protected $table = 'places_contacts';

    /**
    * Mass assignable columns
    */
    protected $fillable=[
        'company_id',
        'place_id',
        'contact_id',
        'type_id',
        'is_default'
    ];

    /**
    * Date time columns.
    */
    protected $dates=[];
    
    //Contact relation with Place Contact
    public function contact(){
        return $this->hasOne(Contact::class,'id','contact_id');
    } 

    //Place relation with Place Contact
    public function place(){
        return $this->hasOne(Place::class,'id','place_id');
    } 

    //Type relation with Place Contact
    public function type(){
        return $this->hasOne(ContactType::class,'id','type_id');
    } 
}
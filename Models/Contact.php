<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Torzer\Awesome\Landlord\BelongsToTenants;

/**
@property int $company_id company id
@property varchar $name name
@property varchar $phone phone
@property varchar $fax fax
@property varchar $email email
@property longtext $address address
@property longtext $contact contact
@property tinyint $active active
@property varchar $phone_extension phone extension
@property varchar $pager_extension pager extension
@property varchar $pager pager
@property varchar $mobile mobile
@property int $bartec_number bartec number
@property varchar $suite suite
@property varchar $buzzer buzzer
@property varchar $website website
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class Contact extends Model 
{
    use SoftDeletes;
    use BelongsToTenants;
    public $tenantColumns = ['company_id'];
    /**
    * Database table name
    */
    protected $table = 'contacts';

    /**
    * Mass assignable columns
    */
    protected $fillable=[
        'company_id',
        'name',
        'phone',
        'fax',
        'email',
        'address',
        'contact',
        'active',
        'phone_extension',
        'pager_extension',
        'pager',
        'mobile',
        'bartec_number',
        'suite',
        'buzzer',
        'website'
    ];

    /**
    * Date time columns.
    */
    protected $dates=[];

    //Place Contact relation with Contact
    public function place(){
        return $this->hasMany(PlacesContact::class,'contact_id','id');
    } 

    //Management Contact relation with Management
    public function management(){
        return $this->hasMany(ManagementContact::class,'management_id','id');
    } 


}
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Torzer\Awesome\Landlord\BelongsToTenants;
/**
@property int $company_id company id
@property varchar $name name
@property varchar $phone phone
@property varchar $fax fax
@property varchar $email email
@property varchar $website website
@property longtext $address address
@property longtext $contact contact
@property tinyint $on_hold on hold
@property longtext $notes notes
@property tinyint $active active
@property tinyint $cod cod
@property tinyint $no_work no work
@property varchar $mobile mobile
@property int $bartec_number bartec number
@property tinyint $charge_gst charge gst
@property tinyint $charge_pst charge pst
@property tinyint $collections collections
@property tinyint $alert alert
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class Management extends Model 
{
    use BelongsToTenants;
    public $tenantColumns = ['company_id'];
    /**
    * Database table name
    */
    protected $table = 'management';

    /**
    * Mass assignable columns
    */
    protected $fillable=[
        'company_id',
        'name',
        'phone',
        'fax',
        'email',
        'website',
        'address',
        'contact',
        'on_hold',
        'notes',
        'active',
        'cod',
        'no_work',
        'mobile',
        'bartec_number',
        'charge_gst',
        'charge_pst',
        'collections',
        'alert'
    ];

    /**
    * Date time columns.
    */
    protected $dates=[];

    //Place Management relation with Management
    public function management(){
        return $this->hasMany(PlacesManagement::class,'management_id','id');
    } 

    //Contact relation with Management
    public function managementcontact(){
        return $this->hasMany(ManagementContact::class,'management_id','id');
    } 

    public function placemanagement(){
        return $this->hasOne(PlacesManagement::class,'management_id','id');
    }
}
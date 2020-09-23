<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Torzer\Awesome\Landlord\BelongsToTenants;
use Illuminate\Database\Eloquent\SoftDeletes;
/**
   @property int $company_id company id
@property int $management_id management id
@property int $contact_id contact id
@property int $type_id type id
@property tinyint $is_default is default
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class ManagementContact extends Model 
{
    use SoftDeletes;
    use BelongsToTenants;
    public $tenantColumns = ['company_id'];
    /**
    * Database table name
    */
    protected $table = 'management_contacts';

    /**
    * Mass assignable columns
    */
    protected $fillable=[
        'company_id',
        'management_id',
        'contact_id',
        'type_id',
        'is_default'
    ];

    /**
    * Date time columns.
    */
    protected $dates=[];

    //Contact relation with Management Contact
    public function contact(){
        return $this->hasOne(Contact::class,'id','contact_id');
    } 

    //Management relation with Management Contact
    public function contactmanagement(){
        return $this->hasOne(Management::class,'id','management_id');
    } 

    //Type relation with Management Contact
    public function type(){
        return $this->hasOne(ContactType::class,'id','type_id');
    } 


}
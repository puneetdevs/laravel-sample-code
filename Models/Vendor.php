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
@property varchar $website website
@property longtext $address address
@property longtext $contact contact
@property longtext $notes notes
@property tinyint $active active
@property longtext $bill_address bill address
@property int $bartec_number bartec number
@property varchar $phone2 phone2
@property longtext $head_office head office
@property tinyint $account account
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class Vendor extends Model 
{
    use SoftDeletes;
    use BelongsToTenants;
    public $tenantColumns = ['company_id'];
    
    /**
    * Database table name
    */
    protected $table = 'vendors';

    /**
    * Mass assignable columns
    */
    protected $fillable=['company_id',
        'name',
        'phone',
        'phone_ext',
        'phone2_ext',
        'fax',
        'email',
        'website',
        'address',
        'contact',
        'notes',
        'active',
        'bill_address',
        'bartec_number',
        'phone2',
        'head_office',
        'account'
    ];

    /**
    * Date time columns.
    */
    protected $dates=[];




}
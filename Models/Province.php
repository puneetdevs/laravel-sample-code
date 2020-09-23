<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Torzer\Awesome\Landlord\BelongsToTenants;
/**
   @property int $country_id country id
@property varchar $state state
@property varchar $state_code state code
@property tinyint $active active
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class Province extends Model 
{
    use BelongsToTenants;
    public $tenantColumns = ['company_id'];
    /**
    * Database table name
    */
    protected $table = 'province';

    /**
    * Mass assignable columns
    */
    protected $fillable=[
        'company_id',
        'country_id',
        'state',
        'state_code',
        'active'
    ];

    /**
    * Date time columns.
    */
    protected $dates=[];

    //Province relation with Country
    public function country(){
        return $this->hasOne(Country::class,'id','country_id');
    } 

}
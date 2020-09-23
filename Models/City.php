<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Torzer\Awesome\Landlord\BelongsToTenants;
/**
   @property int $company_id company id
@property int $state_id state id
@property varchar $city city
@property char $color color
@property tinyint $active active
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class City extends Model 
{
    use BelongsToTenants;
    public $tenantColumns = ['company_id'];
    
    /**
    * Database table name
    */
    protected $table = 'cities';

    /**
    * Mass assignable columns
    */
    protected $fillable=[
        'company_id',
        'state_id',
        'city',
        'color',
        'active',
        'created_by',
        'updated_by'
    ];

    /**
    * Date time columns.
    */
    protected $dates=[];

    //City relation with Province
    public function province(){
        return $this->hasOne(Province::class,'id','state_id');
    } 

}
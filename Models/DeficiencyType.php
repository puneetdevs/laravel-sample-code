<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Torzer\Awesome\Landlord\BelongsToTenants;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
   @property int $company_id company id
@property varchar $deficiency_type deficiency type
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class DeficiencyType extends Model 
{
    use SoftDeletes;
    use BelongsToTenants;
    public $tenantColumns = ['company_id'];
    /**
    * Database table name
    */
    protected $table = 'deficiency_types';

    /**
    * Mass assignable columns
    */
    protected $fillable=[
        'company_id',
        'deficiency_type',
        'status'
    ];

    /**
    * Date time columns.
    */
    protected $dates=[];




}
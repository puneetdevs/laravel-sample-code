<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Torzer\Awesome\Landlord\BelongsToTenants;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
   @property int $company_id company id
@property varchar $panel panel
@property tinyint $active active
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class Panel extends Model 
{
    use SoftDeletes;
    use BelongsToTenants;
    public $tenantColumns = ['company_id'];
    /**
    * Database table name
    */
    protected $table = 'panels';

    /**
    * Mass assignable columns
    */
    protected $fillable=[
        'company_id',
        'panel',
        'active'
    ];

    /**
    * Date time columns.
    */
    protected $dates=[];
}
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Torzer\Awesome\Landlord\BelongsToTenants;
/**
   @property int $company_id company id
@property int $inspection_id inspection id
@property varchar $area area
@property int $place_inspection_area_id place inspection area id
@property tinyint $deleted deleted
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class InspectionArea extends Model 
{
    use SoftDeletes;
    use BelongsToTenants;
    public $tenantColumns = ['company_id']; 
    /**
    * Database table name
    */
    protected $table = 'inspection_areas';

    /**
    * Mass assignable columns
    */
    protected $fillable=['company_id',
        'temporary_uid',
        'inspection_id',
        'area',
        'place_inspection_area_id',
        'deleted'
    ];

    /**
    * Date time columns.
    */
    protected $dates=[];




}
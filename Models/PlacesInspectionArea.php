<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Torzer\Awesome\Landlord\BelongsToTenants;

/**
   @property int $company_id company id
@property int $places_inspection_id places inspection id
@property varchar $area area
@property tinyint $deleted deleted
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class PlacesInspectionArea extends Model 
{
    use SoftDeletes;
    use BelongsToTenants;
    public $tenantColumns = ['company_id'];
    /**
    * Database table name
    */
    protected $table = 'places_inspection_areas';

    /**
    * Mass assignable columns
    */
    protected $fillable=['company_id',
        'places_inspection_id',
        'area',
        'deleted'
    ];

    /**
    * Date time columns.
    */
    protected $dates=[];




}
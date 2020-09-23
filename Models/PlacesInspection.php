<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Torzer\Awesome\Landlord\BelongsToTenants;

/**
   @property int $company_id company id
@property int $place_id place id
@property date $due due
@property longtext $note note
@property int $inspection_type_id inspection type id
@property longtext $technician_notes technician notes
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class PlacesInspection extends Model 
{
    use SoftDeletes;
    use BelongsToTenants;
    public $tenantColumns = ['company_id'];
    /**
    * Database table name
    */
    protected $table = 'places_inspections';

    /**
    * Mass assignable columns
    */
    protected $fillable=['company_id',
        'place_id',
        'due',
        'note',
        'inspection_type_id',
        'technician_notes'
    ];

    /**
    * Date time columns.
    */
    protected $dates=['due'];

    //inspection type with Place Inspection
    public function inspectiontype(){
        return $this->hasOne(InspectionType::class,'id','inspection_type_id');
    } 

    //Place with Place Inspection
    public function place(){
        return $this->hasOne(Place::class,'id','place_id');
    } 

}
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Torzer\Awesome\Landlord\BelongsToTenants;
/**
   @property int $company_id company id
@property int $place_id place id
@property int $inspection_type_id inspection type id
@property int $inspection_status_id inspection status id
@property date $inspection_date inspection date
@property int $checked_out_by_id checked out by id
@property datetime $checked_out_date checked out date
@property date $completed_date completed date
@property date $submitted_date submitted date
@property date $created_date created date
@property int $place_inspection_id place inspection id
@property datetime $modified_timestamp modified timestamp
@property int $tech1_id tech1 id
@property int $tech2_id tech2 id
@property int $tech3_id tech3 id
@property int $workorder_id workorder id
@property varchar $number number
@property longtext $notes notes
@property longtext $technician_notes technician notes
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */

class Inspection extends Model 
{
    use SoftDeletes;
    use BelongsToTenants;
    public $tenantColumns = ['company_id'];
    /**
    * Database table name
    */
    protected $table = 'inspections';

    /**
    * Mass assignable columns
    */
    protected $fillable=['company_id',
        'place_id',
        'inspection_type_id',
        'inspection_status_id',
        'inspection_date',
        'checked_out_by_id',
        'checked_out_date',
        'completed_date',
        'submitted_date',
        'created_date',
        'place_inspection_id',
        'modified_timestamp',
        'tech1_id',
        'tech2_id',
        'tech3_id',
        'workorder_id',
        'number',
        'notes',
        'technician_notes'
    ];

    /**
    * Date time columns.
    */
    protected $dates=['inspection_date',
        'checked_out_date',
        'completed_date',
        'submitted_date',
        'created_date',
        'modified_timestamp'
    ];

    //Inspection Status relation with Inspection
    public function status(){
        return $this->hasOne(InspectionStatus::class,'id','inspection_status_id');
    } 

    //Inspection Type relation with Inspection
    public function type(){
        return $this->hasOne(InspectionType::class,'id','inspection_type_id');
    }
    
    //Place relation with Inspection
    public function place(){
        return $this->hasOne(Place::class,'id','place_id');
    }

    //Technician 1 relation with Inspection
    public function technician1(){
        return $this->hasOne(User::class,'id','tech1_id');
    }

    //Technician 2 relation with Inspection
    public function technician2(){
        return $this->hasOne(User::class,'id','tech2_id');
    }

    //Technician 3 relation with Inspection
    public function technician3(){
        return $this->hasOne(User::class,'id','tech3_id');
    }

    //Inspection Area relation with Inspection
    public function area(){
        return $this->hasMany(InspectionArea::class,'inspection_id','id');
    }

    //Inspection Device relation with Inspection
    public function device(){
        return $this->hasMany(InspectionDevice::class,'inspection_id','id');
    }

}
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Torzer\Awesome\Landlord\BelongsToTenants;
use Illuminate\Database\Eloquent\SoftDeletes;
/**
@property int $company_id company id
@property int $place_communication_type_id place communication type id
@property date $due_date due date
@property date $date date
@property date $contract_expires contract expires
@property smallint $advance_months advance months
@property decimal $price price
@property longtext $notes notes
@property int $place_id place id
@property decimal $hours hours
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class PlaceCommunication extends Model 
{
    use SoftDeletes;
    use BelongsToTenants;
    public $tenantColumns = ['company_id'];
    /**
    * Database table name
    */
    protected $table = 'place_communications';

    /**
    * Mass assignable columns
    */
    protected $fillable=[
        'company_id',
        'place_communication_type_id',
        'due_date',
        'date',
        'contract_expires',
        'advance_months',
        'price',
        'notes',
        'place_id',
        'hours'
    ];

    /**
    * Date time columns.
    */
    protected $dates=[
        'due_date',
        'date',
        'contract_expires'
    ];

    //Place Communication Type relation with Place Communication
    public function type(){
        return $this->hasOne(PlaceCommunicationType::class,'id','place_communication_type_id');
    } 

    //Place Communication Type relation with Place Communication
    public function place(){
        return $this->hasOne(Place::class,'id','place_id');
    } 

}
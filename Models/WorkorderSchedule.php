<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Torzer\Awesome\Landlord\BelongsToTenants;
use Illuminate\Database\Eloquent\SoftDeletes;
/**
   @property int $company_id company id
@property int $workorder_id workorder id
@property int $staff_id staff id
@property int $minutes minutes
@property int $hours hours
@property date $start_date start date
@property time $start_time start time
@property datetime $start_timestamp start timestamp
@property datetime $end_timestamp end timestamp
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class WorkorderSchedule extends Model 
{
    use SoftDeletes;
    use BelongsToTenants;
    public $tenantColumns = ['company_id'];
    /**
    * Database table name
    */
    protected $table = 'workorder_schedules';

    /**
    * Mass assignable columns
    */
    protected $fillable=['company_id',
'workorder_id',
'staff_id',
'minutes',
'hours',
'start_date',
'start_time',
'start_timestamp',
'end_timestamp'];

    /**
    * Date time columns.
    */
    protected $dates=[

    'start_timestamp',
    'end_timestamp'];

    //Workorder Time relation with User as Staff
    public function staff(){
        return $this->hasOne(User::class,'id','staff_id');
    } 
    //Workorder Time relation with User as Staff
    public function workorder(){
        return $this->hasOne(Workorder::class,'id','workorder_id');
    } 
    

}
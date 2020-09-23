<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Torzer\Awesome\Landlord\BelongsToTenants;
use Illuminate\Database\Eloquent\SoftDeletes;
/**
@property int $company_id company id
@property int $workorder_id workorder id
@property int $staff_id staff id
@property int $time time
@property varchar $code code
@property tinyint $verified verified
@property date $date date
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class WorkorderTime extends Model 
{
    use SoftDeletes;
    use BelongsToTenants;
    public $tenantColumns = ['company_id'];
    /**
    * Database table name
    */
    protected $table = 'workorder_time';

    /**
    * Mass assignable columns
    */
    protected $fillable=[
        'company_id',
        'workorder_id',
        'staff_id',
        'time',
        'code',
        'verified',
        'date',
        'created_by_id'
    ];

    /**
    * Date time columns.
    */
    protected $dates=[];

    //Workorder Time relation with User as Staff
    public function staff(){
        return $this->hasOne(User::class,'id','staff_id');
    } 

}
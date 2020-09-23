<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Torzer\Awesome\Landlord\BelongsToTenants;
use Illuminate\Database\Eloquent\SoftDeletes;
/**
   @property int $company_id company id
@property longtext $note note
@property date $start_date start date
@property date $end_date end date
@property longtext $staff_list staff list
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class ScheduleNote extends Model 
{
    use BelongsToTenants;
    use SoftDeletes;
    public $tenantColumns = ['company_id'];
    /**
    * Database table name
    */
    protected $table = 'schedule_notes';

    /**
    * Mass assignable columns
    */
    protected $fillable=['company_id',
        'note',
        'start_date',
        'end_date',
        'staff_list',
    ];

    /**
    * Date time columns.
    */
  



}
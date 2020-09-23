<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Torzer\Awesome\Landlord\BelongsToTenants;
/**
   @property int $company_id company id
@property varchar $send_to_email send to email
@property int $send_to_id send to id
@property int $send_by_id send by id
@property varchar $subject subject
@property text $message message
@property varchar $notification_type notification type
@property tinyint $status status
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class EmailLogging extends Model 
{
    use BelongsToTenants;
    public $tenantColumns = ['company_id'];
    /**
    * Database table name
    */
    protected $table = 'email_logging';

    /**
    * Mass assignable columns
    */
    protected $fillable=['company_id',
'send_to_email',
'send_to_id',
'send_by_id',
'subject',
'message',
'notification_type',
'status'];

    /**
    * Date time columns.
    */
    protected $dates=[];




}
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
/**
   @property varchar $template_name template name
@property varchar $subject subject
@property text $message message
@property tinyint $status status
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class EmailNotification extends Model 
{
    
    /**
    * Database table name
    */
    protected $table = 'email_notifications';

    /**
    * Mass assignable columns
    */
    protected $fillable=['template_name',
        'subject',
        'message',
        'status'
    ];

    /**
    * Date time columns.
    */
    protected $dates=[];




}
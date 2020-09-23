<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
/**
   @property varchar $workorder_status workorder status
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class WorkorderStatus extends Model 
{
    
    /**
    * Database table name
    */
    protected $table = 'workorder_statuses';

    /**
    * Mass assignable columns
    */
    protected $fillable=['workorder_status'];

    /**
    * Date time columns.
    */
    protected $dates=[];




}
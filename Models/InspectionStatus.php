<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
/**
   @property varchar $inspection_status inspection status
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class InspectionStatus extends Model 
{
    
    /**
    * Database table name
    */
    protected $table = 'inspection_statuses';

    /**
    * Mass assignable columns
    */
    protected $fillable=['inspection_status'];

    /**
    * Date time columns.
    */
    protected $dates=[];




}
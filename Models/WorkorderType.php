<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
/**
   @property varchar $workorder_type workorder type
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class WorkorderType extends Model 
{
    
    /**
    * Database table name
    */
    protected $table = 'workorder_types';

    /**
    * Mass assignable columns
    */
    protected $fillable=['workorder_type'];

    /**
    * Date time columns.
    */
    protected $dates=[];




}
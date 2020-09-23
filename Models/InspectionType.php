<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
/**
   @property varchar $inspection_type inspection type
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class InspectionType extends Model 
{
    
    /**
    * Database table name
    */
    protected $table = 'inspection_types';

    /**
    * Mass assignable columns
    */
    protected $fillable=['inspection_type'];

    /**
    * Date time columns.
    */
    protected $dates=[];




}
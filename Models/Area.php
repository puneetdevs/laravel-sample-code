<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
/**
   @property varchar $area area
@property tinyint $status status
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class Area extends Model 
{
    
    /**
    * Database table name
    */
    protected $table = 'area';

    /**
    * Mass assignable columns
    */
    protected $fillable=['area',
'status'];

    /**
    * Date time columns.
    */
    protected $dates=[];




}
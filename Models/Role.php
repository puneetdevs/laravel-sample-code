<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
/**
   @property varchar $role role
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class Role extends Model 
{
    
    /**
    * Database table name
    */
    protected $table = 'roles';

    /**
    * Mass assignable columns
    */
    protected $fillable=['role'];

    /**
    * Date time columns.
    */
    protected $dates=[];




}
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
/**
   @property int $role role
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class StaffRole extends Model 
{
    
    /**
    * Database table name
    */
    protected $table = 'staff_roles';

    /**
    * Mass assignable columns
    */
    protected $fillable=['staff','role'];

    /**
    * Date time columns.
    */
    protected $dates=[];




}
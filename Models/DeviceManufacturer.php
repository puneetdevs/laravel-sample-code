<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
/**
   @property varchar $manufacturer manufacturer
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class DeviceManufacturer extends Model 
{
    
    /**
    * Database table name
    */
    protected $table = 'device_manufacturers';

    /**
    * Mass assignable columns
    */
    protected $fillable=['manufacturer'];

    /**
    * Date time columns.
    */
    protected $dates=[];




}
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
/**
   @property varchar $street_type street type
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class StreetType extends Model 
{
    
    /**
    * Database table name
    */
    protected $table = 'street_types';

    /**
    * Mass assignable columns
    */
    protected $fillable=['street_type'];

    /**
    * Date time columns.
    */
    protected $dates=[];




}
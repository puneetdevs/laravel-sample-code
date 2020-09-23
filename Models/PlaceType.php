<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
/**
   @property varchar $place_type place type
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class PlaceType extends Model 
{
    
    /**
    * Database table name
    */
    protected $table = 'place_types';

    /**
    * Mass assignable columns
    */
    protected $fillable=['place_type'];

    /**
    * Date time columns.
    */
    protected $dates=[];




}
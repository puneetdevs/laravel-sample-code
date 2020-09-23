<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
/**
   @property varchar $place_communication_type place communication type
@property smallint $advance_months advance months
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class PlaceCommunicationType extends Model 
{
    
    /**
    * Database table name
    */
    protected $table = 'place_communication_types';

    /**
    * Mass assignable columns
    */
    protected $fillable=['place_communication_type',
'advance_months'];

    /**
    * Date time columns.
    */
    protected $dates=[];




}
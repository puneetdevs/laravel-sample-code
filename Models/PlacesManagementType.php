<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
/**
   @property int $company_id company id
@property varchar $place_management_type place management type
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class PlacesManagementType extends Model 
{
    
    /**
    * Database table name
    */
    protected $table = 'places_management_types';

    /**
    * Mass assignable columns
    */
    protected $fillable=[
        'place_management_type'
    ];

    /**
    * Date time columns.
    */
    protected $dates=[];




}
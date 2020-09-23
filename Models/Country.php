<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
/**
   @property varchar $country_code country code
@property varchar $country country
   
 */
class Country extends Model 
{
    
    /**
    * Database table name
    */
    protected $table = 'countries';

    /**
    * Mass assignable columns
    */
    protected $fillable=[
        'country',
        'country_code'
    ];

    /**
    * Date time columns.
    */
    protected $dates=[];




}
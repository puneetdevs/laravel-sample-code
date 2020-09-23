<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
/**
   @property varchar $timezone timezone
@property datetime $created_date created date
@property datetime $updated_date updated date
   
 */
class Timezone extends Model 
{
    
    /**
    * Database table name
    */
    protected $table = 'timezones';

    /**
    * Mass assignable columns
    */
    protected $fillable=['updated_date',
        'timezone',
        'created_date',
        'updated_date'
    ];

    /**
    * Date time columns.
    */
    protected $dates=['created_date',
'updated_date'];




}
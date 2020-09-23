<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
/**
   @property varchar $quote_status quote status
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class QuoteStatus extends Model 
{
    
    /**
    * Database table name
    */
    protected $table = 'quote_statuses';

    /**
    * Mass assignable columns
    */
    protected $fillable=['quote_status'];

    /**
    * Date time columns.
    */
    protected $dates=[];
}
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
/**
@property varchar $contact_type contact type
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class ContactType extends Model 
{
    
    /**
    * Database table name
    */
    protected $table = 'contact_types';

    /**
    * Mass assignable columns
    */
    protected $fillable=['contact_type'];

    /**
    * Date time columns.
    */
    protected $dates=[];

}
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
/**
   @property varchar $file_type file type
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class FileType extends Model 
{
    
    /**
    * Database table name
    */
    protected $table = 'file_types';

    /**
    * Mass assignable columns
    */
    protected $fillable=['file_type'];

    /**
    * Date time columns.
    */
    protected $dates=[];




}
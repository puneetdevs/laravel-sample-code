<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
/**
   @property varchar $path path
@property varchar $file_name file name
@property varchar $file_type file type
@property varchar $object_type object type
@property int $object_id object id
@property int $upload_by upload by
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class File extends Model 
{
    
    /**
    * Database table name
    */
    protected $table = 'files';

    /**
    * Mass assignable columns
    */
    protected $fillable=['path',
'file_name',
'file_type',
'object_type',
'object_id',
'upload_by'];

    /**
    * Date time columns.
    */
    protected $dates=[];




}
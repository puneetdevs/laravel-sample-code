<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
/**
   @property varchar $form_name form name
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class FormTemplate extends Model 
{
    
    /**
    * Database table name
    */
    protected $table = 'form_template';

    /**
    * Mass assignable columns
    */
    protected $fillable=['form_name'];

    /**
    * Date time columns.
    */
    protected $dates=[];

    //Place relation with Inspection
    public function inspection_devices(){
        return $this->hasMany(InspectionDevice::class,'form_id','id');
    }

}
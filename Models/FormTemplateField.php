<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
/**
   @property int $model_id model id
@property varchar $field_name field name
@property varchar $field_type field type
@property text $field_value field value
@property varchar $field_lable field lable
@property text $tooltip_message tooltip message
@property varchar $tab_name tab name
@property varchar $field_heading field heading
@property tinyint $value_is_api value is api
@property varchar $api_url api url
@property tinyint $active active
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class FormTemplateField extends Model 
{
    use SoftDeletes;
    /**
    * Database table name
    */
    protected $table = 'form_template_fields';

    /**
    * Mass assignable columns
    */
    protected $fillable=[
        'form_id',
        'field_name',
        'field_type',
        'field_value',
        'field_lable',
        'tooltip_message',
        'tab_name',
        'field_heading',
        'value_is_api',
        'api_url',
        'active'
    ];

    /**
    * Date time columns.
    */
    protected $dates=[];




}
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
/**
   @property int $company_id company id
@property varchar $code code
@property varchar $description description
@property varchar $code_heading code heading
@property tinyint $active active
@property datetime $created_at created at
@property datetime $updated_at updated at
   
 */
class TimeCode extends Model 
{
    use SoftDeletes;

    /**
    * Database table name
    */
    protected $table = 'time_codes';

    /**
    * Mass assignable columns
    */
    protected $fillable=['company_id',
        'code',
        'description',
        'code_heading',
        'active'
    ];

    /**
    * Date time columns.
    */
    protected $dates=[];




}
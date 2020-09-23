<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
   @property int $company_id company id
@property varchar $company_name company name
@property varchar $company_email company email
@property varchar $company_address1 company address1
@property varchar $company_address2 company address2
@property int $company_city company city
@property int $company_country company country
@property int $company_phone company phone
@property int $company_fax company fax
@property text $winterization winterization
@property varchar $tax_name_1 tax name 1
@property float $tax_rate_1 tax rate 1
@property varchar $tax_name_2 tax name 2
@property float $tax_rate_2 tax rate 2
@property varchar $currency currency
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class AppSetting extends Model 
{
    public $tenantColumns = ['company_id'];

    /**
    * Database table name
    */
    protected $table = 'app_settings';

    /**
    * Mass assignable columns
    */
    protected $fillable=['company_id',
        'company_name',
        'company_email',
        'company_address1',
        'company_address2',
        'company_city',
        'company_country',
        'company_phone',
        'company_fax',
        'winterization',
        'tax_name_1',
        'tax_rate_1',
        'tax_name_2',
        'tax_rate_2',
        'currency',
        'term_and_condition',
        'timezone_id'
    ];

    /**
    * Date time columns.
    */
    protected $dates=[];

    //Timezone relation with App Settings
    public function timezone(){
        return $this->hasOne(Timezone::class,'id','timezone_id');
    } 


}
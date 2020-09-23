<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Torzer\Awesome\Landlord\BelongsToTenants;
use Illuminate\Database\Eloquent\SoftDeletes;
/**
@property int $company_id company id
@property int $place_id place id
@property int $bill_to_id bill to id
@property varchar $account account
@property varchar $certificate certificate
@property longtext $contacts contacts
@property varchar $cs_number cs number
@property smallint $billing_months billing months
@property decimal $billing_amount billing amount
@property datetime $install_date install date
@property datetime $removal_date removal date
@property datetime $next_billing_date next billing date
@property int $last_invoice_id last invoice id
@property varchar $dialer_number1 dialer number1
@property varchar $dialer_number2 dialer number2
@property varchar $dialer_type dialer type
@property longtext $notes notes
@property tinyint $ulc ulc
@property tinyint $test_jan test jan
@property tinyint $test_feb test feb
@property tinyint $test_mar test mar
@property tinyint $test_apr test apr
@property tinyint $test_may test may
@property tinyint $test_jun test jun
@property tinyint $test_jul test jul
@property tinyint $test_aug test aug
@property tinyint $test_sep test sep
@property tinyint $test_oct test oct
@property tinyint $test_nov test nov
@property tinyint $test_dec test dec
@property tinyint $ten_digit_programmed ten digit programmed
@property tinyint $elevator elevator
@property tinyint $fire_alarm fire alarm
@property tinyint $other other
@property datetime $created_date created date
@property varchar $sec_account sec account
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class Monitoring extends Model 
{
    use SoftDeletes;
    use BelongsToTenants;
    public $tenantColumns = ['company_id'];
    /**
    * Database table name
    */
    protected $table = 'monitoring';

    /**
    * Mass assignable columns
    */
    protected $fillable=[
        'company_id',
        'place_id',
        'bill_to_id',
        'account',
        'certificate',
        'contacts',
        'cs_number',
        'billing_months',
        'billing_amount',
        'install_date',
        'removal_date',
        'next_billing_date',
        'last_invoice_id',
        'dialer_number1',
        'dialer_number2',
        'dialer_type',
        'notes',
        'ulc',
        'test_jan',
        'test_feb',
        'test_mar',
        'test_apr',
        'test_may',
        'test_jun',
        'test_jul',
        'test_aug',
        'test_sep',
        'test_oct',
        'test_nov',
        'test_dec',
        'ten_digit_programmed',
        'elevator',
        'fire_alarm',
        'other',
        'created_date',
        'sec_account'
    ];

    /**
    * Date time columns.
    */
    protected $dates=[
        'install_date',
        'removal_date',
        'next_billing_date',
        'created_date'
    ];

    //Billto relation with Monitoring
    public function billto(){  
        return $this->hasOne(PlacesManagement::class,'id','bill_to_id');
        
    } 

    //Place relation with Monitoring
    public function place(){
        return $this->hasOne(Place::class,'id','place_id');
    } 

    //Invoice relation with Monitoring
    public function invoice(){
        return $this->hasOne(Invoice::class,'monitoring_id','id');
    } 

    //Invoice relation with Monitoring
    public function monitoringinvoice(){
        return $this->hasOne(Invoice::class,'id','last_invoice_id');
    } 

}
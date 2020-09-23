<?php
namespace App\Models;

use Auth;
use App\Models\AppSetting;
use Illuminate\Database\Eloquent\Model;
use Torzer\Awesome\Landlord\BelongsToTenants;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
@property int $company_id company id
@property varchar $number number
@property datetime $date date
@property int $place_id place id
@property int $bill_to_id bill to id
@property int $management_id management id
@property longtext $notes notes
@property varchar $purchase_order purchase order
@property int $quote_id quote id
@property int $workorder_id workorder id
@property decimal $subtotal subtotal
@property decimal $pst pst
@property decimal $gst gst
@property decimal $total total
@property tinyint $override_gst override gst
@property tinyint $override_pst override pst
@property datetime $posted_date posted date
@property datetime $created_date created date
@property int $created_by_id created by id
@property int $bartec_number bartec number
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class Invoice extends Model 
{
    use SoftDeletes;
    use BelongsToTenants;
    public $tenantColumns = ['company_id'];
    
    /**
    * Database table name
    */
    protected $table = 'invoices';

    /**
    * Mass assignable columns
    */
    protected $fillable=[
        'company_id',
        'number',
        'date',
        'place_id',
        'bill_to_id',
        'management_id',
        'notes',
        'purchase_order',
        'quote_id',
        'workorder_id',
        'monitoring_id',
        'subtotal',
        'pst',
        'gst',
        'total',
        'due',
        'override_gst',
        'override_pst',
        'posted',
        'posted_date',
        'created_date',
        'created_by_id',
        'bartec_number'
    ];

    /**
    * Date time columns.
    */
    protected $dates=[
        'date',
        'posted_date',
        'created_date'
    ];

    protected $appends = ['timezone_posted_date'];

    //Billto relation with Invoice
    public function billto(){
        return $this->hasOne(PlacesManagement::class,'id','bill_to_id');
    } 

    //Place relation with Invoice
    public function place(){
        return $this->hasOne(Place::class,'id','place_id');
    } 

    //Workorder relation with Invoice
    public function workorder(){
        return $this->hasMany(Workorder::class,'id','workorder_id');
    } 
    
    //Workorder relation with Invoice
    public function placemanagement(){
        return $this->hasOne(PlacesManagement::class,'id','management_id');
    } 

    //Workorder relation with Invoice
    public function invoiceitem(){
        return $this->hasMany(InvoiceItem::class,'invoice_id','id');
    }
    
    //Payment relation with Invoice
    public function payment(){
        return $this->hasOne(Payment::class,'invoice_id','id');
    } 

    //Multiple Payment relation with Invoice
    public function payments(){
        return $this->hasMany(Payment::class,'invoice_id','id');
    }

    //Converted posted date according to selected timezone
    public function getTimezonePostedDateAttribute()
    {
        if(!empty($this->posted_date))
        {
            $appSettings = AppSetting::select('timezone_id')->with('timezone')->where('company_id', Auth::user()->company_id)->first();
            return $this->posted_date->timezone($appSettings->timezone->timezone)->format('Y-m-d H:i:s');
        }
        return NULL;
    }

}
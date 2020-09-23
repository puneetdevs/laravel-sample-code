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
@property int $vendor_id vendor id
@property longtext $notes notes
@property decimal $subtotal subtotal
@property decimal $pst pst
@property decimal $gst gst
@property decimal $total total
@property tinyint $override_gst override gst
@property tinyint $override_pst override pst
@property datetime $created_date created date
@property int $created_by_id created by id
@property datetime $posted_date posted date
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class PurchaseOrder extends Model 
{
    use SoftDeletes;
    use BelongsToTenants;
    public $tenantColumns = ['company_id'];
    
    /**
    * Database table name
    */
    protected $table = 'purchase_orders';

    /**
    * Mass assignable columns
    */
    protected $fillable=['company_id',
    'number',
    'date',
    'vendor_id',
    'notes',
    'subtotal',
    'pst',
    'gst',
    'total',
    'override_gst',
    'override_pst',
    'created_date',
    'created_by_id',
    'posted',
    'posted_date'
];

    /**
    * Date time columns.
    */
    protected $dates=['date',
'created_date',
'posted_date'];

    protected $appends = ['timezone_posted_date'];

    //Created By relation with Purchaseorder
    public function createdby(){
        return $this->hasOne(User::class,'id','created_by_id');
    }

    //Purchase Order Item relation with Purchase Order
    public function purchaseitem(){
        return $this->hasMany(PurchaseOrderItem::class,'purchase_order_id','id');
    }

    public function vendor(){
        return $this->hasOne(Vendor::class,'id','vendor_id');
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
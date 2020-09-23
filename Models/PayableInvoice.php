<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Torzer\Awesome\Landlord\BelongsToTenants;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
   @property int $company_id company id
@property varchar $number number
@property varchar $vendor_number vendor number
@property datetime $date date
@property int $purchase_order_id purchase order id
@property int $vendor_id vendor id
@property longtext $notes notes
@property decimal $subtotal subtotal
@property decimal $pst pst
@property decimal $gst gst
@property decimal $total total
@property tinyint $override_gst override gst
@property tinyint $override_pst override pst
@property datetime $posted_date posted date
@property datetime $created_date created date
@property int $created_by_id created by id
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class PayableInvoice extends Model 
{
    use SoftDeletes;
    use BelongsToTenants;
    public $tenantColumns = ['company_id'];

    /**
    * Database table name
    */
    protected $table = 'payable_invoices';

    /**
    * Mass assignable columns
    */
    protected $fillable=['company_id',
        'number',
        'invoice_number',
        'date',
        'purchase_order_id',
        'vendor_id',
        'notes',
        'subtotal',
        'pst',
        'gst',
        'total',
        'override_gst',
        'override_pst',
        'posted',
        'posted_date',
        'created_date',
        'created_by_id'
    ];

    /**
    * Date time columns.
    */
    protected $dates=['date',
        'posted_date',
        'created_date'
    ];

    //Payable Invoice Item relation with Created By
    public function createdby(){
        return $this->hasOne(User::class,'id','created_by_id');
    }

    //Payable Invoice Item relation with Payable Invoice
    public function payableitems(){
        return $this->hasMany(PayableInvoiceItem::class,'payable_invoice_id','id');
    }

    //Payable Invoice Item relation with Payable Invoice
    public function purchaseorder(){
        return $this->hasOne(PurchaseOrder::class,'id','purchase_order_id');
    }

    //Vendor relation with Payable Invoice
    public function vendor(){
        return $this->hasOne(Vendor::class,'id','vendor_id');
    }

    //file relation with Payable Invoice
    public function payablefile(){
        return $this->hasMany(PayableInvoiceFile::class,'payable_invoice_id','id');
    }

}
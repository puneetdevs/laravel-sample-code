<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Torzer\Awesome\Landlord\BelongsToTenants;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
   @property int $company_id company id
@property int $purchase_order_id purchase order id
@property int $workorder_id workorder id
@property decimal $quantity quantity
@property int $part_id part id
@property varchar $description description
@property decimal $unit_price unit price
@property decimal $price price
@property tinyint $charge_pst charge pst
@property tinyint $received received
@property tinyint $added_to_inventory added to inventory
@property int $gl_id gl id
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class PurchaseOrderItem extends Model 
{
    use SoftDeletes;
    use BelongsToTenants;
    public $tenantColumns = ['company_id'];
    /**
    * Database table name
    */
    protected $table = 'purchase_order_items';

    /**
    * Mass assignable columns
    */
    protected $fillable=['company_id',
        'purchase_order_id',
        'workorder_id',
        'quantity',
        'part_id',
        'description',
        'unit_price',
        'price',
        'charge_pst',
        'received',
        'added_to_inventory',
        'gl_id'
    ];

    /**
    * Date time columns.
    */
    protected $dates=[];

    //Purchase Order Item relation with Workorder
    public function workorder(){
        return $this->hasOne(Workorder::class,'id','workorder_id');
    } 

    //Purchase Order Item relation with Part
    public function part(){
        return $this->hasOne(Part::class,'id','part_id');
    }

    //Purchase Order Item relation with Part
    public function glaccount(){
        return $this->hasOne(GlAccount::class,'id','gl_id');
    }

    //Purchase Order Item relation with Purchase Order
    public function purchaseorder(){
        return $this->hasOne(PurchaseOrder::class,'id','purchase_order_id');
    }

}
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Torzer\Awesome\Landlord\BelongsToTenants;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
@property int $company_id company id
@property int $invoice_id invoice id
@property decimal $quantity quantity
@property int $part_id part id
@property varchar $description description
@property decimal $unit_price unit price
@property decimal $price price
@property tinyint $charge_pst charge pst
@property int $gl_id gl id
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class InvoiceItem extends Model 
{
    use SoftDeletes;
    use BelongsToTenants;
    public $tenantColumns = ['company_id'];
    /**
    * Database table name
    */
    protected $table = 'invoice_items';

    /**
    * Mass assignable columns
    */
    protected $fillable=[
        'company_id',
        'invoice_id',
        'quantity',
        'part_id',
        'description',
        'unit_price',
        'price',
        'charge_pst',
        'gl_id'
    ];

    /**
    * Date time columns.
    */
    protected $dates=[];

    //Part with Invoice Item
    public function part(){
        return $this->hasOne(Part::class,'id','part_id');
    }

    //Part with Invoice Item
    public function glaccount(){
        return $this->hasOne(GlAccount::class,'id','gl_id');
    }

}
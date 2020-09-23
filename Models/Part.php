<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Torzer\Awesome\Landlord\BelongsToTenants;

/**
   @property int $company_id company id
@property varchar $number number
@property varchar $code code
@property varchar $name name
@property longtext $notes notes
@property decimal $selling_price selling price
@property varchar $buy_gl buy gl
@property varchar $sell_gl sell gl
@property tinyint $labour labour
@property tinyint $charge_pst charge pst
@property decimal $vendor_price vendor price
@property int $vendor_id vendor id
@property int $minimum_stock minimum stock
@property int $stock stock
@property int $buy_gl_id buy gl id
@property int $sell_gl_id sell gl id
@property int $bartec_number bartec number
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class Part extends Model 
{
    use SoftDeletes;
    use BelongsToTenants;
    public $tenantColumns = ['company_id'];

    /**
    * Database table name
    */
    protected $table = 'parts';

    /**
    * Mass assignable columns
    */
    protected $fillable=['company_id',
        'number',
        'code',
        'name',
        'notes',
        'selling_price',
        'buy_gl',
        'sell_gl',
        'labour',
        'charge_pst',
        'vendor_price',
        'markup',
        'vendor_id',
        'minimum_stock',
        'stock',
        'buy_gl_id',
        'sell_gl_id',
        'bartec_number'
    ];

    /**
    * Date time columns.
    */
    protected $dates=[];

    //Part relation with Vendor
    public function vendor()
    {
        return $this->hasOne(Vendor::class,'id','vendor_id');
    }
}
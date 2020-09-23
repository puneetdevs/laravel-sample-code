<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Torzer\Awesome\Landlord\BelongsToTenants;
use Illuminate\Database\Eloquent\SoftDeletes;
/**
   @property int $company_id company id
@property int $quote_id quote id
@property decimal $quantity quantity
@property int $part_id part id
@property varchar $description description
@property decimal $unit_price unit price
@property decimal $price price
@property tinyint $charge_pst charge pst
@property int $gl_id gl id
@property decimal $unit_cost unit cost
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class QuoteItem extends Model 
{
    use SoftDeletes;
    use BelongsToTenants;
    public $tenantColumns = ['company_id'];
    /**
    * Database table name
    */
    protected $table = 'quote_items';

    /**
    * Mass assignable columns
    */
    protected $fillable=[
        'company_id',
        'quote_id',
        'quantity',
        'part_id',
        'description',
        'unit_price',
        'price',
        'charge_pst',
        'gl_id',
        'unit_cost'
    ];

    /**
    * Date time columns.
    */
    protected $dates=[];

    //Created By relation with Quote item
    public function quote(){
        return $this->hasOne(Quote::class,'id','quote_id');
    }

    //Part relation with Quote item
    public function part(){
        return $this->hasOne(Part::class,'id','part_id');
    }


    //GI Account relation with Quote item
    public function gl(){
        return $this->hasOne(GlAccount::class,'id','gl_id');
    }


    
}
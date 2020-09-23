<?php
namespace App\Models;

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
@property decimal $subtotal subtotal
@property decimal $pst pst
@property decimal $gst gst
@property decimal $total total
@property tinyint $override_gst override gst
@property tinyint $override_pst override pst
@property datetime $created_date created date
@property int $created_by_id created by id
@property int $bartec_number bartec number
@property decimal $profit profit
@property decimal $cost cost
@property decimal $markup markup
@property int $quote_type quote type
@property longtext $office_notes office notes
@property int $quote_status_id quote status id
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class Quote extends Model 
{
    use SoftDeletes;
    use BelongsToTenants;
    public $tenantColumns = ['company_id'];
    /**
    * Database table name
    */
    protected $table = 'quotes';

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
        'subtotal',
        'pst',
        'gst',
        'total',
        'override_gst',
        'override_pst',
        'created_date',
        'created_by_id',
        'bartec_number',
        'profit',
        'cost',
        'markup',
        'quote_type',
        'office_notes',
        'quote_status_id'
    ];

    /**
    * Date time columns.
    */
    protected $dates=[
        'date',
        'created_date'
    ];

    protected $appends = ['created_by_id'];
 
    public function getCreatedByIdAttribute($created_by_id)
    {
        return User::select('id','first_name','last_name')->where('id', $created_by_id)->get();
    }
    

    //Created By relation with Quote
    public function createdby(){
        return $this->hasOne(User::class,'id','created_by_id');
    }

    //Place relation with Quote
    public function place(){
        return $this->hasOne(Place::class,'id','place_id');
    }

    //Management relation with Quote
    public function billtoid(){
        return $this->hasOne(PlacesManagement::class,'id','bill_to_id');
    }

    //Quote Item relation with Quote
    public function quoteitems(){
        return $this->hasMany(QuoteItem::class,'quote_id','id');
    }

}
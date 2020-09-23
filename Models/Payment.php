<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Torzer\Awesome\Landlord\BelongsToTenants;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
   @property int $company_id company id
@property int $invoice_id invoice id
@property int $management_id management id
@property datetime $date date
@property varchar $cheque cheque
@property decimal $amount amount
@property int $bartec_number bartec number
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class Payment extends Model 
{
    use SoftDeletes;
    use BelongsToTenants;
    public $tenantColumns = ['company_id'];
    /**
    * Database table name
    */
    protected $table = 'payments';

    /**
    * Mass assignable columns
    */
    protected $fillable=[
        'company_id',
        'invoice_id',
        'management_id',
        'date',
        'cheque',
        'amount',
        'bartec_number'
    ];

    /**
    * Date time columns.
    */
    protected $dates=['date'];

    //Invoice relation with Payment
    public function invoice(){
        return $this->hasOne(Invoice::class,'id','invoice_id');
    }


}
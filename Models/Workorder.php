<?php
namespace App\Models;

use Auth;
use App\Models\AppSetting;
use Illuminate\Database\Eloquent\Model;
use Torzer\Awesome\Landlord\BelongsToTenants;
use Illuminate\Database\Eloquent\SoftDeletes;
/**
   @property int $company_id company id
@property int $place_id place id
@property int $bill_to_id bill to id
@property int $management_id management id
@property int $contact_id contact id
@property longtext $instructions instructions
@property longtext $work_done work done
@property longtext $notes notes
@property int $workorder_type_id workorder type id
@property varchar $purchase_order purchase order
@property int $quote_id quote id
@property int $workorder_status_id workorder status id
@property datetime $completed_date completed date
@property datetime $created_date created date
@property int $created_by_id created by id
@property varchar $number number
@property int $inspection_id inspection id
@property int $bartec_number bartec number
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class Workorder extends Model 
{
    use SoftDeletes;
    use BelongsToTenants;
    public $tenantColumns = ['company_id'];
    /**
    * Database table name
    */
    protected $table = 'workorders';

    /**
    * Mass assignable columns
    */
    protected $fillable=[
        'company_id',
        'place_id',
        'bill_to_id',
        'management_id',
        'contact_id',
        'instructions',
        'work_done',
        'notes',
        'workorder_type_id',
        'purchase_order',
        'quote_id',
        'workorder_status_id',
        'completed_date',
        'created_date',
        'created_by_id',
        'number',
        'inspection_id',
        'bartec_number'
    ];

    /**
    * Date time columns.
    */
    protected $dates=[
        'completed_date',
        'created_date'
    ];

    protected $appends = ['timezone_completed_date'];

    //Created By relation with Workorder
    public function createdby(){
        return $this->hasOne(User::class,'id','created_by_id');
    } 

    //Workorder Status relation with Workorder
    public function status(){
        return $this->hasOne(WorkorderStatus::class,'id','workorder_status_id');
    } 

    //Workorder Type relation with Workorder
    public function type(){
        return $this->hasOne(WorkorderType::class,'id','workorder_type_id');
    } 

    //Management relation with Workorder
    public function management(){
        return $this->hasOne(PlacesManagement::class,'id','management_id');
    } 

    //Billto relation with Workorder
    public function billto(){
        return $this->hasOne(PlacesManagement::class,'id','bill_to_id');
    } 

    //Place Contact relation with Workorder
    public function contact(){
        return $this->hasOne(PlacesContact::class,'id','contact_id');
    }

    //Place relation with Workorder
    public function place(){
        return $this->hasOne(Place::class,'id','place_id');
    } 

    //Workorder Part relation with Workorder
    public function workorderpart(){
        return $this->hasMany(WorkorderPart::class,'workorder_id','id');
    } 

    //Workorder Time relation with Workorder
    public function workordertime(){
        return $this->hasMany(WorkorderTime::class,'workorder_id','id');
    } 

    //Purchase Order Item relation with Workorder
    public function purchaseorderitem(){
        return $this->hasMany(PurchaseOrderItem::class,'workorder_id','id');
    }

    //workorderSchedule relation with Workorder
    public function schedule(){
        return $this->hasOne(WorkorderSchedule::class,'workorder_id','id')->orderBy('start_date', 'asc');
    }

    //workorderSchedule relation with Workorder
    public function workorderSchedule(){
        return $this->hasMany(WorkorderSchedule::class,'workorder_id','id');
    }
   
    //inspection relation with Workorder
    public function inspection(){
        return $this->hasOne(Inspection::class,'id','inspection_id');
    }

    //quote relation with Workorder
    public function quote(){
        return $this->hasOne(Quote::class,'id','quote_id');
    }

    //file relation with Workorder
    public function workorderfile(){
        return $this->hasMany(WorkorderFile::class,'workorder_id','id');
    }

    //work done comment with Workorder
    public function workdonecomment(){
        return $this->hasMany(WorkorderComment::class,'workorder_id','id');
    }
    
    //Converted created date according to selected timezone
    public function getTimezoneCompletedDateAttribute()
    {
        if(!empty($this->completed_date))
        {
            $appSettings = AppSetting::select('timezone_id')->with('timezone')->where('company_id', Auth::user()->company_id)->first();
            return $this->completed_date->timezone($appSettings->timezone->timezone)->format('Y-m-d');
        }
        return NULL;
    }

}
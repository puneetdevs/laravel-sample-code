<?php
namespace App\Models;

use Auth;
use App\Models\AppSetting;
use Illuminate\Database\Eloquent\Model;
use Torzer\Awesome\Landlord\BelongsToTenants;
use Illuminate\Database\Eloquent\SoftDeletes;
/**
   @property int $company_id company id
@property int $workorder_id workorder id
@property int $type_id type id
@property date $created_date created date
@property varchar $ext ext
@property longtext $description description
@property int $file_id file id
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class WorkorderFile extends Model 
{
    use SoftDeletes;
    use BelongsToTenants;
    public $tenantColumns = ['company_id'];
    /**
    * Database table name
    */
    protected $table = 'workorder_files';

    /**
    * Mass assignable columns
    */
    protected $fillable=['company_id',
        'workorder_id',
        'type_id',
        'created_date',
        'ext',
        'description',
        'file_id'
    ];

    /**
    * Date time columns.
    */
    protected $dates=['created_date'];

    protected $appends = ['timezone_created_date'];

    //File relation with Workorder File
    public function file(){
        return $this->hasOne(File::class,'id','file_id');
    } 

    //File relation with File Type
    public function type(){
        return $this->hasOne(FileType::class,'id','type_id');
    } 

    //Converted created date according to selected timezone
    public function getTimezoneCreatedDateAttribute()
    {
        if(!empty($this->created_date))
        {
            $appSettings = AppSetting::select('timezone_id')->with('timezone')->where('company_id', Auth::user()->company_id)->first();
            return $this->created_date->timezone($appSettings->timezone->timezone)->format('Y-m-d');
        }
        return NULL;
    }

}
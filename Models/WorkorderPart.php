<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Torzer\Awesome\Landlord\BelongsToTenants;
use Illuminate\Database\Eloquent\SoftDeletes;
/**
   @property int $company_id company id
@property int $workorder_id workorder id
@property int $part_id part id
@property decimal $quantity_sold quantity sold
@property varchar $name name
@property decimal $quantity_ordered quantity ordered
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class WorkorderPart extends Model 
{
    use SoftDeletes;
    use BelongsToTenants;
    public $tenantColumns = ['company_id'];
    /**
    * Database table name
    */
    protected $table = 'workorder_parts';

    /**
    * Mass assignable columns
    */
    protected $fillable=['company_id',
        'workorder_id',
        'part_id',
        'quantity_sold',
        'name',
        'quantity_ordered',
        'created_by_id'
    ];

    /**
    * Date time columns.
    */
    protected $dates=[];

    //Workorder Type relation with Part
    public function part(){
        return $this->hasOne(Part::class,'id','part_id');
    } 

    //Workorder Part relation with Workorder
    public function workorder(){
        return $this->hasOne(Workorder::class,'id','workorder_id');
    } 


}
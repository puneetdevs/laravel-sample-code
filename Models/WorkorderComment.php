<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
/**
   @property int $workorder_id workorder id
@property longtext $comment comment
@property int $created_by created by
@property datetime $created_at created at
@property datetime $updated_at updated at
@property datetime $deleted_at deleted at
   
 */
class WorkorderComment extends Model 
{
    
    /**
    * Database table name
    */
    protected $table = 'workorder_comments';

    /**
    * Mass assignable columns
    */
    protected $fillable=['workorder_id',
        'comment',
        'created_by'
    ];

    /**
    * Date time columns.
    */
    protected $dates=[];

    //Created By relation with workorder comments
    public function createdby(){
        return $this->hasOne(User::class,'id','created_by');
    }


}
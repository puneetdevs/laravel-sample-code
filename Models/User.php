<?php

namespace App\Models;

use Laravel\Passport\HasApiTokens;
use App\Models\Traits\Method\UserMethod;
use Illuminate\Notifications\Notifiable;
use App\Models\Traits\Attribute\UserAttribute;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use UserMethod,
        Notifiable,
        HasApiTokens,
        UserAttribute;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */ 
    protected $fillable = [
        'name','first_name', 'last_name', 'email', 'password','domain_number','company_id','username','phone','code','mobile','notes','technician'
        ,'active','schedule_order','office','pic_id','company_logo'
    ];

    public function findForPassport($username)
    {
        return $this->where('username', $username)->first();
    }
    
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    //Staff Role relation with User
    public function staffrole(){
        return $this->hasOne(StaffRole::class,'staff','id');
    } 
    //File relation with User
    public function file(){
        return $this->hasOne(File::class,'id','pic_id');
    } 
    
    //workorderSchedule relation with User
    public function workorderSchedule(){
        return $this->hasMany(WorkorderSchedule::class,'staff_id','id');
    } 

    //workorderSchedule relation with User
    public function schedule(){
        return $this->hasOne(WorkorderSchedule::class,'staff_id','id');
    } 
    
}

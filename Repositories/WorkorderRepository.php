<?php

namespace App\Repositories;


use DB;
use App\Exceptions\Handler;
use App\Repositories\BaseRepository;
use App\Models\WorkorderPart;
use App\Models\Workorder;
use App\Models\WorkorderTime;
use App\Models\WorkorderSchedule;
use App\Models\WorkorderType;
use App\Models\WorkorderFile;
use App\Models\User;
use App\Models\Place;
use App\Models\Management;
use App\Models\StaffRole;
use App\Models\ScheduleNote;
use App\Models\WorkorderStatus;
use App\Models\PlacesManagementType;
use App\Models\PlacesManagement;
use App\Models\Inspection;
use App\Models\File;
use App\Models\WorkorderComment;
use DateTime;
use DateInterval;
use DatePeriod;
use Auth;
use Carbon\Carbon;
use App\Helpers\Helper;
use Illuminate\Support\Facades\Storage;

/**
 * Class WorkorderRepository.
 */
class WorkorderRepository extends BaseRepository
{
    /**
     * @return string
     */
    public function model()
    {
        return Workorder::class;
    }

    
    /**
     * get Workorder Part
     *
     * @param  mixed $field
     * @param  mixed $management_id
     *
     * @return void
     */
    public function getWorkorderPart($field,$id)
    {
        return WorkorderPart::where($field, $id );
    }

    /**
     * Add Workorder Time
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function addWorkorderTime($request){
        //Case-1 If staff_id_2 is set but value not send in request
        //Then insert data only staff_id
        if($request->has('staff_id_2') && empty($request->staff_id_2)){
            foreach($request->code_time as $key => $value){
                if(!empty($value['code']) && !empty($value['time'])){
                    $data[$key]['company_id'] = Auth::user()->company_id;
                    $data[$key]['workorder_id'] = $request->workorder_id;
                    $data[$key]['staff_id'] = $request->staff_id;
                    $data[$key]['date'] =  $request->date;
                    $data[$key]['code'] = $value['code'];
                    $data[$key]['time'] = $value['time'];
                    $data[$key]['created_by_id'] = Auth::user()->id;
                    $data[$key]['created_at'] = date('Y-m-d H:i:s');
                    $data[$key]['updated_at'] = date('Y-m-d H:i:s');
                }
            }
        //Case-2 If staff_id_2 is set and not null
        //Then insert data for staff_id and staff_id_2
        }else if( $request->has('staff_id_2') && !empty($request->staff_id_2)){
            foreach($request->code_time as $key => $value){
                
                if(!empty($value['code']) && !empty($value['time'])){
                    $data[$key]['company_id'] = Auth::user()->company_id;
                    $data[$key]['workorder_id'] = $request->workorder_id;
                    $data[$key]['staff_id'] = $request->staff_id;
                    $data[$key]['date'] =  $request->date;
                    $data[$key]['code'] = $value['code'];
                    $data[$key]['time'] = $value['time'];
                    $data[$key]['created_by_id'] = Auth::user()->id;
                    $data[$key]['created_at'] = date('Y-m-d H:i:s');
                    $data[$key]['updated_at'] = date('Y-m-d H:i:s');
                }
            }
            
            foreach($request->code_time as $keys => $values){
                
                if(!empty($values['code']) && !empty($values['time'])){
                    $datas[$keys]['company_id'] = Auth::user()->company_id;
                    $datas[$keys]['workorder_id'] = $request->workorder_id;
                    $datas[$keys]['staff_id'] = $request->staff_id_2;
                    $datas[$keys]['date'] =  $request->date;
                    $datas[$keys]['code'] = $values['code'];
                    $datas[$keys]['time'] = $values['time'];
                    $datas[$keys]['created_by_id'] = Auth::user()->id;
                    $datas[$keys]['created_at'] = date('Y-m-d H:i:s');
                    $datas[$keys]['updated_at'] = date('Y-m-d H:i:s');
                }
            }
            WorkorderTime::insert($datas);
        //Case-3 If staff_id_2 is not set in request
        //Then insert data for staff_id only not staff_id_2
        }else{
            foreach($request->code_time as $key => $value){
                if(!empty($value['code']) && !empty($value['time'])){
                    $data[$key]['company_id'] = Auth::user()->company_id;
                    $data[$key]['workorder_id'] = $request->workorder_id;
                    $data[$key]['staff_id'] = $request->staff_id;
                    $data[$key]['date'] =  $request->date;
                    $data[$key]['code'] = $value['code'];
                    $data[$key]['time'] = $value['time'];
                    $data[$key]['created_by_id'] = Auth::user()->id;
                    $data[$key]['created_at'] = date('Y-m-d H:i:s');
                    $data[$key]['updated_at'] = date('Y-m-d H:i:s');
                }
            }
        }
        WorkorderTime::insert($data);
    }

    public function addWorkorderSchedule($request)
    {
        //Case-1 If staff_id_2 is set but value not send in request OR If staff_id_2 is not set
        //Then insert data only staff_id
        if( ($request->has('staff_id_2') && empty($request->staff_id_2)) || (empty($request->staff_id_2)) ){
            $count=0;
            $requested_data[0]['company_id'] = Auth::user()->company_id;
            $requested_data[0]['workorder_id'] = $request->workorder_id;
            $requested_data[0]['staff_id'] = $request->staff_id;
            $requested_data[0]['hours'] = $request->hours;
            $requested_data[0]['start_date'] = $request->start_date;
            $requested_data[0]['start_time'] = $request->start_time;
            $requested_data[0]['start_timestamp'] = $request->start_date.' '.$request->start_time.':00' ;
            
            //Calculation of end timestamp
            $endtime = date('Y-m-d H:i:s',strtotime('+'.$request['hours'].' hour',strtotime($requested_data[0]['start_timestamp'])));
            
            $requested_data[0]['end_timestamp'] = $endtime;
            $requested_data[0]['created_at'] = date('Y-m-d H:i:s');
            $requested_data[0]['updated_at'] = date('Y-m-d H:i:s');

        }
        //Case-2 If staff_id_2 is set and not null
        //Then insert data for staff_id and staff_id_2
        else if( $request->has('staff_id_2') && !empty($request->staff_id_2) )
        {
            $staff_count=2;
            for($count=0;$count<$staff_count;$count++)
            {
                $requested_data[$count]['company_id'] = Auth::user()->company_id;
                $requested_data[$count]['workorder_id'] = $request->workorder_id;
                if($count==0)
                {
                    $requested_data[$count]['staff_id'] = $request->staff_id;
                }
                else
                {
                    $requested_data[$count]['staff_id'] = $request->staff_id_2;
                }
                $requested_data[$count]['hours'] = $request->hours;
                $requested_data[$count]['start_date'] = $request->start_date;
                $requested_data[$count]['start_time'] = $request->start_time;
                $requested_data[$count]['start_timestamp'] = $request->start_date.' '.$request->start_time.':00' ;
                
                //Calculation of end timestamp
                $endtime = date('Y-m-d H:i:s',strtotime('+'.$request->hours.' hour',strtotime($requested_data[$count]['start_timestamp'])));
                
                $requested_data[$count]['end_timestamp'] = $endtime;
                $requested_data[$count]['created_at'] = date('Y-m-d H:i:s');
                $requested_data[$count]['updated_at'] = date('Y-m-d H:i:s');
            }
        }
        WorkorderSchedule::insert($requested_data);
        $workorderstatus = WorkorderStatus::where('workorder_status','Scheduled')->first();
        Workorder::where('id',$request->workorder_id)->update(['workorder_status_id' =>$workorderstatus->id ]);

    }

    /**
     * Get Weekly Workorder Schedule Report
     *
     * @return void
     */
    public function getWeeklyWorkorderScheduleReport($request, $role){
        
        //Set Date Class here
        $date = new DateTime($request->start_date);
        //Check Monday
        if($date->format('D') == 'Mon'){
            $current_monday = $date->format('Y-m-d');
        }else{
            $date->modify('last monday');
            $current_monday = $date->format('Y-m-d');
        }
        //Check Friday
        if($date->format('D') == 'Fri'){
            $current_friday = $date->format('Y-m-d');
        }else{
            $date->modify('next friday');
            $current_friday = $date->format('Y-m-d');
        }
      
        $date->modify('last monday');
        $previous_monday = $date->format('Y-m-d');
        $date->modify('next monday');
        $next_monday = $date->format('Y-m-d');
        $week_days = $this->getDatesFromRange($current_monday, $current_friday);
        
        $final_array = array();
        foreach($week_days as $key=>$value){
            $final_array[$key]['date'] = $value;
            $technician_schedule_q = User::select('id','name','first_name','last_name')->where('company_id',Auth::user()->company_id)->where('technician',1)->where('active',1)
                                            ->with(['schedule.workorder.type','schedule.workorder.place.city','schedule.workorder.place.street'
                                                ,'schedule' => function($query) use($value){
                                                $query->where('start_date',$value);
                                            },'schedule.workorder' => function($q){
                                                $q->select('id','workorder_type_id','place_id','number');
                                            },'schedule.workorder.place' => function($q){
                                                $q->select('id','street_number','street_name','suite','name','city_id','street_type_id');
                                            }]);
            $technician_schedule_q->whereHas('staffrole',function($q) use($role){
                $q->where('role',$role->id);
            });
            $schedule_q=$technician_schedule_q->orderBy('schedule_order', 'asc')->orderBy('name', 'asc')->get()->toArray();
           
            foreach($schedule_q as $keyindex=>$value)
            { 
                $j=0;
                    if(!empty($value['schedule']))
                    {
                        $hours=$value['schedule']['hours'];
                        $count=$hours*2;
                        for($i=0;$i<=$count;$i++)
                        {
                            $workorderschedule[$j]['id']=$value['schedule']['id'];
                            $workorderschedule[$j]['company_id']=$value['schedule']['company_id'];
                            $workorderschedule[$j]['staff_id']=$value['schedule']['staff_id'];
                            $workorderschedule[$j]['start_date']=$value['schedule']['start_date'];
                            $workorderschedule[$j]['hours']=$value['schedule']['hours'];
                            $workorderschedule[$j]['time']=$value['schedule']['start_time'];
                            $workorderschedule[$j]['workorder']=$value['schedule']['workorder'];
                            //Add 30 minutes in time
                            $value['schedule']['start_time']=date("H:i:s", strtotime('+30 minutes', strtotime($value['schedule']['start_time'])));
                            $j++;
                        }
                    }
                    if(!empty($value['schedule'])){
                        $schedule_q[$keyindex]['schedule']=$workorderschedule;
                    }
            }
            $final_array[$key]['schedule'] = $schedule_q;
        }
        //Get all technician array list here
        $technician = User::select('id','name','first_name','last_name')
                            ->where('company_id',Auth::user()->company_id)
                            ->where('technician',1)
                            ->where('active',1);
        $technician = $technician->whereHas('staffrole',function($q) use($role){
            $q->where('role',$role->id);
        })->orderBy('schedule_order', 'asc')->orderBy('name', 'asc')->get()->toArray();
        
        return array('technician' => $technician, 'workorder'=>$final_array, 'next_week'=>$next_monday, 'previous_week'=>$previous_monday);
    }

    /**
     * Get Dates From Range
     *
     * @param  mixed $start
     * @param  mixed $end
     * @param  mixed $format
     *
     * @return void
     */
    public function getDatesFromRange($start, $end, $format = 'Y-m-d') { 
        
        // Declare an empty array 
        $array = array(); 
        
        // Variable that store the date interval 
        // of period 1 day 
        $interval = new DateInterval('P1D'); 
    
        $realEnd = new DateTime($end); 
        $realEnd->add($interval); 
    
        $period = new DatePeriod(new DateTime($start), $interval, $realEnd); 
    
        // Use loop to store date into array 
        foreach($period as $date) {                  
            $array[] = $date->format($format);  
        } 
    
        // Return the array elements 
        return $array; 
    } 

    /**
     * Get Weekly Workorder Schedule Report
     *
     * @return void
     */
    public function getScheduleReport($request, $role){
        $all_days = $this->getDatesFromRange($request->start_date, $request->end_date);

        //Get Summary By Technician Report
        if($request->report == 'summary_by_tech'){
            $final_array = array();
            $i=0;
            foreach($all_days as $value){
                
                //start Query here
                $technician_schedule_q = User::select('id','name','first_name','last_name')->where('company_id',Auth::user()->company_id)->where('technician',1)->where('active',1)
                                                ->with(['workorderSchedule' => function($query) use($value,$request){
                                                    $query->where('start_date',$value)
                                                    ->where('company_id',Auth::user()->company_id)
                                                    ->whereNULL('deleted_at');
                                                    //Check if workorder Type send in request
                                                    if($request->has('workorder_type') && !empty($request->workorder_type)){
                                                        $workorder_ids = Workorder::where('company_id',Auth::user()->company_id)
                                                                                    ->where('workorder_type_id',$request->workorder_type)
                                                                                    ->whereNull('deleted_at')
                                                                                    ->get()->pluck('id')->toArray();
                                                        $query->whereIn('workorder_id',$workorder_ids);
                                                    }
                                                },'workorderSchedule.workorder' => function($q){
                                                    $q->select('id','workorder_type_id','place_id','number');
                                                },'workorderSchedule.workorder.type',
                                                'workorderSchedule.workorder.place' => function($q){
                                                    $q->select('id','street_number','street_name','name','suite','street_type_id');
                                                },'workorderSchedule.workorder.place.street']);
                                               
                //Check if technician send in request
                if($request->has('technician') && !empty($request->technician)){
                    $technician_schedule_q->where('id',$request->technician);
                }
                //check Role here 
                $technician_schedule_q->whereHas('staffrole',function($q) use($role){
                    $q->where('role',$role->id);
                });
                //check Role here 
                $technician_schedule_q->whereHas('workorderSchedule',function($q) use($value){
                    $q->where('start_date',$value);
                });
                $technician_schedule = $technician_schedule_q->get()->toArray();
                
                
                
                $j=0;$schedulevalue=array();
                if(!empty($technician_schedule)){

                     foreach($technician_schedule as $value1)
                    {
                        if(!empty($value1['workorder_schedule']))
                        {
                            $schedulevalue[$j]['id']=$value1['id'];
                            $schedulevalue[$j]['name']=$value1['name'];
                            $schedulevalue[$j]['first_name']=$value1['first_name'];
                            $schedulevalue[$j]['last_name']=$value1['last_name'];
                            $schedulevalue[$j]['workorder_schedule']=$value1['workorder_schedule'];
                            $j++;
                        }
                        
                    } 
                    if(!empty($schedulevalue))
                    {
                        $final_array[$i]['date'] = $value;
                        $final_array[$i]['schedule'] = $schedulevalue;
                        $i++;
                    }
                }
            }
            
           
        //Get Summary By Workorder Report
        }else{

            $final_array = array();
            $i = 0;
            foreach($all_days as $value){
                
                //start Query here
                $technician_schedule_q = WorkorderSchedule::
                                                where('start_date',$value)
                                                ->with(['staff' => function($query){
                                                    $query->select('id','name');
                                                },'workorder' => function($q){
                                                    $q->select('id','workorder_type_id','place_id','number');
                                                },'workorder.type',
                                                'workorder.place' => function($q){
                                                    $q->select('id','street_number','street_name','name','suite','street_type_id');
                                                },'workorder.place.street']);
                $technician_schedule_q->join('workorders', 'workorder_schedules.workorder_id', '=', 'workorders.id')
                ->select('workorder_schedules.*', DB::raw('(workorders.company_id) company,(workorders.number) number'));                               
                //Check if technician send in requestworkorders
                if($request->has('technician') && !empty($request->technician)){
                    $technician_schedule_q->where('staff_id',$request->technician);
                }
                //check if workorder type send in request
                if($request->has('workorder_type') && !empty($request->workorder_type)){
                    $workorder_ids = Workorder::where('company_id',Auth::user()->company_id)
                                                ->where('workorder_type_id',$request->workorder_type)
                                                ->whereNull('deleted_at')
                                                ->get()->pluck('id')->toArray();
                    $technician_schedule_q->whereIn('workorder_id',$workorder_ids);
                }
                $technician_schedule = $technician_schedule_q->orderBy('number','desc')->get()->toArray();
                if(!empty($technician_schedule)){
                    
                    $j=0;
                    $final_array[$i]['date'] = $value;
                    $final_array[$i]['schedule'] = $technician_schedule;
                    $i++;
                }
            }
        }
        return $final_array;
    }

    /**
     * Get Shop Workorder Report
     *
     * @param  mixed $request
     * @param  mixed $role
     *
     * @return void
     */
    public function getShopWorkorderReport($request, $role){
        $all_days = $this->getDatesFromRange($request->start_date, $request->end_date);
        //Get technician list here
        $final_array = array();
        $technician_list = User::select('id','name','first_name','last_name')
                                ->where('company_id',Auth::user()->company_id)
                                ->where('technician',1)
                                ->where('active',1);        
        //Check if technician send in request
        if($request->has('technician') && !empty($request->technician)){
            $technician_list->where('id',$request->technician);
        }
        $technician = $technician_list->orderBy('id','asc')->get()->toArray();
        $workorder_ids = Workorder::where('workorder_type_id',18)->get()->pluck('id')->toArray();
        foreach($all_days as $key=>$value){
            
            //start Query here
            $technician_schedule_q = User::select('id','name','first_name','last_name')->where('company_id',Auth::user()->company_id)->where('technician',1)->where('active',1)
                                            ->with(['workorderSchedule' => function($query) use($value,$workorder_ids){
                                                $query->where('start_date',$value)
                                                ->where('company_id',Auth::user()->company_id)
                                                ->whereIn('workorder_id',$workorder_ids)
                                                ->whereNULL('deleted_at')
                                                ->orderBy('staff_id','asc')->orderBy('id','desc');
                                            },'workorderSchedule.workorder' => function($q){
                                                $q->select('id','workorder_type_id','place_id','number');
                                            }]);
            //Check if technician send in request
            if($request->has('technician') && !empty($request->technician)){
                $technician_schedule_q->where('id',$request->technician);
            }
            //check Role here 
            $technician_schedule_q->whereHas('staffrole',function($q) use($role){
                $q->where('role',$role->id);
            });
            
            $final_array[$key]['date'] = $value;
            $final_array[$key]['schedule'] = $technician_schedule_q->orderBy('id','asc')->get()->toArray();
        }
       
        return array('final_array' => $final_array, 'technician'=> $technician);
    }

    /**
     * Post Shop Workorder
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function postShopWorkorder($request){
        //Check place 
        $place = Place::where('company_id',Auth::user()->company_id)->where('is_default',1)->first();
        if($place){
            //Check Management
            $management = Management::where('company_id',Auth::user()->company_id)->first();            
            if($management){
                $owner = PlacesManagementType::where('place_management_type','Owner')->first();
                $Property = PlacesManagementType::where('place_management_type','Property Manager')->first();
                $place_management_owner = PlacesManagement::where('place_id',$place->id)
                                                            ->where('type_id',$owner->id)
                                                            ->first();
                $place_management_property = PlacesManagement::where('place_id',$place->id)
                                                                ->where('type_id',$Property->id)
                                                                ->first();

                $workorder_type =  WorkorderType::where('workorder_type','Shop Time')->first();
                $workorder_status =  WorkorderStatus::where('workorder_status','Shop Time')->first();
                //Set date here
                $all_days = $this->getDatesFromRange($request->start_date, $request->end_date);
                //Get all technician here
                $technician_q = User::where('company_id',Auth::user()->company_id)->where('technician',1);
                if($request->has('technician') && !empty($request->technician)){
                    $technician_q->where('id',$request->technician);
                }
                $technician = $technician_q->get()->toArray();
                
                if(!empty($technician)){
                    //Start loop here and make new workorder and schedule if not created for technician
                    foreach($technician as $technicians){
                        //Create new workorder here for technician
                        $model=new Workorder;
                        $requested_data['place_id'] = $place->id;
                        
                        if($place_management_owner){
                            $requested_data['bill_to_id'] = $place_management_owner->id;
                        }else{
                            return array(STATUS => 200, MESSAGE => "Please create management as  owner and property manager attached before you can create a workorder");
                        }
                        if($place_management_property){
                            $requested_data['management_id'] = $place_management_property->id;
                        }else{
                            return array(STATUS => 200, MESSAGE => "Please create management as  owner and property manager attached before you can create a workorder");
                        }

                        $requested_data['workorder_type_id'] = $workorder_type->id;
                        $requested_data['workorder_status_id'] = $workorder_status->id;
                        $requested_data['created_by_id'] = Auth::user()->id;
                        $requested_data['created_date'] = date('Y-m-d H:i:s');
                        $model->fill($requested_data);
                        if ($model->save()) {
                            //Set workorder Number here
                            $workorder_number = Workorder::where('company_id',Auth::user()->company_id)->whereRaw('MONTH(created_at) = ?',[date('m')])->count();
                            $number = Helper::setWorkorderNumber($workorder_number);
                            Workorder::where('id',$model->id)->update(['number' => $number]);
                            $model->number=$number;
                            $store_data = array();
                            //Add Workorder schedule for all days
                            foreach($all_days as $key=>$day){
                                $store_data[$key]['start_date'] = $day;
                                $store_data[$key]['staff_id'] = $technicians['id'];
                                $store_data[$key]['hours'] = 0;
                                $store_data[$key]['minutes'] = 0;
                                $store_data[$key]['start_time'] = "08:00:00";
                                $store_data[$key]['workorder_id'] = $model->id;
                                $store_data[$key]['company_id'] = Auth::user()->company_id;
                                $store_data[$key]['created_at'] = date('Y-m-d H:i:s');
                                $store_data[$key]['updated_at'] = date('Y-m-d H:i:s');
                            }
                            if(!empty($store_data)){
                                WorkorderSchedule::insert($store_data);
                            }
                        }
                    }
                    return array(STATUS => 200, MESSAGE => "Workorder created successfully");
                }
                return array(STATUS => 466, MESSAGE => "Technician not found, Please create technician first");
            }
            return array(STATUS => 466, MESSAGE => "Please create management and set as default");
        }
        return array(STATUS => 466, MESSAGE => "No Default Place is set. Please set default Place first.");
    }

    /**
     * Get Workorder Schedule Daily
     *
     * @param  mixed $request
     * @param  mixed $role
     * @param  mixed $start_date
     *
     * @return void
     */
    public function getWorkorderScheduleDaily($request, $role,$start_date){
        $technician_schedule_q = User::select('id','name','first_name','last_name')->where('company_id',Auth::user()->company_id)->where('technician',1)->where('active',1)
                ->with(['workorderSchedule.workorder.type','workorderSchedule.workorder.place.city','workorderSchedule.workorder.place.street'
                ,'workorderSchedule' => function($query) use($start_date){
                    $query->where('start_date',$start_date);
                },'workorderSchedule.workorder' => function($q){
                    $q->select('id','workorder_type_id','place_id','number');
                },'workorderSchedule.workorder.place' => function($q){
                    $q->select('id','street_number','street_name','name','suite','city_id','street_type_id');
                }]);
        $technician_schedule = $technician_schedule_q->whereHas('staffrole',function($q) use($role){
                    $q->where('role',$role->id);
                })->orderBy('schedule_order', 'asc')->orderBy('name', 'asc')->get()->toArray();
                         
            foreach($technician_schedule as $key=>$value)
            {
                $j=0;
                foreach($value['workorder_schedule'] as $schedule)
                {

                    if(!empty($schedule))
                    {
                        $hours=$schedule['hours'];
                        $count=$hours*2;
                        for($i=0;$i<=$count;$i++)
                        {
                            $workorderschedule[$j]['id']=$schedule['id'];
                            $workorderschedule[$j]['company_id']=$schedule['company_id'];
                            $workorderschedule[$j]['staff_id']=$schedule['staff_id'];
                            $workorderschedule[$j]['start_date']=$schedule['start_date'];
                            $workorderschedule[$j]['hours']=$schedule['hours'];
                            $workorderschedule[$j]['time']=$schedule['start_time'];
                            $workorderschedule[$j]['workorder']=$schedule['workorder'];
                            //Add 30 minutes in time
                            $schedule['start_time']=date("H:i:s", strtotime('+30 minutes', strtotime($schedule['start_time'])));
                            $j++;
                        }
                    }
                }
                if(!empty($value['workorder_schedule'])){
                    $technician_schedule[$key]['workorder_schedule']=$workorderschedule;
                }
            }    
        $i=0;
        foreach($technician_schedule as $key=>$value)
        {
            $notes= DB::table('schedule_notes')
                    ->whereRaw('"'.$start_date.'" between `start_date` and `end_date`')
                    ->whereRaw("find_in_set('".$value['id']."',staff_list)")
                    ->whereNull('deleted_at')
                    ->get()->toArray();
            if(!empty($notes))
            {
                $technician_schedule[$i]['notes']=$notes;
            }
            else
            {
                $technician_schedule[$i]['notes']='';
            }
            $i++;
        } 
        
        return $technician_schedule;
    }

    /**
     * Workorder Technician
     *
     * @param  mixed $date
     *
     * @return void
     */
    public function workorderTechnician(){
        
        $time_array=array();
        $final_array=array();
        $user_id=Auth::user()->id;
        
        $today_date=date('Y-m-d');
    
        //$start_date = date('Y-m-d', strtotime("-7 day", strtotime($today_date)));
        //$start_date = '2000-01-01';
        $end_date=date('Y-m-d', strtotime("+7 day", strtotime($today_date)));
        
        //$week_days = $this->getDatesFromRange($start_date, $end_date);
        $i = 0;
        // foreach($week_days as $date)
        // {
           
            //Set status when date is not current date
            // if($date==$today_date)
            // {
            //     $status = WorkorderStatus::select('id');
            // }
            // else
            // {
                $status = WorkorderStatus::select('id')->whereIn('workorder_status', ['Scheduled','Shop Time','New','No Charged']);
            //}
            
            $workorder=Workorder::whereIn('workorder_status_id',$status)
                    ->with(['workdonecomment','workdonecomment.createdby','workorderfile.file','place','place.city','place.city.province','place.city.province.country','place.placetype','place.street','place.management',
                                'place.contact','place.monitoring','place.monitoring.monitoringinvoice','management.management',
                                'contact.contact','type','status','quote','quote.quoteitems.part','inspection',
                                'workorderpart','workorderpart.part','workorderSchedule' => function($query) use($end_date,$user_id){
                                    $query->where('start_date','<=',$end_date);
                                    $query->where('staff_id',$user_id)->orderBy('id', 'asc');
                                },'workordertime' ,'workordertime.staff'])
                                ->whereHas('workorderSchedule')
                                ->get()->toArray();
                     
            foreach($workorder as $data)
            {
                
                 
                if(!empty($data['workorder_schedule']) )
                { 
                    
                    //Added app url in file path here
                    foreach($data['workorderfile'] as $key=>$file)
                    {
                        $data['workorderfile'][$key]['file']['path']=env('APP_URL').'/storage/'.$file['file']['path'];
                    }


                    $mytime=0;$totaltime=0;
                    foreach($data['workordertime'] as $time)
                    {
                        if($user_id==$time['staff_id'])
                        {
                            $mytime=$mytime+$time['time'];
                        }
                        $totaltime=$totaltime+$time['time'];
                    }
                    
                    if(!in_array($data['id'], array_column($final_array,'workorder_id')))
                    {
                        $time_staff = WorkorderSchedule::where(WORKORDER_ID,$data['id']);        
                        $time_staff = $time_staff->join('users', 'workorder_schedules.staff_id', '=', 'users.id')
                                        ->select('workorder_schedules.staff_id', DB::raw("CONCAT(users.first_name,' ',users.last_name) as name, users.first_name, users.last_name"))->get()->toArray();
                        $staff_members =  array();
                        $finalTimeStaff = array();
                        if(!empty($time_staff)){
                            $j=0;
                            foreach($time_staff as $tk=>$tv){
                                if(!in_array($tv['staff_id'],$staff_members)){
                                    $finalTimeStaff[$j] = $tv;
                                    $staff_members[$j] = $tv['staff_id']; 
                                    $j++;
                                }
                            }
                        }
                        $final_array[$i]['time_technician'] = $finalTimeStaff; 
                        $final_array[$i]['workorder_id'] = $data['id'];
                        $final_array[$i]['workorder_number'] = $data['number'];
                        $final_array[$i]['instructions'] = $data['instructions'];
                        $final_array[$i]['work_done'] = $data['workdonecomment'];   
                        $final_array[$i]['notes'] = $data['notes']; 
                        $final_array[$i]['place'] = $data['place'];
                        $final_array[$i]['place']['city'] = $data['place']['city'];
                        $final_array[$i]['place']['city']['province'] = isset($data['place']['city']['province']) ? $data['place']['city']['province'] : [];
                        $final_array[$i]['place']['city']['province']['country'] = isset($data['place']['city']['province']['country']) ? $data['place']['city']['province']['country']: [];
                        $final_array[$i]['place']['city'] = $data['place']['city'];
                        $final_array[$i]['place']['place_type'] = $data['place']['placetype'];
                        $final_array[$i]['place']['street'] = $data['place']['street'];
                        $final_array[$i]['place']['place_management'] = $data['place']['management'];
                        $final_array[$i]['place']['contact'] = $data['place']['contact'];
                        $final_array[$i]['management'] = $data['management'];
                        $final_array[$i]['contact'] = $data['contact'];
                        $final_array[$i]['workorder_type_id'] = $data['type']['id'];
                        $final_array[$i]['workorder_type'] = $data['type']['workorder_type'];
                        $final_array[$i]['workorder_status_id'] = $data['status']['id'];
                        $final_array[$i]['workorder_status'] = $data['status']['workorder_status'];
                        //$final_array[$i]['date'] = $data['workorder_schedule']['start_date'];
                        $final_array[$i]['my_time'] = $mytime;
                        $final_array[$i]['total_time'] = $totaltime;
                        $final_array[$i]['schedule'] = $data['workorder_schedule'];
                        $final_array[$i]['time'] = $data['workordertime'];
                        $final_array[$i]['part'] = $data['workorderpart'];
                        $final_array[$i]['inspection'] = $data['inspection'];
                        $final_array[$i]['quote'] = $data['quote'];
                        $final_array[$i]['workorderfile'] = $data['workorderfile'];
                        $i++;
                    }else{
                        if(!empty($data['workorder_schedule'])){
                            $array_key = array_search($data['id'], array_column($final_array,'workorder_id'));
                            $final_array[$array_key]['schedule'] = $data['workorder_schedule'];
                            //$final_array[$array_key]['date'] = $data['start_date'];
                        }
                    }
                    

                }
            }
       // }
        //Last five days times are added here
        $t=0;
        $fifth_last_date=date('Y-m-d', strtotime("-5 day", strtotime($today_date)));
        $last_five_dates = $this->getDatesFromRange($fifth_last_date, $today_date);
        foreach($last_five_dates as $date)
        {
            $workordertime=WorkorderTime::where('date', $date)
                ->where('staff_id', $user_id)->sum('time');

            if(!empty($workordertime))
            {
                $time_array[$t]['date'] = $date;
                $time_array[$t]['time'] = $workordertime;
                $t++;
            }
        }
        
        return array('final_array' => $final_array, 'timearray'=> $time_array);
    }

    /**
     * inspection Technician
     *
     * @param  mixed $date
     *
     * @return void
     */
    public function inspectionTechnician(){
        $final_array=array();
        $user_id=Auth::user()->id;
        
        $today_date=date('Y-m-d');
        $start_date = date('Y-m-d', strtotime("-7 day", strtotime($today_date)));
        $end_date=date('Y-m-d', strtotime("+7 day", strtotime($today_date)));
        
        $week_days = $this->getDatesFromRange($start_date, $end_date);

        $i = 0;
        foreach($week_days as $date)
        {
            //Set status when date is not current date
            if($date==$today_date)
            {
                $status = WorkorderStatus::select('id');
            }
            else
            {
                $status = WorkorderStatus::select('id')->whereIn('workorder_status', ['Scheduled','Shop Time','New']);
            }

            $workorder=Workorder::whereIn('workorder_status_id',$status)
                        ->with(['inspection','inspection.area','inspection.device.deficiencytype','inspection.device.inspectionarea',
                        'inspection.device.devicetype','inspection.device.formtemplate','inspection.device.deficiencyfile.file','inspection.device.inspectiondevicenotes',
                        'inspection.device.testeddetail','inspection.device.noaccessdetail','inspection.device.repaireddetail',
                        'inspection.device.defectivedetail','inspection.type','inspection.status','place','place.street','place.city','type','status',
                        'workorderSchedule' => function($query) use($date,$user_id){
                            $query->where('start_date',$date);
                            $query->where('staff_id',$user_id);
                        }])
                        ->whereHas('workorderSchedule')
                        ->get()->toArray();

            foreach($workorder as $data)
            {
                if(!empty($data['workorder_schedule']) && !empty($data['inspection']) )
                {
                    foreach($data['inspection']['device'] as $key=>$deficiencyfile)
                    {
                        foreach($deficiencyfile['deficiencyfile'] as $key1=>$file)
                        {
                            $data['inspection']['device'][$key]['deficiencyfile'][$key1]['file']['path']=env('APP_URL').'/storage/'.$file['file']['path'];
                        }
                        //dd($deficiencyfile['deficiencyfile']);
                    }
                    /* //Added app url in file path here
                    foreach($data['workorderfile'] as $key=>$file)
                    {
                        $data['workorderfile'][$key]['file']['path']=env('APP_URL').'/storage/'.$file['file']['path'];
                    } */

                    if(!in_array($data['inspection']['id'], array_column($final_array,'inspection_id')))
                    {
                        $final_array[$i]['inspection_id'] = $data['inspection']['id'];
                        $final_array[$i]['number'] = $data['inspection']['number'];
                        $final_array[$i]['date'] = $data['inspection']['inspection_date'];
                        $final_array[$i]['inspection_type'] = $data['inspection']['type'];
                        $final_array[$i]['inspection_status'] = $data['inspection']['status'];
                        $final_array[$i]['technician1'] = $data['inspection']['tech1_id'];
                        $final_array[$i]['technician2'] = $data['inspection']['tech2_id'];
                        $final_array[$i]['technician3'] = $data['inspection']['tech3_id'];
                        $final_array[$i]['technician3'] = $data['inspection']['tech3_id'];
                        $final_array[$i]['technician_notes'] = $data['inspection']['technician_notes'];
                        $final_array[$i]['inspection_notes'] = $data['inspection']['notes'];
                        $final_array[$i]['place'] = $data['place'];
                        $final_array[$i]['area'] = $data['inspection']['area'];
                        $final_array[$i]['device'] = $data['inspection']['device'];
                        $i++;
                    }
                        
                }
                
            }
        }

        
        return $final_array;
    }

    /**
     * add Workorder Part
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function addWorkorderPart($request)
    {
        $model=new WorkorderPart;
        $requested_data = $request->all();
        $requested_data['company_id'] = Auth::user()->company_id;
        $requested_data['created_by_id'] = Auth::user()->id;
        $requested_data['name'] = $requested_data['description'];
        $requested_data['created_at'] = date('Y-m-d H:i:s');
        $requested_data['updated_at'] = date('Y-m-d H:i:s');
        $model->fill($requested_data);
        $model->save();
    }

    /**
     * update Technician Workorder Details
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function updateTechnicianWorkorderDetails($request)
    {
        foreach($request->workorder as $workorder)
        {
            //Add workorder time here
            if(array_key_exists('time', $workorder)){
                $this->addTechnicianWorkorderTime($workorder['time'],$workorder['workorder_id']);
            }

            //Delete workorder time here
            if(array_key_exists('delete_time', $workorder)){
                $this->deleteTechnicianWorkorderTime($workorder['delete_time']);
            }

            //Add workorder part here
            if(array_key_exists('part', $workorder)){
                $this->addTechnicianWorkorderPart($workorder['part'],$workorder['workorder_id']);
            }

            //Delete workorder time here
            if(array_key_exists('delete_part', $workorder)){
                $this->deleteTechnicianWorkorderPart($workorder['delete_part']);
            }

            //Delete workorder files here
            if(array_key_exists('delete_files', $workorder))
            {
                $this->deleteTechnicianWorkorderFiles($workorder['delete_files']);
            }
            
            //Delete workorder files here
            if(array_key_exists('work_done', $workorder))
            {
                foreach($workorder['work_done'] as $workdone)
                {
                    $this->workorderWorkDoneComment($workdone['comment'],$workorder['workorder_id']);
                }
            }

            //$requested_data['work_done'] = $workorder['work_done'];
            $requested_data['workorder_status_id'] = $workorder['workorder_status_id'];
            Workorder::where('id',$workorder['workorder_id'])->update($requested_data);
        }
        
    }

    /**
     * add Technician Workorder Part
     *
     * @param  mixed $request
     * @param  mixed $workorder_id
     *
     * @return void
     */
    public function addTechnicianWorkorderPart($request,$workorder_id)
    {
        foreach($request as $part)
        {
            $model=new WorkorderPart;
            $requested_data = $part;
            $requested_data['company_id'] = Auth::user()->company_id;
            $requested_data['created_by_id'] = Auth::user()->id;
            $requested_data['name'] = $part['description'];
            $requested_data['workorder_id'] = $workorder_id;
            $requested_data['created_at'] = date('Y-m-d H:i:s');
            $requested_data['updated_at'] = date('Y-m-d H:i:s');
            $model->fill($requested_data);
            $model->save();
        }
    }
    
    /**
     * delete Technician Workorder Time
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function deleteTechnicianWorkorderTime($request)
    {  
        WorkorderTime::whereIn('id',$request)->update(['deleted_at' => date('Y-m-d H:i:s')]);
    }
    /**
     * delete Technician Workorder Part
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function deleteTechnicianWorkorderPart($request)
    {  
        WorkorderPart::whereIn('id',$request)->update(['deleted_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * delete Technician Workorder Files
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function deleteTechnicianWorkorderFiles($request)
    {  
        if(!empty($request))
        {
            WorkorderFile::whereIn('id',$request)->update(['deleted_at' => date('Y-m-d H:i:s')]);
        }
    }

    /**
     * add Technician Workorder Time
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function addTechnicianWorkorderTime($request,$workorder_id){
        //Case-1 If staff_id_2 is set but value not send in request
        //Then insert data only staff_id
        foreach($request as $request)
        {
            if( (array_key_exists('staff_id_2', $request)) && empty($request['staff_id_2'])){
                foreach($request['code_time'] as $key => $value){
                    if(!empty($value['code']) && !empty($value['time'])){
                        $data[$key]['company_id'] = Auth::user()->company_id;
                        $data[$key]['workorder_id'] = $workorder_id;
                        $data[$key]['staff_id'] = $request['staff_id'];
                        $data[$key]['date'] =  $request['date'];
                        $data[$key]['code'] = $value['code'];
                        $data[$key]['time'] = $value['time'];
                        $data[$key]['created_by_id'] = Auth::user()->id;
                        $data[$key]['created_at'] = date('Y-m-d H:i:s');
                        $data[$key]['updated_at'] = date('Y-m-d H:i:s');
                    }
                }
            //Case-2 If staff_id_2 is set and not null
            //Then insert data for staff_id and staff_id_2
            }else if( isset($request['staff_id_2']) && !empty($request['staff_id_2'])){
                foreach($request['code_time'] as $key => $value){
                    if(!empty($value['code']) && !empty($value['time'])){
                        $data[$key]['company_id'] = Auth::user()->company_id;
                        $data[$key]['workorder_id'] = $workorder_id;
                        $data[$key]['staff_id'] = $request['staff_id'];
                        $data[$key]['date'] =  $request['date'];
                        $data[$key]['code'] = $value['code'];
                        $data[$key]['time'] = $value['time'];
                        $data[$key]['created_by_id'] = Auth::user()->id;
                        $data[$key]['created_at'] = date('Y-m-d H:i:s');
                        $data[$key]['updated_at'] = date('Y-m-d H:i:s');
                    }
                }
                
                foreach($request['code_time'] as $keys => $values){
                    if(!empty($values['code']) && !empty($values['time'])){
                        $datas[$keys]['company_id'] = Auth::user()->company_id;
                        $datas[$keys]['workorder_id'] = $workorder_id;
                        $datas[$keys]['staff_id'] = $request['staff_id_2'];
                        $datas[$keys]['date'] =  $request['date'];
                        $datas[$keys]['code'] = $values['code'];
                        $datas[$keys]['time'] = $values['time'];
                        $datas[$keys]['created_by_id'] = Auth::user()->id;
                        $datas[$keys]['created_at'] = date('Y-m-d H:i:s');
                        $datas[$keys]['updated_at'] = date('Y-m-d H:i:s');
                    }
                }
                WorkorderTime::insert($datas);
            //Case-3 If staff_id_2 is not set in request
            //Then insert data for staff_id only not staff_id_2
            }else{
                foreach($request['code_time'] as $key => $value){
                    if(!empty($value['code']) && !empty($value['time'])){
                        $data[$key]['company_id'] = Auth::user()->company_id;
                        $data[$key]['workorder_id'] = $workorder_id;
                        $data[$key]['staff_id'] = $request['staff_id'];
                        $data[$key]['date'] =  $request['date'];
                        $data[$key]['code'] = $value['code'];
                        $data[$key]['time'] = $value['time'];
                        $data[$key]['created_by_id'] = Auth::user()->id;
                        $data[$key]['created_at'] = date('Y-m-d H:i:s');
                        $data[$key]['updated_at'] = date('Y-m-d H:i:s');
                    }
                }
            }
            WorkorderTime::insert($data);
        }
        
    }

    /**
     * delete Workorder Part
     *
     * @param  mixed $id
     *
     * @return void
     */
    public function deleteWorkorderPart($id)
    {
        return WorkorderPart::whereIn('id',$id)->update(['deleted_at' => date('Y-m-d H:i:s')]);
    }

    public function uploadDocumentFile($request)
    {
        //File path set here
        $file = $request->file('file');
        $destinationPath = 'public/document/';
        //Uploade File Here
        $file_orignal_name = $file->getClientOriginalName();
        $ext = $file->getClientOriginalExtension();
        $file_name = str_replace('.'.$ext,"", $file_orignal_name ).time().'.'.$ext;
        $file_name = str_replace(' ', '-', $file_name);
        $uploaded = Storage::put($destinationPath.$file_name, (string) file_get_contents($file), 'public');
        //Save File in Files Table
        if($uploaded) {
            $file_path = 'app/'.$destinationPath.$file_name;
            $request_data['file_name'] = $file_orignal_name;
            $request_data['path'] = $file_path;
            $request_data['file_type'] = 'document';
            $request_data['object_type'] = 'workorder_document' ;
            $request_data['object_id'] = $request->workorder_id ;
            $request_data['upload_by'] = Auth::user()->id;
            if($image = File::create($request_data)){
                //Save File Data in place file table
                $data['company_id'] = Auth::user()->company_id;
                $data['workorder_id'] = $request->workorder_id;
                $data['created_date'] = date('Y-m-d H:i:s');
                $data['ext'] = $ext;
                $data['description'] = ($request->has('description')) ? $request->description : '';
                $data['file_id'] = $image->id;
                if($workorder_file = WorkorderFile::create($data)){
                    return WorkorderFile::where('id',$workorder_file->id)->first();
                }
            }
        }
        return false;
    }

    public function workorderWorkDoneComment($comment,$workorder_id)
    {
        $requested_data['created_by'] = Auth::user()->id;
        $requested_data['workorder_id'] = $workorder_id;
        $requested_data['comment'] = $comment;
        $model=new WorkorderComment;
        $model->fill($requested_data);
        $model->save();
    }
    
}

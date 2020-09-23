<?php

namespace App\Repositories;


use DB;
use App\Exceptions\Handler;
use App\Repositories\BaseRepository;
use App\Models\User;
use App\Models\WorkorderTime;
use DateTime;
use Auth;
use Carbon\Carbon;
use App\Helpers\Helper;
use App\Repositories\WorkorderRepository;


/**
 * Class UserRepository.
 */
class UserRepository extends BaseRepository
{
    public function __construct(WorkorderRepository $workorderRepository){
        $this->workorderRepository = $workorderRepository;
    }

    /**
     * @return string
     */
    public function model()
    {
        return User::class;
    }

    /**
     * Get Staff Time Report
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function getStaffTimeDetailReport($request)
    {
        $all_days = $this->workorderRepository->getDatesFromRange($request->start_date, $request->end_date);
        $j=0;
        $final_array=array();
        foreach($all_days as $date)
        {
            if(empty($request->technician))
            {
                $workordertime=WorkorderTime::where('workorder_time.date', $date)
                    ->join('workorders', 'workorder_time.workorder_id', '=', 'workorders.id')
                    ->join('places', 'workorders.place_id', '=', 'places.id')
                    ->leftJoin('street_types', 'places.street_type_id', '=', 'street_types.id')
                    ->join('users', 'workorder_time.staff_id', '=', 'users.id')
                    ->select('workorder_time.*', DB::raw('(users.name) staffname,(users.first_name) firstname,(users.last_name) lastname,
                    (workorders.number) workordernumber,(places.suite) suite,(places.id) place_id,(places.street_number) street_number,(places.street_name) street_name,(street_types.street_type) street_types'))
                    ->get()->groupBy(['staff_id','workorder_id'])->toArray();
            }
            else
            {
                $workordertime=WorkorderTime::where('workorder_time.date', $date)
                    ->where('staff_id', $request->technician)
                    ->join('workorders', 'workorder_time.workorder_id', '=', 'workorders.id')
                    ->join('places', 'workorders.place_id', '=', 'places.id')
                    ->leftJoin('street_types', 'places.street_type_id', '=', 'street_types.id')
                    ->join('users', 'workorder_time.staff_id', '=', 'users.id')
                    ->select('workorder_time.*', DB::raw('(users.name) staffname,(users.first_name) firstname,(users.last_name) lastname,
                    (workorders.number) workordernumber,(places.id) place_id,(places.suite) suite,(places.street_number) street_number,(places.street_name) street_name,(street_types.street_type) street_types'))
                    ->get()->groupBy(['staff_id','workorder_id'])->toArray();
            }
            
            $i=0;$staff_time=array();$c=0;
            foreach($workordertime as $index=>$workorderwise)
            {
                $i=0;
                foreach($workorderwise as $time)
                {
                    $minutes=0;
                    foreach($time as $timedetail)
                    {
                        $minutes=$minutes+$timedetail['time'];
                    }
                    if(!empty($minutes))
                    {
                        $suite = !empty($time[0]['suite']) ? '#'.$time[0]['suite'].' ' : '';
                        $street_number = $time[0]['street_number'];
                        $street_name = $time[0]['street_name'];
                        $street_types = isset($time[0]['street_types']) && !empty($time[0]['street_types']) ? $time[0]['street_types'] : '';
                        $minutes = Helper::secondToHourConvert($minutes);
                        $staff_time[$i]['time']=number_format((float)$minutes, 2, '.', '');
                        $staff_time[$i]['workordernumber']=$time[0]['workordernumber'];
                        $staff_time[$i]['workorderid']=$time[0]['workorder_id'];
                        $staff_time[$i]['date']=$time[0]['date'];
                        $staff_time[$i]['staffid']=$time[0]['staff_id'];
                        $staff_time[$i]['staffname']=$time[0]['firstname'].' '.$time[0]['lastname'];
                        $staff_time[$i]['place_id']=$time[0]['place_id'];
                        $staff_time[$i]['placedetail']= $suite.$street_number.' '.$street_name.' '.$street_types;
                        $staff_name=$time[0]['firstname'].' '.$time[0]['lastname'];
                        $staff_id=$time[0]['staff_id'];
                        $i++;
                    }
                }

                if(in_array($index, array_column($staff_time,'staffid')))
                {
                    $total=array_sum(array_column($staff_time, 'time'));
                    $staffwise[$c]['staff_id']=$staff_id;
                    $staffwise[$c]['staffname']=$staff_name;
                    $staffwise[$c]['total']=number_format((float)$total, 2, '.', '');
                    $staffwise[$c]['time_detail']=$staff_time;
                    $c++;
                }
                $technician_list = User::select('id','name','first_name','last_name')->where('company_id',Auth::user()->company_id)->where('technician',1)->where('active',1)->get()->toArray();
                
            }

            if(!empty($staff_time))
            {
                
                if(empty($request->technician))
                {
                    $count=count($staffwise);
                    
                    foreach($technician_list as $technicians)
                    {
                        if(!in_array($technicians['id'], array_column($staffwise,'staff_id')))
                        {
                            $total=0.0;
                            $staffwise[$count]['staff_id']=$technicians['id'];
                            $staffwise[$count]['staffname']=$technicians['first_name'].' '.$technicians['last_name'];
                            $staffwise[$count]['total']=number_format((float)$total, 2, '.', '');
                            $staffwise[$count]['time_detail']=number_format((float)$total, 2, '.', '');
                            $count++;
                        }
                    }
                }
               
                $final_array[$j]['date']=$date;
                $final_array[$j]['details']=$staffwise;
                $staffwise=array();
                $j++;
            }  
        }
        return $final_array;
    }

    /**
     * Get Staff Print Payroll Report
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function getStaffPrintpayrollReport($request)
    {
        if(empty($request->technician))
        {
            $workordertime=WorkorderTime::whereBetween('workorder_time.date', [$request->start_date, $request->end_date])
                    ->join('workorders', 'workorder_time.workorder_id', '=', 'workorders.id')
                    ->join('places', 'workorders.place_id', '=', 'places.id')
                    ->join('users', 'workorder_time.staff_id', '=', 'users.id')
                    ->select('workorder_time.*', DB::raw('(users.name) staffname,(users.first_name) firstname,(users.last_name) lastname,
                    (workorders.number) workordernumber,(places.street_number) street_number,(places.street_name) street_name'))
                    ->get()->groupBy(['staff_id','date','code'])->toArray();                   
        }
        else
        {
            $workordertime=WorkorderTime::whereBetween('workorder_time.date', [$request->start_date, $request->end_date])
                ->where('staff_id', $request->technician)
                ->join('workorders', 'workorder_time.workorder_id', '=', 'workorders.id')
                ->join('places', 'workorders.place_id', '=', 'places.id')
                ->join('users', 'workorder_time.staff_id', '=', 'users.id')
                ->select('workorder_time.*', DB::raw('(users.name) staffname,(users.first_name) firstname,(users.last_name) lastname,
                (workorders.number) workordernumber,(places.street_number) street_number,(places.street_name) street_name'))
                ->orderBy('code')
                ->get()->groupBy(['staff_id','date','code'])->toArray();                   
        }
        $i=0;
        $datedetail=array();
        foreach($workordertime as $staffid=>$timedetail)
        {
            $j=0;
            foreach($timedetail as $timedate=>$datewisedata)
            {
                
                $timecodecheck=1;$datewisetime=array();
                for($loop=0;$loop<=4;$loop++)
                {
                    foreach($datewisedata as $timecode=>$codewisedata)
                    {
                        //Set staffname and id
                        $staffname=$codewisedata[0]['firstname'].' '.$codewisedata[0]['lastname'];
                        $staffid=$codewisedata[0]['staff_id'];

                        $time_code_value=substr($timecode, -1);
                    
                            if($loop==$time_code_value)
                            {
                                $minutes=0;

                                foreach($codewisedata as $data)
                                {
                                    $minutes=$minutes+$data['time'];
                                }
                                $minutes = Helper::secondToHourConvert($minutes);
                                    
                            }
                        $timecodecheck++;

                    }
                    if(!empty($minutes))
                    {
                        $datewisetime['datetime'][$loop]=number_format((float)$minutes, 2, '.', '');
                        $minutes=0;
                    }
                    else
                    {
                        $datewisetime['datetime'][$loop]=0.00;
                    }

                }

                $datedetail[$j]['date']=$timedate;
                $datedetail[$j]['time']=$datewisetime['datetime'];
                $j++;
                
            }
            
            $totaltimevalue=array();
            foreach($datedetail as $data)
            {
                foreach($data['time'] as $key1=>$data2)
                {
                    if(!empty($totaltimevalue[$key1]))
                    {
                        $totaltimevalue[$key1]+=$data2;
                    }
                    else
                    {
                        $totaltimevalue[$key1]=$data2;
                    }
                    
                }
            }
            
            if(!empty($datedetail))
            {
                $final_array[$i]['staff_id']=$staffid;
                $final_array[$i]['staff_name']=$staffname;
                $final_array[$i]['timedetail']['totaltime']=$totaltimevalue;
                $final_array[$i]['timedetail']['dates']=$datedetail;
                $i++;
            }
        }
        return $final_array; 
    }
   
}

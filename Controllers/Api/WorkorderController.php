<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\ApiController;
use App\Models\Role;
use App\Models\User;
use App\Models\Workorder;
use App\Models\WorkorderType;
use App\Models\WorkorderStatus;
use App\Models\WorkorderPart;
use App\Models\WorkorderTime;
use App\Models\WorkorderSchedule;
use App\Models\StaffRole;
use App\Models\PlacesManagementType;
use App\Models\PlacesManagement;
use App\Models\PurchaseOrderItem;
use App\Models\WorkorderFile;
use App\Models\QuoteItem;
use App\Models\Part;
use App\Transformers\UserTransformer;
use App\Transformers\WorkorderTransformer;
use App\Transformers\WorkorderPartTransformer;
use App\Transformers\WorkorderTimeTransformer;
use App\Transformers\WorkorderScheduleTransformer;
use App\Transformers\PurchaseOrderItemTransformer;
use App\Transformers\WorkorderFileTransformer;
use App\Http\Requests\Api\Workorders\Index;
use App\Http\Requests\Api\Workorders\Show;
use App\Http\Requests\Api\Workorders\Create;
use App\Http\Requests\Api\Workorders\Store;
use App\Http\Requests\Api\Workorders\Edit;
use App\Http\Requests\Api\Workorders\Update;
use App\Http\Requests\Api\Workorders\Destroy;
use App\Http\Requests\Api\Workorders\StoreWorkorderPart;
use App\Http\Requests\Api\Workorders\DeleteWorkorderPart;
use App\Http\Requests\Api\Workorders\DeleteWorkorderTime;
use App\Http\Requests\Api\Workorders\DeleteWorkorderSchedule;
use App\Http\Requests\Api\Workorders\StoreWorkorderTime;
use App\Http\Requests\Api\Workorders\StoreWorkorderSchedule;
use App\Http\Requests\Api\Workorders\ScheduleReport;
use App\Http\Requests\Api\Workorders\ShopWorkorderReport;
use App\Http\Requests\Api\Workorders\CheckPlace;
use App\Http\Requests\Api\Workorders\DeleteWorkorderFiles;
use App\Repositories\WorkorderRepository;
use App\Exports\UsersExport;
use Excel;
use Auth;
use App\Helpers\Helper;
use Response;
use DB;

/**
 * Workorder
 *
 * @Resource("Workorder", uri="/workorders")
 */

class WorkorderController extends ApiController
{
    /*Construct here define Workorder repository */
    public function __construct(WorkorderRepository $workorderRepository){
        $this->workorderRepository = $workorderRepository;
    }

    /**
     * Get Workorder Listing with Filters
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function index(Index $request)
    {
       
        //Set Sort by & Sort by Column
        $sortBy = Helper::setSortByValue($request);

        //Set Per Page Record
        $per_page = Helper::setPerPage($request);

        $workorder = Workorder::select('*');

        //Search Filter For Work Order Number
        if($request->has('number') && !empty($request->number) ){

            $number=$request->number;
            $count=strlen($number);
            //Add dash in the workorder no
            if( $count>3 && (substr($number, 4, 1))!='-' )
            {
                $number=substr_replace( $number,'-', 4, 0 );
            }
            if($count>7 && (substr($number, 9, 1))!='-')
            {
                $number=substr_replace( $number,'-', 9, 0 );
            }
                
            $workorder->where('workorders.number','like','%'.$number.'%');
        }

        //Search Filter For Work Order Status
        if($request->has(STATUS) && !empty($request->status)){
            $workorder->where('workorder_status_id',$request->status);
        }
        
        //Search Filter For Work Order Type
        if($request->has('type') && !empty($request->type)){
            $workorder->where('workorder_type_id',$request->type);
        }

        //Search Filter For Purchase Order
        if($request->has('purchase_order') && !empty($request->purchase_order)){
            $workorder->where('purchase_order','like','%'.$request->purchase_order.'%');
        }

        //If place id send in request then get only selected place wordorder
        if($request->has(PLACE_ID) && !empty($request->place_id)){
            $workorder->where('workorders.place_id',$request->place_id);
        }

        $workorder->join('workorder_statuses', 'workorders.workorder_status_id', '=', 'workorder_statuses.id')
                ->join('workorder_types', 'workorders.workorder_type_id', '=', 'workorder_types.id')
                ->join('places', 'workorders.place_id', '=', 'places.id')
                ->join('places_management', 'workorders.bill_to_id', '=', 'places_management.id')
                ->join('management', 'places_management.management_id', '=', 'management.id')
                ->select('workorders.*', DB::raw('(workorder_statuses.workorder_status) workorderstatus,(workorder_types.workorder_type) workordertype,(places.suite) placesuite,(management.name) billtomanage'));
            
            return $this->response->paginator($workorder->orderBy($sortBy['column'], $sortBy['order'])->paginate($per_page), new WorkorderTransformer());
    }

    /**
     * Get Single Workorder Detail
     *
     * @param  mixed $request
     * @param  mixed $workorder
     *
     * @return void
     */
    public function show(Show $request, $workorder)
    {
        $workorder = Workorder::where('id', $workorder)->first();
        
        if($workorder){
            return $this->response->item($workorder, new WorkorderTransformer()); 
        }
        return response()->json([MESSAGE=>'Work order not found. Please try again.'], 404);
    }

    /**
     * Create new workorder
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function store(Store $request)
    {
        $model=new Workorder;
        $requested_data = $request->all();
        $requested_data['company_id'] = Auth::user()->company_id;
        $requested_data['created_by_id'] = Auth::user()->id;
        $requested_data['created_date'] = date('Y-m-d H:i:s');
        unset($requested_data['work_done']);
        $model->fill($requested_data);
        if ($model->save()) {
            $workorder_id = $model->id;
            
            //Set Number here
            if($request->has('workorder_number') && !empty($request->workorder_number)){
                $explode_number = explode("-",$request->workorder_number);
                $number = (integer) $explode_number[2]+ 1;
                $number = strlen($number) == 1 ? '0'.$number: $number;
                $number = $explode_number[0].'-'.$explode_number[1].'-'.$number;
            }else{
                $workorder_number = Workorder::where(COMPANY_ID,Auth::user()->company_id)->whereRaw('MONTH(created_at) = ?',[date('m')])->count();
                $number = Helper::setWorkorderNumber($workorder_number);
            }
            #Copy Quote Items When Status Repair and Quote id not null
            
            if($request->workorder_type_id == 8 && $request->has('quote_id') && !empty($request->quote_id) ){
                $quoteItem = QuoteItem::where('quote_id',$request->quote_id)->get()->toArray();
                
                if(!empty($quoteItem)){
                    foreach($quoteItem as $key=>$value){
                        $part = null;
                        $part = Part::where('id',$value['part_id'])->first();
                        $models=new WorkorderPart;
                        $requested['workorder_id'] = $workorder_id;
                        $requested['part_id'] = $value['part_id'];
                        $requested['name'] = ($part) ? $part->name : '';
                        $requested['quantity_ordered'] = $value['quantity'];
                        $requested['quantity_sold'] = 0;
                        $requested['company_id'] = Auth::user()->company_id;
                        $requested['created_by_id'] = Auth::user()->id;
                        $requested['created_at'] = date('Y-m-d H:i:s');
                        $requested['updated_at'] = date('Y-m-d H:i:s');
                        if(!empty($requested)){
                            $models->fill($requested);
                            $models->save();
                        }
                    }
                }
            }
            Workorder::where('id',$model->id)->update(['number' => $number]);
            $model->number=$number;

            //Add Workorder workdone comment
            if(!empty($request->work_done))  
            {
                $this->workorderRepository->workorderWorkDoneComment($request->work_done,$model->id);        
            }

            return $this->response->item($model, new WorkOrderTransformer());
        } else {
            return $this->response->errorInternal('Error occurred while saving work order.');
        }
    }
    
    /**
     * Check Property Managment For Place
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function checkPropertyManagmentForPlace(CheckPlace $request){
        $owner_place_management_type = PlacesManagementType::where('place_management_type','Owner')->first();
        $property_place_management_type = PlacesManagementType::where('place_management_type','Property Manager')->first();
        $place_management = PlacesManagement::where(PLACE_ID,$request->place_id)->where('type_id',$owner_place_management_type->id)->first();
        if($place_management){
            $place_managements = PlacesManagement::where(PLACE_ID,$request->place_id)->where('type_id',$property_place_management_type->id)->first();
            if($place_managements){
                return $this->response->array([STATUS => 200, MESSAGE => 'Property manager and owner manager is available.']);
            }
            return $this->response->array([STATUS => 466, MESSAGE => 'This place needs and owner and property manager attached before you can create a work order.']);
        }else{
            return $this->response->array([STATUS => 466, MESSAGE => 'This place needs and owner and property manager attached before you can create a work order.']);
        }
    }

    /**
     * update Workorder
     *
     * @param  mixed $request
     * @param  mixed $workorder
     *
     * @return void
     */
    public function update(Update $request, $workorder)
    {
        $requested_data = $request->all();
        $requested_data['completed_date'] = isset($requested_data['workorder_status_id']) && $requested_data['workorder_status_id'] == '3'  ? date('Y-m-d H:i:s') : NULL;
        unset($requested_data['work_done']);
        Workorder::where('id',$requested_data['id'])->update($requested_data);

        //Add Workorder workdone comment
        if(!empty($request->work_done))
        {
            $this->workorderRepository->workorderWorkDoneComment($request->work_done,$requested_data['id']);        
        }

        $workorder = Workorder::where('id',$requested_data['id'])->first();
        return $this->response->item($workorder, new WorkorderTransformer());
    }

    /**
     * destroy Workorder
     *
     * @param  mixed $request
     * @param  mixed $workorder
     *
     * @return void
     */
    public function destroy(Destroy $request, $workorder)
    {
        if(Workorder::where(COMPANY_ID,Auth::user()->company_id)->where('id',$workorder)->first()){
            $workorder = Workorder::findOrFail($workorder);
            if ($workorder->delete()) {
                return $this->response->array([STATUS => 200, MESSAGE => 'Work order successfully deleted.']);
            } else {
                 return $this->response->errorInternal('Error occurred while deleting work order.');
            }
        }
        return $this->response->errorInternal('Work order not found. Please try again.');
    }

    /**
     * Get Workorder Type
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function getWorkorderType(Request $request)
    {
        $worktype=WorkorderType::get()->toArray();
        if($worktype){
            return $this->response->array(['data' => $worktype]);
        }
        return $this->response->errorInternal('Workorder type not found, please try again.');
    }
    
    /**
     * Get Workorder Status
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function getWorkorderStatus(Request $request)
    {
        $workstatus=WorkorderStatus::get()->toArray();
        if($workstatus){
            return $this->response->array(['data' => $workstatus]);
        }
        return $this->response->errorInternal('Work order status not found. Please try again.');
    }
    
    /**
     * Add Workorder Part
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function storeWorkorderPart(StoreWorkorderPart $request)
    {
        $this->workorderRepository->addWorkorderPart($request);
        return $this->response->array([STATUS => 200, MESSAGE => 'Part has been added under the work order.']);        
    }

    /**
     * update Technician Workorder
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function updateTechnicianWorkorder(Request $request)
    {
        $this->workorderRepository->updateTechnicianWorkorderDetails($request);
        return $this->response->array([STATUS => 200, MESSAGE => 'Work order information updated.']);
        
    }

    /**
     * Delete Workorder Part
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function deleteWorkorderPart(DeleteWorkorderPart $request)
    {  
        $part = $this->workorderRepository->deleteWorkorderPart($request->id);
        if($part)
        {
            return $this->response->array([STATUS => 200, MESSAGE => DELETED_SUCCESSFULLY]);
        }
        return $this->response->errorInternal(DELETE_ERROR_MSG);        
    }

    /**
     * Get Workorder Part
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function getWorkorderPart(Request $request)
    {
        //Set Sort by & Sort by Column
        $sortBy = Helper::setSortByValue($request);

        //Set Per Page Record
        $per_page = Helper::setPerPage($request);
        if($request->has(WORKORDER_ID) && !empty($request->workorder_id)){
            $workorderpart = $this->workorderRepository->getWorkorderPart(WORKORDER_ID ,$request->workorder_id);
            
            return $this->response->paginator($workorderpart->orderBy($sortBy['column'], $sortBy['order'])->paginate($per_page), new WorkorderPartTransformer());
        }
        return $this->response->errorInternal('Please send work order id.');
    }

    /**
     * Store Workorder Time
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function storeWorkorderTime(StoreWorkorderTime $request)
    {
        $this->workorderRepository->addWorkorderTime($request); 
        return $this->response->array([STATUS => 200, MESSAGE => 'Time has been added.']);
    }

    /**
     * get Workorder Time
     *
     * @param  mixed $request
     * @param  mixed $workorder_id
     *
     * @return void
     */
    public function getWorkorderTime(Request $request, $workorder_id)
    {   
        //Set Sort by & Sort by Column
        $sortBy = Helper::setSortByValue($request);

        //Set Per Page Record
        $per_page = Helper::setPerPage($request);
        $workorder_time = WorkorderTime::where(WORKORDER_ID,$workorder_id)->where('workorder_time.company_id',Auth::user()->company_id);
        
        $workorder_time->join('users', 'workorder_time.staff_id', '=', 'users.id')
                ->select('workorder_time.*', DB::raw('(users.name) technician'));


        return $this->response->paginator($workorder_time->orderBy($sortBy['column'], $sortBy['order'])->paginate($per_page), new WorkorderTimeTransformer());
    }

    /**
     * Delete Workorder Time
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function deleteWorkorderTime(DeleteWorkorderTime $request)
    {  
        if(WorkorderTime::whereIn('id',$request->id)->update([DELETED_AT => date('Y-m-d H:i:s')])){
            return $this->response->array([STATUS => 200, MESSAGE => DELETED_SUCCESSFULLY]);
        }
        return $this->response->errorInternal(DELETE_ERROR_MSG);
    }

    /**
     * Store Workorder Schedule
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function storeWorkorderSchedule(StoreWorkorderSchedule $request)
    {
        $this->workorderRepository->addWorkorderSchedule($request);
        return $this->response->array([STATUS => 200, MESSAGE => 'Appointment has been added.']);
    }

    /**
     * Delete Workorder Schedule
     * 
     * @param  mixed $request
     *
     * @return void
     */         
    public function deleteWorkorderSchedule(DeleteWorkorderSchedule $request)
    {  
        if(WorkorderSchedule::whereIn('id',$request->id)->update([DELETED_AT => date('Y-m-d H:i:s')])){
            return $this->response->array([STATUS => 200, MESSAGE => DELETED_SUCCESSFULLY]);
        }
        return $this->response->errorInternal(DELETE_ERROR_MSG);
    }

    /**
     * Get Workorder Schedule
     *
     * @param  mixed $request
     * @param  mixed $workorder_id
     *
     * @return void
     */
    public function getWorkorderSchedule(Request $request, $workorder_id)
    {
        //Set Sort by & Sort by Column
        $sortBy = Helper::setSortByValue($request);

        //Set Per Page Record
        $per_page = Helper::setPerPage($request);
        $workorderschedule = WorkorderSchedule::where(WORKORDER_ID,$workorder_id)->where('workorder_schedules.company_id',Auth::user()->company_id);
        
        $workorderschedule->join('users', 'workorder_schedules.staff_id', '=', 'users.id')
                ->select('workorder_schedules.*', DB::raw('(users.name) technician'));

        return $this->response->paginator($workorderschedule->orderBy($sortBy['column'], $sortBy['order'])->paginate($per_page), new WorkorderScheduleTransformer());
    }

    /**
     * Get Weekly Schedule Report
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function getWeeklyScheduleReport(Request $request)
    {
        $role = Role::where('role','User')->first();
        $schedule = $this->workorderRepository->getWeeklyWorkorderScheduleReport($request, $role);
        return $this->response->array([STATUS => 200, 'technician' => $schedule['technician'] ,'data' => $schedule['workorder'], 'next_date' => $schedule['next_week'], 'privous_date' => $schedule['previous_week'] ]);
    }
    
    /**
     * download Weekly Schedule Report
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function downloadWeeklyScheduleReport(Request $request)
    {
        $role = Role::where('role','User')->first();
        $schedule = $this->workorderRepository->getWeeklyWorkorderScheduleReport($request, $role);
        $count=0;
        //Declare the array for weekly report
        $downloadreport=array();
        //Add the heading of the excel columns
        array_push($downloadreport,array('Date','Staff Id','Name','Start Time', 'Hours'));
        //loop for every day of the week
        foreach($schedule['workorder'] as $data) {
            $date=$schedule['workorder'][$count]['date'];
            //for every user schedule
            foreach($schedule['workorder'][$count]['schedule'] as $data)
            {
                //Check if user has schedule or not
                if(!empty($data['schedule'])){
                    array_push($downloadreport,array($date,$data['id'],$data['name'],$data['schedule']['start_time'],$data['schedule']['hours']));
                }
                else{
                    array_push($downloadreport,array($date,$data['id'],$data['name'],'',''));
                }
            }
           $count++;
        }
        //To download the excel
        $export = new UsersExport([
            $downloadreport
            
        ]);
        return Excel::download($export, 'weeklyschedule.csv');
    }

    /**
     * Get Workorder Schedule Daily
     *
     * @param  mixed $request
     * @param  mixed $workorder_id
     *
     * @return void
     */
    public function getWorkorderScheduleDaily(Request $request)
    {
        //Start Date Value
        $start_date = $request->has(START_DATE) && !empty($request->start_date) ? $request->start_date : date('Y-m-d');
        $role = Role::where('role','User')->first();
        $report = $this->workorderRepository->getWorkorderScheduleDaily($request,$role,$start_date);        
        return $this->response->array([STATUS => 200, 'data' => $report]);
    }

    /**
     * Download Workorder Schedule Daily
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function downloadWorkorderScheduleDaily(Request $request)
    {
        //Start Date Value
        $start_date = $request->has(START_DATE) && !empty($request->start_date) ? $request->start_date : date('Y-m-d');
        $role = Role::where('role','User')->first();
        
        $table = $this->workorderRepository->getWorkorderScheduleDaily($request,$role,$start_date);
        $count=0;
        //Declare the array for daily report
        $downloadreport=array();
        //Add the heading of the excel columns
        array_push($downloadreport,array('Staff Id','Staff Fname','Staff Lname','Start Date','Start Time', 'Hours'));
        foreach($table as $data) {
            //Check workorder schedule count
            $schedule_count=count($table[$count]['workorder_schedule']);
            //If user has workorder schedule
            if(!empty($schedule_count))
            {
                //for every schedule of the user
                foreach($data['workorder_schedule'] as $workschdeule)
                {
                    array_push($downloadreport,array($data['id'],$data['first_name'],$data['last_name'],$workschdeule['start_date'],$workschdeule['start_time'],$workschdeule['hours']));
                }
            }
            else{
                array_push($downloadreport,array($data['id'],$data['first_name'],$data['last_name'],'','',''));
            }
           $count++;
        }
        
        //To download the excel
        $export = new UsersExport([
            $downloadreport
            
        ]);
        return Excel::download($export, 'dailyschedule.csv');
    }

    /**
     * Get Schedule Report
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function getScheduleReport(ScheduleReport $request)
    {
        $role = Role::where('role','User')->first();
        $report = $this->workorderRepository->getScheduleReport($request,$role);
        return $this->response->array([STATUS => 200, 'data' => $report ]);
    }

    /**
     * download Schedule Report
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function downloadScheduleReport(Request $request)
    {
        $role = Role::where('role','User')->first();
        $report = $this->workorderRepository->getScheduleReport($request,$role);
        //Declare the array for daily report
        $downloadreport=array();
        //Add the heading of the excel columns
        array_push($downloadreport,array('Date','Staff Id','Name','Workorder Number','Start Time', 'Hours','Workorder Type','Place Address'));
        $count=0;
        
        //loop for every date
        foreach($report as $data) {
            $date=$data['date'];
            //If the report type is 'Summary By Technician'
            if($request->report == 'summary_by_tech')
            {
                //for every user schedule
                foreach($data['schedule'] as $workorder)
                {
                    $staffname=$workorder['name'];
                    foreach($workorder['workorder_schedule'] as $schedule)
                    {
                        $placeaddress=$schedule['workorder']['place']['street_number'].' '.$schedule['workorder']['place']['street_name'];
                        array_push($downloadreport,array($date,$schedule['staff_id'],$staffname,$schedule['workorder']['number'],$schedule['start_time'],$schedule['hours'],$schedule['workorder']['type']['workorder_type'],$placeaddress));
                    }
                    
                }
            }
            //If the report type is 'Summary By Workorder'
            else
            {
                //for every user schedule 
                foreach($data['schedule'] as $workorder)
                {
                    $placeaddress=$workorder['workorder']['place']['street_number'].' '.$workorder['workorder']['place']['street_name'];
                    array_push($downloadreport,array($date,$workorder['staff_id'],$workorder['staff']['name'],$workorder['workorder']['number'],$workorder['start_time'],$workorder['hours'],$workorder['workorder']['type']['workorder_type'],$placeaddress));
                }
            }
        $count++;
        }

        //To download the excel
        $export = new UsersExport([
            $downloadreport
            
        ]);
        return Excel::download($export, 'shopworkorder.csv');
    }

    /**
     * Get Shop Workorder Report
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function getShopWorkorderReport(ShopWorkorderReport $request)
    {
        $role = Role::where('role','User')->first();
        $report = $this->workorderRepository->getShopWorkorderReport($request,$role);
        return $this->response->array([STATUS => 200, 'data' => $report['final_array'] ,'technician' => $report['technician']]);
    }

    /**
     * Download Shop Workorder Report
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function downloadShopWorkorderReport(Request $request)
    {
        $role = Role::where('role','User')->first();
        $report = $this->workorderRepository->getShopWorkorderReport($request,$role);
        $count=0;
        //Declare the array for daily report
        $downloadreport=array();
        //Add the heading of the excel columns
        array_push($downloadreport,array('Date','Staff Id','Name','Workorder Number','Start Time', 'Hours'));
        //loop for every date
        foreach($report['final_array'] as $data) {
            $date=$report['final_array'][$count]['date'];
            //for every user schedule
            foreach($report['final_array'][$count]['schedule'] as $data2)
            {
                //Check if user has schedule or not
                if(!empty($data2['schedule'])){
                    array_push($downloadreport,array($date,$data2['id'],$data2['name'],$data2['schedule']['workorder']['number'],$data2['schedule']['start_time'],$data2['schedule']['hours']));
                }
                else{
                    array_push($downloadreport,array($date,$data2['id'],$data2['name'],'','',''));
                }
            }
           $count++;
        }
        //To download the excel
        $export = new UsersExport([
            $downloadreport
            
        ]);
        return Excel::download($export, 'shopworkorder.csv');        
    }
    
    /**
     * post Shop Workorder
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function postShopWorkorder(ShopWorkorderReport $request)
    {
        $result = $this->workorderRepository->postShopWorkorder($request);
        return response()->json([MESSAGE=>$result['message']], $result[STATUS]);
        //return $this->response->array([STATUS => $result[STATUS], MESSAGE => $result['message'] ]);
    }

    /**
     * get Workorder Purchase Order
     *
     * @param  mixed $request
     * @param  mixed $workorder_id
     *
     * @return void
     */
    public function getWorkorderPurchaseOrder(Request $request, $workorder_id)
    {
        //Set Per Page Record
        $per_page = Helper::setPerPage($request);
        $purchaseorderitem = PurchaseOrderItem::where(WORKORDER_ID,$workorder_id)->where(COMPANY_ID,Auth::user()->company_id);
        return $this->response->paginator($purchaseorderitem->paginate($per_page), new PurchaseOrderItemTransformer());
    }

    /**
     * Workorder Technician Wise
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function workorderTechnician(Request $request)
    {
        $result = $this->workorderRepository->workorderTechnician();
        return $this->response->array([STATUS => 200, 'workorder' => $result['final_array'],'timedetail' => $result['timearray'] ]);
    }

    /**
     * Inspection Technician Wise
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function inspectionTechnician(Request $request)
    {
        $result = $this->workorderRepository->inspectionTechnician();
        return $this->response->array([STATUS => 200, 'inspection' => $result ]);
    }

    /**
     * workorder Technician File Upload
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function workorderTechnicianFileUpload(Request $request){
        if($workorderfile = $this->workorderRepository->uploadDocumentFile($request)){
            return $this->response->item($workorderfile, new WorkorderFileTransformer());
        }
        return $this->response->errorInternal('Error while uploading file in management. Please try again.');
    }

    public function getWorkorderFiles(Request $request)
    {
        //Set Sort by & Sort by Column
        $sortBy = Helper::setSortByValue($request);

        //Set Per Page Record
        $per_page = Helper::setPerPage($request);

        if($request->has(WORKORDER_ID) && !empty($request->workorder_id)){
            //Start Place File Query
            $placefile = WorkorderFile::where(COMPANY_ID,Auth::user()->company_id);
            $placefile->where(WORKORDER_ID, $request->workorder_id );

    
            return $this->response->paginator($placefile->orderBy($sortBy['column'], $sortBy['order'])->paginate($per_page), new WorkorderFileTransformer());
        }
        return $this->response->errorInternal('Please send workorder_id.');
    }

    public function deleteWorkorderFiles(DeleteWorkorderFiles $request)
    {
        if(WorkorderFile::whereIn('id',$request->id)->update([DELETED_AT => date('Y-m-d H:i:s')])){
            return $this->response->array([STATUS => 200, MESSAGE => DELETED_SUCCESSFULLY]);
        }
        return $this->response->errorInternal(DELETE_ERROR_MSG);
    }

}

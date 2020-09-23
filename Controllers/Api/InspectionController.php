<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\ApiController;
use App\Models\Inspection;
use App\Models\InspectionArea;
use App\Models\InspectionDevice;
use App\Models\FormTemplateField;
use App\Models\FormTemplate; 
use App\Models\ManufacturersModel; 
use App\Models\InspectionStatus;
use App\Models\DeviceType;
use App\Models\InspectionDeviceDeficiencyFile;
use App\Models\InspectionDeviceNote;
use App\Transformers\InspectionTransformer;
use App\Transformers\InspectionAreaTransformer;
use App\Transformers\InspectionDeviceTransformer;
use App\Transformers\FormTemplateFieldTransformer;
use App\Transformers\InspectionDeviceNoteTransformer;
use App\Http\Requests\Api\Inspections\Index;
use App\Http\Requests\Api\Inspections\Show;
use App\Http\Requests\Api\Inspections\Create;
use App\Http\Requests\Api\Inspections\Store;
use App\Http\Requests\Api\Inspections\Edit;
use App\Http\Requests\Api\Inspections\Update;
use App\Http\Requests\Api\Inspections\Destroy;
use App\Http\Requests\Api\Inspections\DeletePlaceInspection;
use App\Http\Requests\Api\Inspections\StoreInspectionArea;
use App\Http\Requests\Api\Inspections\UpdateInspectionArea;
use App\Http\Requests\Api\Inspections\DeleteInspectionArea;
use App\Http\Requests\Api\Inspections\StoreInspectionDevice;
use App\Http\Requests\Api\Inspections\UpdateInspectionDevice;
use App\Http\Requests\Api\Inspections\DeleteInspectionDevice;
use App\Http\Requests\Api\Inspections\UpdateInspectionDeviceCheck;
use App\Http\Requests\Api\Inspections\UpdateInspectionDeviceDeficiency;
use App\Http\Requests\Api\Inspections\StoreInspectionDeviceForm;
use App\Http\Requests\Api\Inspections\UpdateMultipleInspection;
use App\Repositories\InspectionRepository;
use App\Helpers\Helper;
use Auth;
use DB;

/**
 * Inspection
 *
 * @Resource("Inspection", uri="/inspections")
 */

class InspectionController extends ApiController
{
    /*Construct here define inspection repository */
    public function __construct(InspectionRepository $inspectionRepository){
        $this->inspectionRepository = $inspectionRepository;
      }
    
    /**
     * Inspection List
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function index(Index $request)
    {
        #Set Per Page Record
        $per_page = Helper::setPerPage($request);

        $inspection = Inspection::select('*')->orderBy('created_at','desc');

        #Search Filter For Inspection Date
        if($request->has('date') && !empty($request->date)){
            $inspection->where('inspection_date',$request->date);
        }

        #Search Filter For Inspection Type
        if($request->has('inspection_type') && !empty($request->inspection_type)){
            $inspection->where('inspection_type_id',$request->inspection_type);
        }

        #Search Filter For Inspection Status
        if($request->has('inspection_status') && !empty($request->inspection_status)){
            $inspection->where('inspection_status_id',$request->inspection_status);
        }
        return $this->response->paginator($inspection->paginate($per_page), new InspectionTransformer());
    }

    /**
     * show
     *
     * @param  mixed $request
     * @param  mixed $inspection
     *
     * @return void
     */
    public function show(Show $request, $inspection)
    {
        $inspection = Inspection::where('id',$inspection)->first();
        if($inspection){
            return $this->response->item($inspection, new InspectionTransformer()); 
        }
        return response()->json([MESSAGE=>'No matching records found.'], 404);
    }

    /**
     * Store Inspection
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function store(Store $request)
    { 
        foreach($request->inspection as $value){
           
            $model=new Inspection;
            $requested_data = array();
            $requested_data['inspection_status_id'] = 1;
            $requested_data['created_date'] = date('Y-m-d');
            $requested_data['place_id'] = $value['place_id'];
            $requested_data['inspection_type_id'] = $value['inspection_type_id'];
            $requested_data['place_inspection_id'] = $value['place_inspection_id'];
            $model->fill($requested_data);
            if ($model->save()) {
                $inspection_number = Inspection::where('company_id',Auth::user()->company_id)->withTrashed()->count();
                $number= Helper::setNumberCompanyWise($inspection_number,'inspections'); 
                Inspection::where('id',$model->id)->update(['number' => $number]);

                $this->inspectionRepository->insertDataFromPlaceDevice($model->id, $value['place_inspection_id']);

                $model->number=$number;
            }
        }
        return $this->response->array([STATUS => 200, MESSAGE => 'Inspection has been created successfully.']);
    }
 
    /**
     * Update Inspection
     *
     * @param  mixed $request
     * @param  mixed $inspection
     *
     * @return void
     */
    public function update(Update $request, $inspection)
    {
        $this->inspectionRepository->updateInspection($request);
        return $this->response->array([STATUS => 200, MESSAGE => UPDATED_SUCCESSFULLY]);
    }

    public function destroy(Destroy $request, $inspection)
    {
        $inspection = Inspection::findOrFail($inspection);

        if ($inspection->delete()) {
            return $this->response->array([STATUS => 200, MESSAGE => 'Inspection successfully deleted.']);
        } else {
             return $this->response->errorInternal('Error occurred while deleting inspection.');
        }
    }

    /**
     * update Technician Inspection
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function updateTechnicianInspection(UpdateMultipleInspection $request)
    {

        $this->inspectionRepository->updateTechnicianInspectionDetails($request);
        return $this->response->array([STATUS => 200, MESSAGE => 'Inspection Information updated.']);

    }

    /**
     * Get Inspection List In Places
     *
     * @param  mixed $request
     * @param  mixed $place_id
     *
     * @return void
     */
    public function getPlaceInspection(Request $request, $place_id)
    {
        #Set Sort by & Sort by Column
        $sortBy = Helper::setSortByValue($request);

        #Set Per Page Record
        $per_page = Helper::setPerPage($request);
        $inspection = Inspection::where('place_id',$place_id)->where('company_id',Auth::user()->company_id);

        $inspection->join('inspection_types', 'inspections.inspection_type_id', '=', 'inspection_types.id')
                ->join('inspection_statuses', 'inspections.inspection_status_id', '=', 'inspection_statuses.id')
                ->select('inspections.*', DB::raw('(inspection_types.inspection_type) inspectiontype,(inspection_statuses.inspection_status) inspectionstatus'));

        return $this->response->paginator($inspection->orderBy($sortBy['column'], $sortBy['order'])->paginate($per_page), new InspectionTransformer());
    }

    /**
     * Delete Inspection In Place
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function deletePlaceInspection(DeletePlaceInspection $request)
    {  
        if(Inspection::whereIn('id',$request->id)->update(['deleted_at' => date('Y-m-d H:i:s')])){
            return $this->response->array([STATUS => 200, MESSAGE => 'Inspection has been removed successfully.']);
        }
        return $this->response->errorInternal(DELETE_ERROR_MSG);
    }

    /**
     * Store Inspection Area
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function storeInspectionArea(StoreInspectionArea $request)
    {
        $model=new InspectionArea;
        $model->fill($request->all());
        if ($model->save()) {
            return $this->response->array([STATUS => 200, MESSAGE => ADD_SUCCESS_MSG]);
        }
        return $this->response->errorInternal('Error occurred while saving inspection area.');
    }

    /**
     * Update Inspection Area
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function updateInspectionArea(UpdateInspectionArea $request)
    {
        $requested_data = $request->all();
        InspectionArea::where('id',$requested_data['id'])->update($requested_data);
        return $this->response->array([STATUS => 200, MESSAGE => UPDATED_SUCCESSFULLY]);
    }

    /**
     * Delete Inspection Area
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function deleteInspectionArea(DeleteInspectionArea $request)
    {  
        if(!InspectionDevice::where('area_id',$request->id)->first()){
            if(InspectionArea::where('id',$request->id)->update(['deleted_at' => date('Y-m-d H:i:s')])){
                #Query to delete device related to the area
                InspectionDevice::where('area_id',$request->id)->update(['deleted_at' => date('Y-m-d H:i:s')]);
                return $this->response->array([STATUS => 200, MESSAGE => 'Successfully deleted.']);
            }
            return $this->response->errorInternal(DELETE_ERROR_MSG);
        }
        return response()->json(['error' => PART_NOT_DELETE_ERROR_MSG], 466);
    }

    /**
     * Get Inspection Area Listing
     *
     * @param  mixed $request
     * @param  mixed $inspection_id
     *
     * @return void
     */
    public function getInspectionArea(Request $request, $inspection_id)
    {
        #Set Sort by & Sort by Column
        $sortBy = Helper::setSortByValue($request);

        #Set Per Page Record
        $per_page = Helper::setPerPage($request);
        $area = InspectionArea::where('inspection_id',$inspection_id)->where('company_id',Auth::user()->company_id);
        return $this->response->paginator($area->orderBy($sortBy['column'], $sortBy['order'])->paginate($per_page), new InspectionAreaTransformer());
    }

    /**
     * Get Inspection Area Detail
     *
     * @param  mixed $request
     * @param  mixed $inspectionarea_id
     *
     * @return void
     */
    public function getInspectionAreaDetail(Request $request, $inspectionarea_id)
    {
        $inspectionarea=InspectionArea::where('id',$inspectionarea_id)->first();
        if($inspectionarea){
            return $this->response->item($inspectionarea, new InspectionAreaTransformer());
        }
        return $this->response->errorInternal(NO_RECORD_MSG);
    }

    /**
     * Store Inspection Device
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function storeInspectionDevice(StoreInspectionDevice $request)
    {
        $device_count=$request->no_of_devices;
        $requested_data = $request->all();
        
        unset($requested_data['no_of_devices']);
        for($i=0;$i<$device_count;$i++)
        {
            #Set Tested Date If Tested Is Checked
            $tested_on = $request->tested == true ? date('Y-m-d H:i:s') : '';
            if(!empty($tested_on))
            {
                $requested_data['tested_on'] =$tested_on;
            }
            $model=new InspectionDevice;
            $model->fill($requested_data);
            if ($model->save()) {

                //Add tested details
                $this->inspectionRepository->addTestedDetail($model->id,$request->tested);

                //Add no access details
                $this->inspectionRepository->addNoaccessDetail($model->id,$request->no_access);

                //Add repaired details
                $this->inspectionRepository->addRepairedDetail($model->id,$request->repaired);

                //Add defective details
                $this->inspectionRepository->addDefectiveDetail($model->id,$request->defective);

                $default_form = $this->saveDeviceFormDetail($request,$requested_data['device_type_id']);
                if(!empty($default_form)){
                    $details =  serialize($default_form['data']); 
                    InspectionDevice::where('id',$model->id)->update(['details'=> ''.$details.'', 'form_id' => $default_form['form_id']]);
                }
            }
        }
        return $this->response->array([STATUS => 200, MESSAGE => ADD_SUCCESS_MSG]);
        
    }

    /**
     * Save Device Form Detail
     *
     * @param  mixed $request
     * @param  mixed $device_type_id
     *
     * @return void
     */
    public function saveDeviceFormDetail($request,$device_type_id){
        $result = array();
        $device_type = DeviceType::where('id',$device_type_id)->first();
        if($device_type && isset($device_type->form_template_id) && $device_type->form_template_id !== NULL){
            $result = $this->getFormFields($request,$device_type->form_template_id);
            $data = json_decode($result->getContent(), TRUE);
            return array('data' => $data['data'], 'form_id' => $device_type->form_template_id) ;
        }
        return $result;
    }

    /**
     * Store Inspection Device Form
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function storeInspectionDeviceForm(StoreInspectionDeviceForm $request)
    {
        $details =  serialize($request->details); 
        if(InspectionDevice::where('id',$request->inspection_devices_id)->update(['details'=> ''.$details.'','form_id' => $request->form_id])){
            return $this->response->array([STATUS => 200, MESSAGE => ADD_SUCCESS_MSG]);
        }
        return $this->response->array([STATUS => 466, MESSAGE => 'Something went wrong while save device form.']);
    }

    /**
     * Update Inspection Device
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function updateInspectionDevice(UpdateInspectionDevice $request)
    {
        $requested_data = $request->all();
        #Set Tested Date If Tested Is Checked
        $tested_on = $request->tested == true ? date('Y-m-d H:i:s') : '';
        if(!empty($tested_on))
        {
            $requested_data['tested_on'] =$tested_on;
        }
        
        $checkInspection = InspectionDevice::where('id',$requested_data['id'])->first();
        //Add tested detail if updated
        if($checkInspection->tested != $request->tested)
        {
            $this->inspectionRepository->addTestedDetail($requested_data['id'],$request->tested);
        }
        
        //Add No access detail if updated
        if($checkInspection->no_access != $request->no_access)
        {
            $this->inspectionRepository->addNoaccessDetail($requested_data['id'],$request->no_access);
        }

        //Add Repaired detail if updated
        if($checkInspection->repaired != $request->repaired)
        {
            $this->inspectionRepository->addRepairedDetail($requested_data['id'],$request->repaired);
        }

        //Add Repaired detail if updated
        if($checkInspection->defective != $request->defective)
        {
            $this->inspectionRepository->addDefectiveDetail($requested_data['id'],$request->defective);
        }
        
        $default_form = $this->saveDeviceFormDetail($request,$requested_data['device_type_id']);
        if(!empty($default_form)){
            $details =  serialize($default_form['data']); 
            $requested_data['details'] = $details;
            $requested_data['form_id'] = $default_form['form_id'];
        }else{
            $requested_data['details'] = NULL;
            $requested_data['form_id'] = NULL;
        }
        InspectionDevice::where('id',$requested_data['id'])->update($requested_data);
        return $this->response->array([STATUS => 200, MESSAGE => 'Device details has been updated successfully.']);
    }

    

    /**
     * Delete Inspection Device
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function deleteInspectionDevice(DeleteInspectionDevice $request)
    {  
        if(InspectionDevice::where('id',$request->id)->update(['deleted_at' => date('Y-m-d H:i:s')])){
            return $this->response->array([STATUS => 200, MESSAGE => 'Device details has been removed successfully.']);
        }
        return $this->response->errorInternal(DELETE_ERROR_MSG);
    }

    /**
     * Get Inspection Device Listing
     *
     * @param  mixed $request
     * @param  mixed $inspection_id
     *
     * @return void
     */
    public function getInspectionDevice(Request $request, $inspection_id)
    {
        #Set Sort by & Sort by Column
        $sortBy = Helper::setSortByValue($request);

        #Set Per Page Record
        $per_page = Helper::setPerPage($request);
        $device = InspectionDevice::where('inspection_devices.inspection_id',$inspection_id)->where('inspection_devices.company_id',Auth::user()->company_id);
        
        #Search Filter For Area
        if($request->has('area') && !empty($request->area)){
            $device->where('area_id',$request->area);
        }

        #Search Filter For Location
        if($request->has('location') && !empty($request->location)){
            $device->where('location','like','%'.$request->location.'%');
        }

        #Search Filter For Device Number
        if($request->has('device_number') && !empty($request->device_number)){
            $device->where('device_number','like','%'.$request->device_number.'%');
        }

        #Search Filter For Identifier
        if($request->has('identifier') && !empty($request->identifier)){
            $device->where('identifier','like','%'.$request->identifier.'%');
        }

        #Search Filter For Device
        if($request->has('device') && !empty($request->device)){
            $device->where('device_type_id',$request->device);
        }
        #Search Filter For Tested
        if($request->has('tested') && !empty($request->tested)){   
            $tested =  $request->tested == 'true' ? 1 :0;  
            $device->where('tested',$tested);
        }
        $device->join('inspection_areas', 'inspection_devices.area_id', '=', 'inspection_areas.id')
                ->join('device_types', 'inspection_devices.device_type_id', '=', 'device_types.id')
                ->leftJoin('deficiency_types', 'inspection_devices.deficiency_type_id', '=', 'deficiency_types.id')
                ->select('inspection_devices.*', DB::raw('(inspection_areas.area) areaname,(device_types.device_type) devicetypename,(deficiency_types.deficiency_type) deficiencyname'));
        

        return $this->response->paginator($device->orderBy($sortBy['column'], $sortBy['order'])->paginate($per_page), new InspectionDeviceTransformer());
    }

    /**
     * Get Inspection Detail
     *
     * @param  mixed $request
     * @param  mixed $inspectiondevice_id
     *
     * @return void
     */
    public function getInspectionDeviceDetail(Request $request, $inspectiondevice_id)
    {
        $inspectiondevice=InspectionDevice::where('id',$inspectiondevice_id)->first();
        if($inspectiondevice){
            return $this->response->item($inspectiondevice, new InspectionDeviceTransformer());
        }
        return $this->response->errorInternal(NO_RECORD_MSG);
    }

    /**
     * Update Inspection Device Checks
     *
     * @param  mixed $request
     * @param  mixed $inspectiondevice_id
     *
     * @return void
     */
    public function updateInspectionDeviceCheck(UpdateInspectionDeviceCheck $request, $inspectiondevice_id)
    {
        $requested_data = $request->all();
        $requested_data['tested_on'] = date('Y-m-d H:i:s');
        InspectionDevice::where('id',$requested_data['id'])->update($requested_data);
        return $this->response->array([STATUS => 200, MESSAGE => UPDATED_SUCCESSFULLY]);
    }

    /**
     * Update Inspection Device Deficiency
     *
     * @param  mixed $request
     * @param  mixed $inspectiondevice_id
     *
     * @return void
     */
    public function updateInspectionDeviceDeficiency(UpdateInspectionDeviceDeficiency $request, $inspectiondevice_id)
    {
        $requested_data = $request->all();
        InspectionDevice::where('id',$requested_data['id'])->update($requested_data);
        return $this->response->array([STATUS => 200, MESSAGE => UPDATED_SUCCESSFULLY]);
    }

    /**
     * get Inspection Device Deficiency Detail
     *
     * @param  mixed $request
     * @param  mixed $inspectiondevice_id
     *
     * @return void
     */
    public function getInspectionDeviceDeficiencyDetail(Request $request, $inspectiondevice_id)
    {
        $inspectiondevice=InspectionDevice::select('id','deficiency_type_id','deficiency_detail')->where('id',$inspectiondevice_id)->first();
        if($inspectiondevice){
            return $this->response->array([STATUS => 200, 'data' => $inspectiondevice]);
        }
        return $this->response->errorInternal(NO_RECORD_MSG);
    }

    /**
     * Get Form Fields
     *
     * @param  mixed $request
     * @param  mixed $form_id
     *
     * @return void
     */
    public function getFormFields(Request $request, $form_id){
        if($request->has('inspection_device_id') && !empty($request->inspection_device_id)){
          
            $inspection_device = InspectionDevice::where('id',$request->inspection_device_id)->first();
            if($inspection_device && isset($inspection_device->details) && !empty($inspection_device->details)){
                $details = $inspection_device->details;
                return $this->response->array([STATUS => 200, 'data' => $details]);
            }
        }
        
        $form_template = FormTemplate::where('id',$form_id)->first();
        if($form_template){
            $formData = FormTemplateField::where('form_id',$form_template->id)->get()->groupBy('tab_name')->toArray();
           
            $formDataResult = array();
            $j = 0;
            $remove_field = ['manufacturer','model'];
            foreach($formData as $key=>$formDataValue){
                $formDataResult[$j]['tab_name'] = $key;
                $k=0;
                $formDataArray = array();
                foreach($formDataValue as $subValue){
                    if(!in_array($subValue['field_name'],$remove_field) &&  $subValue['field_type'] == 'dropdown'){
                        $subValue['option'] = [["id"=> "Y","name"=> "Yes"],["id"=> "N","name"=>"No"],["id"=> "X","name"=>"Not Applicable"]];
                    }
                    
                    $formDataArray[$k] = $subValue;
                    $manufacturer =  array();
                    if($subValue['field_name'] == 'manufacturer'){

                        if($subValue['api_url'] == "Smoke Detector"){
                            $manufacturer_type_id = 1;
                        }else if($subValue['api_url'] == 'Lighting'){
                            $manufacturer_type_id = 2;
                        }else if($subValue['api_url'] == 'Fire Alarm Panel'){
                            $manufacturer_type_id = 3;
                        }else if($subValue['api_url'] == 'Kitchen System'){
                            $manufacturer_type_id = 4;
                        }else if($subValue['api_url'] == 'Ancillary Device Circuit Test'){
                            $manufacturer_type_id = 5;
                        }else{
                            $manufacturer_type_id = '';
                        }
                        
                        $manufacturer_model = ManufacturersModel::select(DB::raw('(id) id,(manufacturer) name'));
                        if($manufacturer_type_id != ''){
                            $manufacturer_model->where('device_type',$manufacturer_type_id );
                        }
                        $manufacturer_model = $manufacturer_model->get()->unique('name')->toArray();
                        if(!empty($manufacturer_model)){
                            $i = 0;
                            foreach($manufacturer_model as $value){
                                $manufacturer[$i] = $value;
                                $model = ManufacturersModel::select(DB::raw('(id) id,(model) name'));
                                if($manufacturer_type_id != ''){
                                    $model->where('device_type',$manufacturer_type_id );
                                }
                                $model = $model->where('manufacturer','like','%'.$value['name'].'%')->get()->toArray();
                                if($model){
                                    $manufacturer[$i]['model'] = $model;
                                }
                                $i++;
                            }
                        }
                        $formDataArray[$k]['option'] = $manufacturer;
                    }
                    $k++;
                }
                $formDataResult[$j]['data'] = $formDataArray;
                $j++;
            }
           
            return $this->response->array([STATUS => 200, 'data' => $formDataResult]);
        }
        return array(STATUS => 466, MESSAGE => "Form id is invalid.");
    }

    /**
     * Get Inspection Status
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function getInspectionStatus(Request $request)
    {
        $status=InspectionStatus::get()->toArray();
        if($status){
            return $this->response->array(['data' => $status]);
        }
        return $this->response->errorInternal('Inspection status not found. Please try again.');
    }

    /**
     * get Inspection Print
     *
     * @param  mixed $request
     * @param  mixed $inspection_id
     *
     * @return void
     */
    public function getInspectionPrint(Request $request,$inspection_id)
    {
        
        $printtype=$request->printtype;
        if(!empty($printtype))
        {
            
            switch($printtype)
            {
                case "main":
                $print = $this->inspectionRepository->getInspectionPrintMain($request, $inspection_id);
                break;

                case "devices":
                $print = $this->inspectionRepository->getInspectionPrintDevice($request, $inspection_id);
                break;

                case "deficiencies":
                $print = $this->inspectionRepository->getInspectionPrintDeficiency($request, $inspection_id);
                break;

                case "noaccess":
                $print = $this->inspectionRepository->getInspectionPrintNoaccess($request, $inspection_id);
                break;

                case "alldevicetypes":
                $print = $this->inspectionRepository->getInspectionPrintAlldeviceTypes($request, $inspection_id);
                break;

                case "management":
                $print[] = $this->inspectionRepository->getInspectionPrintMain($request, $inspection_id);
                $print[] = $this->inspectionRepository->getInspectionPrintDeficiency($request, $inspection_id);
                $print[] = $this->inspectionRepository->getInspectionPrintNoaccess($request, $inspection_id);
                break;

                case "allreports":
                $print[] = $this->inspectionRepository->getInspectionPrintMain($request, $inspection_id);
                $print[] = $this->inspectionRepository->getInspectionPrintDeficiency($request, $inspection_id);
                $print[] = $this->inspectionRepository->getInspectionPrintNoaccess($request, $inspection_id);
                $print[] = $this->inspectionRepository->getInspectionPrintDevice($request, $inspection_id);
                $print[] = $this->inspectionRepository->getInspectionPrintAlldeviceTypes($request, $inspection_id);
                break;

                default:
                return $this->response->errorInternal('Please send valid print type.');
                break;

            }
            return $this->response->array([STATUS => 200, 'data' => $print]);
        }
        return $this->response->errorInternal('Please send print type.');
    }

    /**
     * store Inspection Device Notes
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function storeInspectionDeviceNotes(Request $request)
    {
        $requested_data = $request->all();
        $requested_data['created_by'] = Auth::user()->id;
        $model=new InspectionDeviceNote;
        $model->fill($requested_data);
        if ($model->save()) {
            return $this->response->item($model, new InspectionDeviceNoteTransformer());
        } else {
              return $this->response->errorInternal('Error occurred while saving inspection device note.');
        }
    }
    
    /**
     * Delete Inspection Device Files
     *
     * @param  mixed $request
     * @return void
     */
    public function deleteInspectionDeviceFiles(Request $request)
    {
        if(InspectionDeviceDeficiencyFile::whereIn('id',$request->id)->update([DELETED_AT => date('Y-m-d H:i:s')])){
            return $this->response->array([STATUS => 200, MESSAGE => DELETED_SUCCESSFULLY]);
        }
        return $this->response->errorInternal(DELETE_ERROR_MSG);
    }

}

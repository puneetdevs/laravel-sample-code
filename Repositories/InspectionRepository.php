<?php

namespace App\Repositories;


use DB;
use App\Exceptions\Handler;
use App\Repositories\BaseRepository;
use App\Models\Inspection;
use App\Models\InspectionDevice;
use App\Models\PlacesInspectionArea;
use App\Models\InspectionArea;
use App\Models\PlacesInspectionDevice;
use App\Models\PlacesInspection;
use App\Models\InspectionStatus; 
use App\Models\FormTemplate; 
use App\Models\InspectionDeviceTestedDetail;
use App\Models\InspectionDeviceNoaccessDetail;
use App\Models\InspectionDeviceRepairedDetail;
use App\Models\InspectionDeviceDeficiencyFile;
use App\Models\InspectionDeviceDefectiveDetail;
use DateTime;
use Auth;
use Carbon\Carbon;
/**
 * Class InspectionRepository.
 */
class InspectionRepository extends BaseRepository
{
    /**
     * @return string
     */
    public function model()
    {
        return Inspection::class;
    }

    
    /**
     * Inspection Print Main
     *
     * @param  mixed $request
     * @param  mixed $inspection_id
     *
     * @return void
     */
    public function getInspectionPrintMain($request, $inspection_id)
    {
        $devicecount = $this->getInspectionDeviceCount($inspection_id);
        
        if($request->printtype == 'allreports' || $request->printtype == 'management'){
            $result = array('devices'=>$devicecount);
        }else{
            $result = array('device'=>$devicecount);
        }
        return $result;
    }
    
    /**
     * get Inspection Device Counts
     *
     * @param  mixed $inspection_id
     *
     * @return void
     */
    private function getInspectionDeviceCount($inspection_id)
    {
        $device=InspectionDevice::where('inspection_id',$inspection_id)
                ->join(DEVICE_TYPES, 'inspection_devices.device_type_id', '=', 'device_types.id')
                ->select('inspection_devices.*', DB::raw('(device_types.device_type) typename'))
                ->get()->toArray();

        if(!empty($device))
        {
            $deviceinfo=array();
            $i=0;
            foreach($device as $value)
            {
             
                if(in_array($value['device_type_id'], array_column($deviceinfo,'type')))
                {
                    $array_key = array_search($value['device_type_id'],array_column($deviceinfo,'type'));
                    if($value['tested']==1)
                    {
                        $deviceinfo[$array_key]['tested']= $deviceinfo[$array_key]['tested']+1;
                    }
                    if($value['no_access']==1)
                    {    
                        $deviceinfo[$array_key]['noaccess']=$deviceinfo[$array_key]['noaccess']+1;
                    } 
                    if( !empty($value['deficiency_type_id']) )
                    {
                        $deviceinfo[$array_key]['deficiency']=$deviceinfo[$array_key]['deficiency']+1;
                    }
                    if($value['repaired']==1)
                    {
                        $deviceinfo[$array_key]['repair']=$deviceinfo[$array_key]['repair']+1;
                    }
                }
                else
                {
                    $deviceinfo[$i]['type']=$value['device_type_id'];
                    $deviceinfo[$i]['typename']=$value['typename'];
                    $deviceinfo[$i]['tested']=$value['tested'];
                    $deviceinfo[$i]['noaccess']=$value['no_access'];
                    $deficiency=0;
                    if(!empty($value['deficiency_type_id']))
                    {
                        $deficiency=1;
                    }
                    $deviceinfo[$i]['deficiency']=$deficiency;
                    $deviceinfo[$i]['repair']=$value['repaired'];
                    $i++;
                }
            }
            return array('devicedetail' => $deviceinfo);
        }
        return array('status' => 466, 'message' => "No data found");
    }

    /**
     * Get Inspection Print Device
     *
     * @param  mixed $request
     * @param  mixed $inspection_id
     *
     * @return void
     */
    public function getInspectionPrintDevice($request, $inspection_id)
    {
        $deviceinfo=InspectionDevice::where('inspection_devices.inspection_id',$inspection_id)
                ->join(DEVICE_TYPES, 'inspection_devices.device_type_id', '=', 'device_types.id')
                ->join('inspection_areas', 'inspection_devices.area_id', '=', 'inspection_areas.id')
                ->select('inspection_devices.*', DB::raw('(device_types.device_type) devicetypename,(inspection_areas.area) areaname'))
                ->get()->toArray();
        return array('device'=>$deviceinfo);
    }

    /**
     * get Inspection Print Deficiency
     *
     * @param  mixed $request
     * @param  mixed $inspection_id
     *
     * @return void
     */
    public function getInspectionPrintDeficiency($request, $inspection_id)
    {
        $deficiencyinfo=InspectionDevice::where('inspection_devices.inspection_id',$inspection_id)
                ->join(DEVICE_TYPES, 'inspection_devices.device_type_id', '=', 'device_types.id')
                ->join('inspection_areas', 'inspection_devices.area_id', '=', 'inspection_areas.id')
                ->join('deficiency_types', 'inspection_devices.deficiency_type_id', '=', 'deficiency_types.id')
                ->select('inspection_devices.*', DB::raw('(device_types.device_type) devicetypename,(inspection_areas.area) areaname,(deficiency_types.deficiency_type) deficiencyname'))
                ->get()->toArray();
        return array('deficiency'=>$deficiencyinfo);
    }

    /**
     * get Inspection Print Noaccess
     *
     * @param  mixed $request
     * @param  mixed $inspection_id
     *
     * @return void
     */
    public function getInspectionPrintNoaccess($request, $inspection_id)
    {
        $noaccessinfo=InspectionDevice::where('inspection_devices.inspection_id',$inspection_id)
                ->where('no_access',1)
                ->join(DEVICE_TYPES, 'inspection_devices.device_type_id', '=', 'device_types.id')
                ->join('inspection_areas', 'inspection_devices.area_id', '=', 'inspection_areas.id')
                ->select('inspection_devices.location', DB::raw('(device_types.device_type) devicetypename,(inspection_areas.area) areaname'))
                ->get()->toArray();
        return array('noaccess'=>$noaccessinfo);
    }

    /**
     * get Inspection Print All device Types
     *
     * @param  mixed $request
     * @param  mixed $inspection_id
     *
     * @return void
     */
    public function getInspectionPrintAlldeviceTypes($request, $inspection_id)
    {
        if($request->has('form_id') && !empty($request->form_id)){
            $devicedetail=InspectionDevice::where('inspection_devices.inspection_id',$inspection_id)
                                            ->where('inspection_devices.form_id',$request->form_id)
                                            ->join(DEVICE_TYPES, 'inspection_devices.device_type_id', '=', 'device_types.id')
                                            ->LeftJoin('inspection_areas', 'inspection_devices.area_id', '=', 'inspection_areas.id')
                                            ->LeftJoin('deficiency_types', 'inspection_devices.deficiency_type_id', '=', 'deficiency_types.id')
                                            ->select('inspection_devices.*', DB::raw('(device_types.device_type) devicetypename,(inspection_areas.area) areaname,(deficiency_types.deficiency_type) deficiencyname'))
                                            ->get()->toArray();
        }else{
            $devicedetail = FormTemplate::with(['inspection_devices' => function($q)use($inspection_id){
                    $q->where('inspection_id',$inspection_id);
                },'inspection_devices.devicetype','inspection_devices.inspectionarea','inspection_devices.deficiencytype',
            ])->get()->toArray();
        }
        
        return array('form_template'=>$devicedetail);
    }

    /**
     * insert Data From Place Device in inspection
     *
     * @param  mixed $inspection_id
     * @param  mixed $placeinspection_id
     *
     * @return void
     */
    public function insertDataFromPlaceDevice($inspection_id,$placeinspection_id)
    {
        $this->insertInspectionAreaFromDevices($inspection_id,$placeinspection_id);
        $this->insertInspectionDevicesFromDevices($inspection_id,$placeinspection_id);

        //Update technician notes in inspection from place-device technician notes
        $inspection=PlacesInspection::where('id',$placeinspection_id)->first();
        if(!empty($inspection->technician_notes))
        {
            Inspection::where('id',$inspection_id)->update(['technician_notes' => $inspection->technician_notes]);
        }
    }

    /**
     * insert Inspection Area From Devices in inspection
     *
     * @param  mixed $inspection_id
     * @param  mixed $placeinspection_id
     *
     * @return void
     */
    public function insertInspectionAreaFromDevices($inspection_id,$placeinspection_id)
    {
        $placedevicearea = PlacesInspectionArea::where('places_inspection_id',$placeinspection_id)->where('company_id',Auth::user()->company_id)->get()->toArray();
        if(!empty($placedevicearea))
        {
            foreach($placedevicearea as $keys => $values)
            {
                $datas[$keys]['company_id'] = Auth::user()->company_id;
                $datas[$keys]['inspection_id'] = $inspection_id;
                $datas[$keys]['area'] = $values['area'];
                $datas[$keys]['created_at'] = date('Y-m-d H:i:s');
                $datas[$keys]['updated_at'] = date('Y-m-d H:i:s');
            }
            InspectionArea::insert($datas);
        }
    }

    /**
     * insert Inspection Devices From Devices in inspection
     *
     * @param  mixed $inspection_id
     * @param  mixed $placeinspection_id
     *
     * @return void
     */
    public function insertInspectionDevicesFromDevices($inspection_id,$placeinspection_id)
    {
        $device = PlacesInspectionDevice::where('places_inspection_devices.places_inspection_id',$placeinspection_id)->where('places_inspection_devices.company_id',Auth::user()->company_id)
                ->join('places_inspection_areas', 'places_inspection_devices.area_id', '=', 'places_inspection_areas.id')
                ->select('places_inspection_devices.*', DB::raw('(places_inspection_areas.area) areaname'))
                ->get()->toArray();
        if(!empty($device))
        {
            foreach($device as $keys => $values)
            {
                    $datas[$keys]['company_id'] = Auth::user()->company_id;
                    $datas[$keys]['inspection_id'] = $inspection_id;
                    //Fetch area id from inspection area
                    $area_id = InspectionArea::where('area',$values['areaname'])->where('inspection_id',$inspection_id)->where('company_id',Auth::user()->company_id)->first();
                    
                    $datas[$keys]['area_id'] = $area_id->id;
                    $datas[$keys]['location'] = $values['location'];
                    $datas[$keys]['identifier'] = $values['identifier'];
                    $datas[$keys]['device_type_id'] = $values['device_type_id'];
                    $datas[$keys]['note'] = $values['note'];
                    $datas[$keys]['details'] = $values['details'];
                    $datas[$keys]['form_id'] = $values['form_id'];
                    $datas[$keys]['created_at'] = date('Y-m-d H:i:s');
                    $datas[$keys]['updated_at'] = date('Y-m-d H:i:s');
            }
            InspectionDevice::insert($datas);
        }
    }

    /**
     * update Inspection
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function updateInspection($request)
    {
        $status_id=InspectionStatus::where('inspection_status','Complete')->first();
        $requested_data = $request->all();
        $requested_data['completed_date'] = isset ($request->inspection_status_id) && $request->inspection_status_id == $status_id->id  ? date('Y-m-d H:i:s') : NULL;
        
        Inspection::where('id',$requested_data['id'])->update($requested_data);
        return true;
    }

    /**
     * update Technician Inspection Details
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function updateTechnicianInspectionDetails($request)
    {
        foreach($request->inspection as $inspection)
        {
            $this->addTechnicianInspectionArea($inspection,$inspection['inspection_id']);
            $this->addTechnicianInspectionDevices($inspection['inspection_device'],$inspection['inspection_id']);
            $this->deleteTechnicianInspectionArea($inspection['inspection_delete_area']);
            $this->deleteTechnicianInspectionDevices($inspection['inspection_delete_device']);

            if(array_key_exists('delete_device_files', $inspection)){
                $this->deleteTechnicianDeviceFiles($inspection['delete_device_files']);
            }

            $requested_data = $inspection;
            unset($requested_data['inspection_id'],$requested_data['inspection_area'],$requested_data['inspection_device'],
            $requested_data['inspection_delete_area'],$requested_data['inspection_delete_device'],$requested_data['delete_device_files']);
            $requested_data['id'] = $inspection['inspection_id'];
            Inspection::where('id',$inspection['inspection_id'])->update($requested_data);
        }
        
    }

    /**
     * add Technician Inspection Area
     *
     * @param  mixed $inspectionarea
     * @param  mixed $inspection_id
     *
     * @return void
     */
    public function addTechnicianInspectionArea($inspectionarea,$inspection_id)
    {
        foreach($inspectionarea['inspection_area'] as $areavalue){

            if( !empty($areavalue['id']) && is_numeric($areavalue['id']) ){
                $data['area'] = $areavalue['area'];
                
                InspectionArea::where('id',$areavalue['id'])->update($data);
            }
            else{
                $model=new InspectionArea;
                $data['company_id'] = Auth::user()->company_id;
                $data['area'] = $areavalue['area'];
                $data['inspection_id'] = $inspection_id;
                $data['temporary_uid'] = $areavalue['id'];
                $model->fill($data);
                $model->save();
            }
        }
    }

    /**
     * add Technician Inspection Devices
     *
     * @param  mixed $inspectiondevice
     * @param  mixed $inspection_id
     *
     * @return void
     */
    public function addTechnicianInspectionDevices($inspectiondevice,$inspection_id)
    {
        foreach($inspectiondevice as $devicevalue){

            //Set area id here
            if(is_numeric($devicevalue['area_id']))
            {
                $area_id=$devicevalue['area_id'];
            }
            else
            {
                $area_id = InspectionArea::where('temporary_uid',$devicevalue['area_id'])
                                            ->where('company_id',Auth::user()->company_id)
                                            ->pluck('id')->first();
            }
            
            //Update device detail here
            if( !empty($devicevalue['id']) && is_numeric($devicevalue['id']) )
            {
                $data['area_id'] = $area_id;
                $data['device_number'] = $devicevalue['device_number'];
                $data['location'] = $devicevalue['location'];
                $data['identifier'] = $devicevalue['identifier'];
                $data['device_type_id'] = $devicevalue['device_type_id'];
                $data['tested'] = $devicevalue['tested'];
                $tested_on = $devicevalue['tested'] == 1 ? date('Y-m-d H:i:s') : '';
                
                if(!empty($tested_on))
                {
                    $data['tested_on'] =$tested_on;
                }

                $data['no_access'] = $devicevalue['no_access'];
                $data['repaired'] = $devicevalue['repaired'];
                $data['defective'] = $devicevalue['defective'];
                $data['note'] = $devicevalue['note'];
                
                //for deficiency type detail
                if( (array_key_exists('deficiency_type_id', $devicevalue)) && !empty($devicevalue['deficiency_type_id'])){
                    $data['deficiency_type_id'] = $devicevalue['deficiency_type_id'];
                    $data['deficiency_detail'] = $devicevalue['deficiency_detail'];
                }

                //for device form details
                if( (array_key_exists('details', $devicevalue)) && !empty($devicevalue['details'])){
                    $details =  serialize($devicevalue['details']);
                    $data['details'] = $details;
                    $data['form_id'] = $devicevalue['form_id'];
                }

                $checkInspection = InspectionDevice::where('id',$devicevalue['id'])->first();
                //Add tested detail if updated
                if($checkInspection->tested != $devicevalue['tested'])
                {
                    $this->addTestedDetail($devicevalue['id'],$devicevalue['tested']);
                }
                
                //Add No access detail if updated
                if($checkInspection->no_access != $devicevalue['no_access'])
                {
                    $this->addNoaccessDetail($devicevalue['id'],$devicevalue['no_access']);
                }

                //Add Repaired detail if updated
                if($checkInspection->repaired != $devicevalue['repaired'])
                {
                    $this->addRepairedDetail($devicevalue['id'],$devicevalue['repaired']);
                }

                //Add Defective detail if updated
                if($checkInspection->defective != $devicevalue['defective'])
                {
                    $this->addDefectiveDetail($devicevalue['id'],$devicevalue['defective']);
                }
                
                InspectionDevice::where('id',$devicevalue['id'])->update($data);

            }
            else{
                $model=new InspectionDevice;
                $data = $devicevalue;  

                //Set area id here
                unset($data['area_id'],$data['id']);
                $data['temporary_uid'] = $devicevalue['id'];
                $data['area_id']=$area_id;
                
                $tested_on = $devicevalue['tested'] == 1 ? date('Y-m-d H:i:s') : '';
                if(!empty($tested_on))
                {
                    $data['tested_on'] =$tested_on;
                }

                //for device form details
                if( (array_key_exists('details', $devicevalue)) && !empty($devicevalue['details'])){
                    $details =  serialize($devicevalue['details']);
                    $data['details'] = $details;
                    $data['form_id'] = $devicevalue['form_id'];
                }
                
                $data['company_id'] = Auth::user()->company_id;
                $data['inspection_id'] = $inspection_id;
                $model->fill($data);
                $model->save();

                //Add tested details
                $this->addTestedDetail($model->id,$devicevalue['tested']);

                //Add no access details
                $this->addNoaccessDetail($model->id,$devicevalue['no_access']);

                //Add repaired details
                $this->addRepairedDetail($model->id,$devicevalue['repaired']);

                //Add defective details
                $this->addDefectiveDetail($model->id,$devicevalue['defective']);
            }
        }
    }

    /**
     * delete Technician Inspection Area
     *
     * @return void
     */
    public function deleteTechnicianInspectionArea($inspectionareas)
    {
        if(InspectionArea::whereIn('id',$inspectionareas)->update([DELETED_AT => date('Y-m-d H:i:s')])){
            //Query to delete device related to the area
            InspectionDevice::whereIn('area_id',$inspectionareas)->update([DELETED_AT => date('Y-m-d H:i:s')]);
        }
    }

    /**
     * delete Technician Inspection Devices
     *
     * @param  mixed $inspectiondevices
     *
     * @return void
     */
    public function deleteTechnicianInspectionDevices($inspectiondevices)
    {
        InspectionDevice::whereIn('id',$inspectiondevices)->update([DELETED_AT => date('Y-m-d H:i:s')]);
    }

    /**
     * add Tested Detail
     *
     * @param  mixed $inspection_device_id
     * @param  mixed $tested
     *
     * @return void
     */
    public function addTestedDetail($inspection_device_id,$tested)
    {
        $requested_data['inspection_device_id'] = $inspection_device_id;
        $requested_data['tested'] = $tested;
        $requested_data['created_by'] = Auth::user()->id;
        $model=new InspectionDeviceTestedDetail;
        $model->fill($requested_data);
        $model->save();
    }

    /**
     * add Noaccess Detail
     *
     * @param  mixed $inspection_device_id
     * @param  mixed $noaccess
     *
     * @return void
     */
    public function addNoaccessDetail($inspection_device_id,$noaccess)
    {
        $requested_data['inspection_device_id'] = $inspection_device_id;
        $requested_data['no_access'] = $noaccess;
        $requested_data['created_by'] = Auth::user()->id;
        $model=new InspectionDeviceNoaccessDetail;
        $model->fill($requested_data);
        $model->save();
    }

    /**
     * add Repaired Detail
     *
     * @param  mixed $inspection_device_id
     * @param  mixed $repaired
     *
     * @return void
     */
    public function addRepairedDetail($inspection_device_id,$repaired)
    {
        $requested_data['inspection_device_id'] = $inspection_device_id;
        $requested_data['repaired'] = $repaired;
        $requested_data['created_by'] = Auth::user()->id;
        $model=new InspectionDeviceRepairedDetail;
        $model->fill($requested_data);
        $model->save();
    }
    
    /**
     * add Defective Detail
     *
     * @param  mixed $inspection_device_id
     * @param  mixed $defective
     * @return void
     */
    public function addDefectiveDetail($inspection_device_id,$defective)
    {
        $requested_data['inspection_device_id'] = $inspection_device_id;
        $requested_data['defective'] = $defective;
        $requested_data['created_by'] = Auth::user()->id;
        $model=new InspectionDeviceDefectiveDetail;
        $model->fill($requested_data);
        $model->save();
    }

    /**
     * delete Technician Device Files
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function deleteTechnicianDeviceFiles($request)
    {  
        if(!empty($request))
        {
            InspectionDeviceDeficiencyFile::whereIn('id',$request)->update(['deleted_at' => date('Y-m-d H:i:s')]);
        }
    }

}

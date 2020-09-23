<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\ApiController;
use App\Models\DeviceType;
use App\Models\FormTemplate;
use App\Models\FormTemplateField;
use App\Models\ManufacturersModel;
use App\Transformers\DeviceTypeTransformer;
use App\Transformers\FormTemplateTransformer;
use App\Http\Requests\Api\DeviceTypes\Index;
use App\Http\Requests\Api\DeviceTypes\Show;
use App\Http\Requests\Api\DeviceTypes\Create;
use App\Http\Requests\Api\DeviceTypes\Store;
use App\Http\Requests\Api\DeviceTypes\Edit;
use App\Http\Requests\Api\DeviceTypes\Update;
use App\Http\Requests\Api\DeviceTypes\Destroy;
use Auth;
use DB;
use App\Helpers\Helper;

/**
 * DeviceType
 *
 * @Resource("DeviceType", uri="/device_types")
 */

class DeviceTypeController extends ApiController
{
    
    /**
     * Get Device Type Listing
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function index(Index $request)
    {
        #Set Sort by & Sort by Column
        $sortBy = Helper::setSortByValue($request);

        #Set Per Page Record
        $per_page = Helper::setPerPage($request);

        $device_type = DeviceType::select('*');
        if($request->has('q') && !empty($request->q)){
            $device_type->where('device_type', 'like' , '%'.$request->q.'%');
        }
        return $this->response->paginator($device_type->orderBy($sortBy['column'], $sortBy['order'])->paginate($per_page), new DeviceTypeTransformer());
    }

    /**
     * Get Single Device Type
     *
     * @param  mixed $request
     * @param  mixed $devicetype
     *
     * @return void
     */
    public function show(Show $request, $devicetype)
    {
        $devicetype = DeviceType::where('id',$devicetype)->first();
        if($devicetype){
            return $this->response->item($devicetype, new DeviceTypeTransformer());
        }
        return response()->json([MESSAGE=>'No matching records found.'], 404);
    }

    /**
     * Create Device Type
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function store(Store $request)
    {
        $requested_data = $request->all();
        $requested_data['company_id'] = Auth::user()->company_id;
        $model=new DeviceType;
        $model->fill($requested_data);
        if ($model->save()) {
            return $this->response->item($model, new DeviceTypeTransformer());
        } else {
              return $this->response->errorInternal('Error occurred while saving device type.');
        }
    }
 
    /**
     * Update Device Type
     *
     * @param  mixed $request
     * @param  mixed $devicetype
     *
     * @return void
     */
    public function update(Update $request,  $devicetype)
    {
        $requested_data = $request->all();
        DeviceType::where('id',$requested_data['id'])->update($requested_data);
        $devicetype = DeviceType::where('id',$requested_data['id'])->first();
        return $this->response->item($devicetype, new DeviceTypeTransformer());
    }

    public function destroy(Destroy $request, $devicetype)
    {
        
    }

    /**
     * Get Form Template
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function getFormTemplate(Request $request)
    {
        return $this->response->paginator(FormTemplate::paginate(100), new FormTemplateTransformer());
    }

    /**
     * get Form Template Details
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function getFormTemplateDetails(Request $request)
    {
        //Get all form template
        $all_form_template = FormTemplate::orderBy('id')->get();

        $form_template_details=array();
        $t=0;
        //Get form template fields here
        foreach($all_form_template as $form_template)
        {
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
                            }else if($subValue['api_url'] == 'Alarm Panel'){
                                $manufacturer_type_id = 3;
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
              
            }
            $form_template_details[$t]['form_template_id']=$form_template->id;
            $form_template_details[$t]['form_template']=$form_template->form_name;
            $form_template_details[$t]['form_template_fields']=$formDataResult;
            $t++;
        }
        return $this->response->array(['status' => 200, 'data' => $form_template_details]);
    }

}

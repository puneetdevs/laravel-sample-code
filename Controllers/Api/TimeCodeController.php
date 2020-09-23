<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\ApiController;
use App\Models\TimeCode;
use App\Models\Timezone;
use App\Transformers\TimeCodeTransformer;
use App\Transformers\TimeZoneTransformer;
use App\Http\Requests\Api\TimeCodes\Index;
use App\Http\Requests\Api\TimeCodes\Show;
use App\Http\Requests\Api\TimeCodes\Create;
use App\Http\Requests\Api\TimeCodes\Store;
use App\Http\Requests\Api\TimeCodes\Edit;
use App\Http\Requests\Api\TimeCodes\Update;
use App\Http\Requests\Api\TimeCodes\Destroy;
use Auth;
use App\Helpers\Helper;

/**
 * TimeCode
 *
 * @Resource("TimeCode", uri="/time_codes")
 */

class TimeCodeController extends ApiController
{
    
    /**
     * Get Time Code Listing
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function index(Index $request)
    {
        //Insert default timecode for company here
        $timecodecheck = TimeCode::where('company_id',Auth::user()->company_id)->get();
        if(empty($timecodecheck->count()))
        {
            $timecode = TimeCode::where('company_id',0)->get()->toArray();
            foreach($timecode as $key=>$code){
                $store_data[$key]['code'] = $code['code'];
                $store_data[$key]['description'] = $code['description'];
                $store_data[$key]['code_heading'] = $code['code_heading'];
                $store_data[$key]['active'] = $code['active'];
                $store_data[$key]['company_id'] = Auth::user()->company_id;
                $store_data[$key]['created_at'] = date('Y-m-d H:i:s');
                $store_data[$key]['updated_at'] = date('Y-m-d H:i:s');
            }
            if(!empty($store_data)){
                TimeCode::insert($store_data);
            }
        }
        
        //Set Sort by & Sort by Column
        $sortBy = Helper::setSortByValue($request);

        //Set Per Page Record
        $per_page = Helper::setPerPage($request);

        $timecode = TimeCode::select('*')->where('company_id',Auth::user()->company_id);

        //Search Filter For Active Status
        if($request->has('active') && !empty($request->active)){   
            $active =  $request->active == 'true' ? 1 :0;  
            $timecode->where('active',$active);
        }

        $columns_search = ['time_codes.code','time_codes.description','time_codes.code_heading'];
        if($request->has('q') && !empty($request->q)){
            $timecode->where(function ($query) use($columns_search, $request) {
                foreach($columns_search as $column) {
                    $query->orWhere($column, 'LIKE', '%' . $request->q . '%');
                }
            });
        }

        return $this->response->paginator($timecode->orderBy($sortBy['column'], $sortBy['order'])->paginate($per_page), new TimeCodeTransformer());
    }

    /**
     * Get Single Time Code Detail
     *
     * @param  mixed $request
     * @param  mixed $timecode
     *
     * @return void
     */
    public function show(Show $request,  $timecode)
    {
        $timecode = TimeCode::where('id',$quote)->first();
        if($timecode){
            return $this->response->item($timecode, new TimeCodeTransformer());  
        }
        return response()->json([MESSAGE=>'No matching records found.'], 404);
    }

    /**
     * store Time Code
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function store(Store $request)
    {
        $model=new TimeCode;
        $requested_data = $request->all();
        $requested_data['active'] = 1;
        $requested_data['company_id'] = Auth::user()->company_id;
        $model->fill($requested_data);
        if ($model->save()) {
            return $this->response->item($model, new TimeCodeTransformer());
        } else {
              return $this->response->errorInternal('Error occurred while saving time code.');
        }
        
    }
 
    /**
     * update Time Code Details
     *
     * @param  mixed $request
     * @param  mixed $timecode
     *
     * @return void
     */
    public function update(Update $request,  TimeCode $timecode)
    {
        $requested_data = $request->all();
        TimeCode::where('id',$requested_data['id'])->update($requested_data);
        $timecode = TimeCode::where('id',$requested_data['id'])->first();
        return $this->response->item($timecode, new TimeCodeTransformer());
    }

    /**
     * destroy
     *
     * @param  mixed $request
     * @param  mixed $timecode
     *
     * @return void
     */
    public function destroy(Destroy $request, $timecode)
    {
        $timecode = TimeCode::findOrFail($timecode);

        if ($timecode->delete()) {
            return $this->response->array(['status' => 200, 'message' => 'TimeCode successfully deleted.']);
        } else {
             return $this->response->errorInternal('Error occurred while deleting TimeCode');
        }
    }

    /**
     * get Time Zones
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function getTimeZones(Request $request)
    {
        #Set Per Page Record
        $per_page = Helper::setPerPage($request);
        $timezone = Timezone::select('*');
        return $this->response->paginator($timezone->paginate($per_page), new TimeZoneTransformer());
    }

    /**
     * get Time Code List
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function getTimeCodeList(Request $request)
    {
        $timecode = TimeCode::select('*')->where('company_id',Auth::user()->company_id)->where('active',1)->orderBy('id','asc')->get()->groupBy('code_heading')->toArray();
        
        $j=0;$k=0;$page_wise_code=array();$page=1;
        foreach($timecode as $key=>$codes)
        {
            $code_values=array();$i=0;
            foreach($codes as $code_value)
            {
                $code_values[$i]['code_id']=$code_value['id'];
                $code_values[$i]['company_id']=$code_value['company_id'];
                $code_values[$i]['code']=$code_value['code'];
                $code_values[$i]['description']=$code_value['description'];
                $code_values[$i]['active']=$code_value['active'];
                $code_values[$i]['code_heading']=$code_value['code_heading'];
                $i++;
            }
            $heading_wise[$j]['heading']=$key;
            $heading_wise[$j]['code_detail']=$code_values;
            $j++;

            $page_wise_code[$k]['page']=$page;
            $page_wise_code[$k]['heading_wise_detail']=$heading_wise;

            if($j==5)
            {
                $k++;$page++;
                $j=0;
                $heading_wise=array();
            }
        }
        return $this->response->array(['status' => 200, 'time_codes' => $page_wise_code]);
    }

}

<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\ApiController;
use App\Models\InspectionDevice;
use App\Models\DeficiencyType;
use App\Transformers\DeficiencyTypeTransformer;
use App\Transformers\DeficiencyFileTransformer;
use App\Http\Requests\Api\DeficiencyTypes\Index;
use App\Http\Requests\Api\DeficiencyTypes\Show;
use App\Http\Requests\Api\DeficiencyTypes\Create;
use App\Http\Requests\Api\DeficiencyTypes\Store;
use App\Http\Requests\Api\DeficiencyTypes\Edit;
use App\Http\Requests\Api\DeficiencyTypes\Update;
use App\Http\Requests\Api\DeficiencyTypes\Destroy;
use Auth;
use App\Models\File;
use App\Models\InspectionDeviceDeficiencyFile;
use App\Helpers\Helper;
use Illuminate\Support\Facades\Storage;


/**
 * DeficiencyType
 *
 * @Resource("DeficiencyType", uri="/deficiency_types")
 */

class DeficiencyTypeController extends ApiController
{
    
    /**
     * Get Deficiency with Search Filter
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function index(Index $request)
    {   
        
        #Set Sort by & Sort by Column
        $sortBy = Helper::setSortByValueDeficiency($request);
       
        #Set Per Page Record
        $per_page = Helper::setPerPage($request);

        #Add Search Filter here
        $deficiency = DeficiencyType::select('*');

        if($request->has('q') && !empty($request->q)){
            $deficiency->where('deficiency_type', 'like' , '%'.$request->q.'%');
        }
        return $this->response->paginator($deficiency->orderBy($sortBy['column'], $sortBy['order'])->paginate($per_page), new DeficiencyTypeTransformer());
    }

    /**
     * Get Single Deficieny
     *
     * @param  mixed $request
     * @param  mixed $deficiencytype
     *
     * @return void
     */
    public function show(Show $request, $deficiencytype)
    {
        $deficiencytype = DeficiencyType::where('id',$deficiencytype)->first();
        if($deficiencytype){
             return $this->response->item($deficiencytype, new DeficiencyTypeTransformer());
        }
        return response()->json([MESSAGE=>'No matching records found.'], 404);
    }

    /**
     * Create Deficiency
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function store(Store $request)
    {
        $requested_data = $request->all();
        $requested_data['company_id'] = Auth::user()->company_id;
        $model=new DeficiencyType;
        $model->fill($requested_data);
        if ($model->save()) {
            return $this->response->item($model, new DeficiencyTypeTransformer());
        } else {
              return $this->response->errorInternal('Error occurred while saving deficiency type.');
        }
    }
 
    /**
     * Update Deficiency Type
     *
     * @param  mixed $request
     * @param  mixed $deficiencytype
     *
     * @return void
     */
    public function update(Update $request,  $deficiencytype)
    {
        $requested_data = $request->all();
        DeficiencyType::where('id',$requested_data['id'])->update($requested_data);
        $deficiency = DeficiencyType::where('id',$requested_data['id'])->first();
        return $this->response->item($deficiency, new DeficiencyTypeTransformer());
    }

    public function destroy(Destroy $request, $deficiencytype)
    {
        
    }

    /**
     * deficiency File Upload
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function deficiencyFileUpload(Request $request)
    {
        if($deficiencyfile = $this->uploadDocumentFile($request)){
            return $this->response->item($deficiencyfile, new DeficiencyFileTransformer());
        }
        return $this->response->errorInternal('Error while uploading file in deficiency. Please try again.');
    }

    /**
     * upload Document File
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function uploadDocumentFile(Request $request)
    {
        //Set area id here
        if(is_numeric($request->inspection_devices_id))
        {
            $inspection_devices_id = $request->inspection_devices_id;
        }
        else
        {
            $inspection_devices_id = InspectionDevice::where('temporary_uid',$request->inspection_devices_id)
                                        ->where('company_id',Auth::user()->company_id)
                                        ->pluck('id')->first();
        }

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
            $request_data['object_type'] = 'deficiency_document' ;
            $request_data['object_id'] = $inspection_devices_id ;
            $request_data['upload_by'] = Auth::user()->id;
            if($image = File::create($request_data)){
                //Save File Data in place file table
                $data['company_id'] = Auth::user()->company_id;
                $data['inspection_devices_id'] = $inspection_devices_id;
                $data['created_date'] = date('Y-m-d H:i:s');
                $data['ext'] = $ext;
                $data['file_id'] = $image->id;
                if($inspection_device_file = InspectionDeviceDeficiencyFile::create($data)){
                    return InspectionDeviceDeficiencyFile::where('id',$inspection_device_file->id)->first();
                }
            }
        }
        return false;
    }

    /**
     * deficiency File Listing
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function deficiencyFileListing(Request $request)
    {
        //Set Sort by & Sort by Column
        $sortBy = Helper::setSortByValue($request);

        //Set Per Page Record
        $per_page = Helper::setPerPage($request);

        if($request->has('inspection_devices_id') && !empty($request->inspection_devices_id)){
            //Start Place File Query
            $deficiencyfile = InspectionDeviceDeficiencyFile::where('inspection_devices_id', $request->inspection_devices_id );

    
            return $this->response->paginator($deficiencyfile->orderBy($sortBy['column'], $sortBy['order'])->paginate($per_page), new DeficiencyFileTransformer());
        }
        return $this->response->errorInternal('Please send inspection_devices_id.');
    }

}

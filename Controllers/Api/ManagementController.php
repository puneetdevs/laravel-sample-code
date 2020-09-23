<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\ApiController;
use App\Models\Management;
use App\Models\ManagementFile;
use App\Models\PlacesManagement;
use App\Transformers\ManagementTransformer;
use App\Transformers\ManagementFileTransformer;
use App\Transformers\ManagementContactTransformer;
use App\Transformers\PlaceManagementTransformer;
use App\Http\Requests\Api\Management\Index;
use App\Http\Requests\Api\Management\Show;
use App\Http\Requests\Api\Management\Create;
use App\Http\Requests\Api\Management\Store;
use App\Http\Requests\Api\Management\Edit;
use App\Http\Requests\Api\Management\Update;
use App\Http\Requests\Api\Management\Destroy;
use App\Http\Requests\Api\Management\UploadManagementFile;
use App\Http\Requests\Api\Management\DeleteManagementFile;
use App\Repositories\PlaceRepository;
use App\Repositories\ManagementRepository;
use Auth;
use App\Helpers\Helper;
use DB;

/**
 * Management
 *
 * @Resource("Management", uri="/management")
 */

class ManagementController extends ApiController
{
    /*Construct here*/
    public function __construct(PlaceRepository $placeRepository,ManagementRepository $managementRepository){
        $this->placeRepository = $placeRepository;
        $this->managementRepository = $managementRepository;
      }

      
    /**
     * Get Management
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function index(Index $request)
    {
        #Set Sort by & Sort by Column
        $sortBy = Helper::setSortByValue($request);

        #per page set here
        $per_page = Helper::setPerPage($request);

        $management = Management::select('*');
        if($request->has('q') && !empty($request->q)){
            $management->where('name', 'like' , '%'.$request->q.'%');
        }
        if($request->has('alert') && !empty($request->alert)  && $request->alert == 'true'){
            $management->where('alert', 1);
        }
        if($request->has('hold')  && !empty($request->hold) && $request->hold == 'true' ){
            $management->where('on_hold', 1);
        }
        if($request->has('active') && !empty($request->active)){
            $active =  $request->active == 'true' ? [1] :[0,1];  
            $management->whereIn('active',$active);
        }else{
            $management->where('active',1);
        }
        return $this->response->paginator($management->orderBy($sortBy['column'], $sortBy['order'])->paginate($per_page), new ManagementTransformer());
    }

    /**
     * Get Single Management
     *
     * @param  mixed $request
     * @param  mixed $management
     *
     * @return void
     */
    public function show(Show $request,  $management)
    {
        $management = Management::where('id',$management)->first();
        if($management){
            return $this->response->item($management, new ManagementTransformer());
        }
        return response()->json([MESSAGE=>'No matching records found.'], 404);
    }

    /**
     * Create Management
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function store(Store $request)
    {
        $requested_data = $request->all();
        $requested_data['company_id'] = Auth::user()->company_id;
        $model=new Management;
        $model->fill($requested_data);
        if ($model->save()) {
            return $this->response->item($model, new ManagementTransformer());
        } else {
              return $this->response->errorInternal('Error occurred while saving management.');
        }
    }
 
    /**
     * Update Management
     *
     * @param  mixed $request
     * @param  mixed $management
     *
     * @return void
     */
    public function update(Update $request,  $management)
    {
        $requested_data = $request->all();
        if($request->has('is_default') && $request->is_default === 1){
            Management::where('company_id',Auth::user()->company_id)->where('is_default',1)->update(['is_default'=>0]);
        }
        Management::where('id',$requested_data['id'])->update($requested_data);
        $management = Management::where('id',$requested_data['id'])->first();
        return $this->response->item($management, new ManagementTransformer());
    }

    public function destroy(Destroy $request, $management)
    {
        
    }

    /**
     * Upload Management File
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function uploadManagementFile(UploadManagementFile $request){
        if($placefile = $this->placeRepository->uploadDocumentFile($request, 'management')){
            return $this->response->item($placefile, new ManagementFileTransformer());
        }
        return $this->response->errorInternal('Error while uploading file in management. Please try again.');
    }

    /**
     * Get Management Files
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function getManagementFiles(Request $request)
    {
        #Set Sort by & Sort by Column
        $sortBy = Helper::setSortByValue($request);

        #per page set here
        $per_page = Helper::setPerPage($request);

        if($request->has('management_id') && !empty($request->management_id)){
            #Start Place File Query
            $managefile = ManagementFile::where('company_id',Auth::user()->company_id);
            $managefile->where('management_id', $request->management_id );
            
            $managefile->join('file_types', 'management_files.type_id', '=', 'file_types.id')
                ->select('management_files.*', DB::raw('(file_types.file_type) filetype'));

            return $this->response->paginator($managefile->orderBy($sortBy['column'], $sortBy['order'])->paginate($per_page), new ManagementFileTransformer());
        }
        return $this->response->errorInternal('Please send management_id.');
    }

    /**
     * Delete Management File
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function deleteManagementFile(DeleteManagementFile $request)
    {  
        if(ManagementFile::whereIn('id',$request->id)->update(['deleted_at' => date('Y-m-d H:i:s')])){
            return $this->response->array([STATUS => 200, MESSAGE => 'Successfully deleted.']);
        }
        return $this->response->errorInternal('Error while delete action perform. Please try again.');
    }

    /**
     * get Contact In Management
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function getManagementContact(Request $request)
    {
        #Set Sort by & Sort by Column
        $sortBy = Helper::setSortByValue($request);

        #Set Per Page Record
        $per_page = Helper::setPerPage($request);

        if($request->has('management_id') && !empty($request->management_id)){
            $managementcontact = $this->managementRepository->getManagementContact('management_id' ,$request->management_id);
            
            $managementcontact->join('contacts', 'management_contacts.contact_id', '=', 'contacts.id')
                ->join('contact_types', 'management_contacts.type_id', '=', 'contact_types.id')
                ->select('management_contacts.*', DB::raw('(contacts.name) contactname,(contact_types.contact_type) contacttype'));
            
            return $this->response->paginator($managementcontact->orderBy($sortBy['column'], $sortBy['order'])->paginate($per_page), new ManagementContactTransformer());
        }
        return $this->response->errorInternal('Please send management_id.');
    }

    /**
     * get Management Place
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function getManagementPlace(Request $request)
    {
        
        if($request->has('management_id') && !empty($request->management_id)){
            #Set Sort by & Sort by Column
            $sortBy = Helper::setSortByValue($request);

            #Set Per Page Record
            $per_page = Helper::setPerPage($request);
            $managementplace = $this->managementRepository->getManagementPlace('management_id' ,$request->management_id);
            
            $managementplace->join('places', 'places_management.place_id', '=', 'places.id')
                ->join('places_management_types', 'places_management.type_id', '=', 'places_management_types.id')
                ->select('places_management.*', DB::raw('(places.name) name,(places_management_types.place_management_type) managementtype'));


            return $this->response->paginator($managementplace->orderBy($sortBy['column'], $sortBy['order'])->paginate($per_page), new PlaceManagementTransformer());
        }
        return $this->response->errorInternal('Please send management_id.');
    }

    
    /**
     * Get History Report
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function getHistoryReport(Request $request){
        if($request->has('management_id') && !empty($request->management_id)){
            $place_management = PlacesManagement::where('management_id',$request->management_id)
                                                ->with(['management','invoice'=>function($q){
                                                    $q->where('posted',1);
                                                },'invoice.payment'=>function($q){
                                                    $q->orderBy('id','desc');
                                                }])->whereHas('invoice')->get()->toArray();
            $final_data = array();
            $management_data = array();
            $days_to_pay = 0;
            $i=0;
            if(!empty($place_management)){
                foreach($place_management as $k=>$management){
                    $management_data = $management;
                    if(!empty($management['invoice'])){
                        foreach($management['invoice'] as $key=>$invoice_data){                
                            if(isset($invoice_data['payment']) && !empty($invoice_data['payment'])){
                                $date1=date_create(date('Y-m-d'));
                                $date2=date_create(date('Y-m-d',strtotime($invoice_data['date'])));
                                $diff=date_diff($date1,$date2);
                                $date_diff = $diff->format("%a");
                                $days_to_pay += $date_diff;
                                $invoice_data['payment']['days_to_pay'] = $date_diff;
                            }
                            $final_data[$i] =  $invoice_data;
                            $i++;
                        }
                    }
                }
                $average_days = '';
                if($days_to_pay > 0){
                    $average_days = $days_to_pay/count($final_data);
                }
                $management_data['invoice'] = $final_data;
                $management_data['average_days'] = number_format((float)$average_days, 2, '.', '');
            }
            $response = $this->response->array([STATUS => 200, 'data' => $management_data]);
        }else{
            $response = response()->json([MESSAGE=>'Please send management id.'], 406);
        }
        return $response; 
    }
    
    /**
     * get Outstanding Report
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function getOutstandingReport(Request $request){
        if($request->has('management_id') && !empty($request->management_id)){
            $place_management = PlacesManagement::where('management_id',$request->management_id)
                                                ->with(['management','invoice'=>function($q){
                                                    $q->where('posted',1);
                                                }])->whereHas('invoice')->get()->toArray();
            $final_data = array();
            $management_data = array();
            $i=0;
            if(!empty($place_management)){
                foreach($place_management as $k=>$management){
                    $management_data = $management;
                    if(!empty($management['invoice'])){
                        foreach($management['invoice'] as $key=>$invoice_data){  
                            $final_data[$i] =  $invoice_data;
                            $i++;
                        }
                    }
                }
                $management_data['invoice'] = $final_data;
            }
            $response = $this->response->array([STATUS => 200, 'data' => $management_data]);
        }else{
            $response = response()->json([MESSAGE=>'Please send management id.'], 406);
        }
        return $response; 
    }
    
}

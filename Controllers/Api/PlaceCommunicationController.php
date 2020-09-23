<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\ApiController;
use App\Models\PlaceCommunication;
use App\Models\PlaceCommunicationType;
use App\Models\PlacesManagement;
use App\Transformers\PlaceCommunicationTransformer;
use App\Transformers\CommunicationTypeTransformer;
use App\Http\Requests\Api\PlaceCommunications\Index;
use App\Http\Requests\Api\PlaceCommunications\Show;
use App\Http\Requests\Api\PlaceCommunications\Create;
use App\Http\Requests\Api\PlaceCommunications\Store;
use App\Http\Requests\Api\PlaceCommunications\Edit;
use App\Http\Requests\Api\PlaceCommunications\Update;
use App\Http\Requests\Api\PlaceCommunications\Destroy;
use App\Http\Requests\Api\PlaceCommunications\DeletePlaceCommunication;
use App\Http\Requests\Api\PlaceCommunications\CommReport;
use Auth;
use App\Helpers\Helper;
use DB;

/**
 * PlaceCommunication
 *
 * @Resource("PlaceCommunication", uri="/place_communications")
 */

class PlaceCommunicationController extends ApiController
{
    
    /**
     * Get Place Communication Listing
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function index(Index $request)
    {
        //Set Sort by & Sort by Column
        $sortBy = Helper::setSortByValue($request);

        //per page set here
        $per_page = Helper::setPerPage($request);
        
        if($request->has(PLACE_ID) && !empty($request->place_id)){
            //Start Place Communication Query
            $placecommunication = PlaceCommunication::where(COMPANY_ID,Auth::user()->company_id);
            $placecommunication->where(PLACE_ID, $request->place_id );

            $placecommunication->join('place_communication_types', 'place_communications.place_communication_type_id', '=', 'place_communication_types.id')
                ->select('place_communications.*', DB::raw('(place_communication_types.place_communication_type) communicationtype'));

            return $this->response->paginator($placecommunication->orderBy($sortBy['column'], $sortBy['order'])->paginate($per_page), new PlaceCommunicationTransformer());
        }
        return $this->response->errorInternal('Please send place_id.');
    }

    /**
     * Get Place Communication Detail
     *
     * @param  mixed $request
     * @param  mixed $placecommunication
     *
     * @return void
     */
    public function show(Show $request, $placecommunication)
    {
        $placecommunication = PlaceCommunication::where('id',$placecommunication)->first();
        if($placecommunication){
            return $this->response->item($placecommunication, new PlaceCommunicationTransformer());
        }
        return response()->json([MESSAGE=>'No matching records found.'], 404);
    }

    /**
     * Add New Place Communication
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function store(Store $request)
    {
        $request_data = $request->all();
        $request_data['company_id'] = Auth::user()->company_id;
        $model=new PlaceCommunication;
        $model->fill($request->all());
        if ($model->save()) {
            return $this->response->item($model, new PlaceCommunicationTransformer());
        } else {
              return $this->response->errorInternal('Error occurred while saving place communication.');
        }
    }
 
    /**
     * Update Place Communication
     *
     * @param  mixed $request
     * @param  mixed $placecommunication
     *
     * @return void
     */
    public function update(Update $request, $placecommunication)
    {
        $requested_data = $request->all();
        PlaceCommunication::where('id',$requested_data['id'])->update($requested_data);
        $placecommunication = PlaceCommunication::where('id',$requested_data['id'])->first();
        return $this->response->item($placecommunication, new PlaceCommunicationTransformer());
    }

    public function destroy(Destroy $request, $placecommunication)
    {
        
    }

    /**
     * Get Place Communication Type
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function getCommunicationType(Request $request)
    {
        //per page set here
        $per_page = Helper::setPerPage($request);

        return $this->response->paginator(PlaceCommunicationType::paginate($per_page), new CommunicationTypeTransformer());
    }

    /**
     * Delete Place Communication
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function deletePlaceCommunication(DeletePlaceCommunication $request)
    {  
        if(PlaceCommunication::whereIn('id',$request->id)->update(['deleted_at' => date('Y-m-d H:i:s')])){
            return $this->response->array(['status' => 200, 'message' => 'Successfully deleted.']);
        }
        return $this->response->errorInternal('Error while delete action perform. Please try again.');
    }

    /**
     * get Communication Report
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function getCommunicationReport(CommReport $request)
    {
        $communicationReport='';
        $places_management = array();
        //Check Management here
        if($request->has('management_id') && !empty($request->management_id)){
            $places_management = PlacesManagement::where(COMPANY_ID,Auth::user()->company_id)
                                                    ->where('management_id',$request->management_id)
                                                    ->get()->pluck(PLACE_ID)->toArray();
            if(!empty($places_management)){
                //Query Start here and Star/End date Check here
                $placecommunication = PlaceCommunication::where(COMPANY_ID,Auth::user()->company_id)
                                                        ->whereIn(PLACE_ID,$places_management)
                                                        ->whereBetween('due_date', [$request->start_date,$request->end_date]);
                //Comm Type Check here
                if($request->has('type') && !empty($request->type)){
                $placecommunication->where('place_communication_type_id',$request->type);
                }
                //Star/End date Check here
                $communicationReport = $placecommunication->with(['type','place.street'])->get()->toArray();
            }                                        
        }else{
            //Query Start here and Star/End date Check here
            $placecommunication = PlaceCommunication::where(COMPANY_ID,Auth::user()->company_id)
                                                    ->whereBetween('due_date', [$request->start_date,$request->end_date]);
            //Comm Type Check here
            if($request->has('type') && !empty($request->type)){
            $placecommunication->where('place_communication_type_id',$request->type);
            }
            //Star/End date Check here
            $communicationReport = $placecommunication->with(['type','place.street'])->get()->toArray(); 
        }
       
        return $this->response->array(['status' => 200, 'data' => $communicationReport]);
    }

}

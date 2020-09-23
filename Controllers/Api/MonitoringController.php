<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\ApiController;
use App\Models\Monitoring;
use App\Transformers\MonitoringTransformer;
use App\Http\Requests\Api\Monitoring\Index;
use App\Http\Requests\Api\Monitoring\Show;
use App\Http\Requests\Api\Monitoring\Create;
use App\Http\Requests\Api\Monitoring\Store;
use App\Http\Requests\Api\Monitoring\Edit;
use App\Http\Requests\Api\Monitoring\Update;
use App\Http\Requests\Api\Monitoring\Destroy;
use App\Http\Requests\Api\Monitoring\DeleteMonitoring;
use App\Http\Requests\Api\Monitoring\MonitoringInvoice;
use App\Repositories\MonitoringRepository;
use Auth;
use App\Helpers\Helper;

/**
 * Monitoring
 *
 * @Resource("Monitoring", uri="/monitoring")
 */

class MonitoringController extends ApiController
{
    /*Construct here define Monitoring Repository */
    public function __construct(MonitoringRepository $monitoringRepository){
        $this->monitoringRepository = $monitoringRepository;
    }

    /**
     * Get Monitoring Listing
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

        #Query Start here and Check place_id is send or not
        if($request->has('place_id') && !empty($request->place_id)){
            return $this->response->paginator(Monitoring::where('place_id',$request->place_id)->orderBy($sortBy['column'], $sortBy['order'])->paginate($per_page), new MonitoringTransformer());
        }
        return $this->response->errorInternal('Please send place_id.');
    }

    /**
     * Get Single Monitoring Detail
     *
     * @param  mixed $request
     * @param  mixed $monitoring
     *
     * @return void
     */
    public function show(Show $request, $monitoring)
    {
        $monitoring = Monitoring::where('id',$monitoring)->first();
        if($monitoring){
            return $this->response->item($monitoring, new MonitoringTransformer());
        }
        return response()->json([MESSAGE=>'No matching records found.'], 404);
    }

    /**
     * store monitoring
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function store(Store $request)
    {
        $model=new Monitoring;
        $requested_data = $request->all();
        $requested_data['created_date'] = date('Y-m-d H:i:s');
        $model->fill($requested_data);
        if ($model->save()) {
            return $this->response->item($model, new MonitoringTransformer());
        } else {
              return $this->response->errorInternal('Error occurred while saving monitoring.');
        }
    }
 
    /**
     * update Monitoring
     *
     * @param  mixed $request
     * @param  mixed $monitoring
     *
     * @return void
     */
    public function update(Update $request,   $monitoring)
    {
        $requested_data = $request->all();
        Monitoring::where('id',$requested_data['id'])->update($requested_data);
        $monitoring = Monitoring::where('id',$requested_data['id'])->first();
        return $this->response->item($monitoring, new MonitoringTransformer());
    }

    public function destroy(Destroy $request, $monitoring)
    {
        
    }

    /**
     * Delete Monitoring
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function deleteMonitoring(DeleteMonitoring $request)
    {  
        if(Monitoring::whereIn('id',$request->id)->update(['deleted_at' => date('Y-m-d H:i:s')])){
            return $this->response->array(['status' => 200, 'message' => 'Successfully deleted.']);
        }
        return $this->response->errorInternal('Error while delete action perform. Please try again.');
    }

    /**
     * Get Monitoring Report
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function getMonitoringReport(Request $request)
    {
        if($request->has('invoice_date') && !empty($request->invoice_date)){
            if($request->has('due_date') && !empty($request->due_date)){
                #per page set here
                $per_page = Helper::setPerPage($request);
                $monitoring = $this->monitoringRepository->getMonitoringReport($request);
                return $this->response->paginator($monitoring->paginate($per_page), new MonitoringTransformer());
            }
            return $this->response->errorInternal('Please send due date.');
        }
        return $this->response->errorInternal('Please send invoice date.');
    }

    /**
     * create Monitoring Invoice
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function createMonitoringInvoice(MonitoringInvoice $request)
    {
        $invoice = $this->monitoringRepository->createMonitoringInvoice($request);
        return $this->response->array(['status' => 200, 'data' => $invoice]);
           
    }

}

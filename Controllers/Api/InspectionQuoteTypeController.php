<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\ApiController;
use App\Models\InspectionQuoteType;
use App\Transformers\InspectionQuoteTypeTransformer;
use App\Http\Requests\Api\InspectionQuoteTypes\Index;
use App\Http\Requests\Api\InspectionQuoteTypes\Show;
use App\Http\Requests\Api\InspectionQuoteTypes\Create;
use App\Http\Requests\Api\InspectionQuoteTypes\Store;
use App\Http\Requests\Api\InspectionQuoteTypes\Edit;
use App\Http\Requests\Api\InspectionQuoteTypes\Update;
use App\Http\Requests\Api\InspectionQuoteTypes\Destroy;
use Auth;
use App\Helpers\Helper;

/**
 * InspectionQuoteType
 *
 * @Resource("InspectionQuoteType", uri="/inspection_quote_types")
 */

class InspectionQuoteTypeController extends ApiController
{
    
    /**
     * Get Inspection Quote Types
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

        $inspectionquotetype = InspectionQuoteType::select('*');
        if($request->has('q') && !empty($request->q)){
            $inspectionquotetype->where('inspection_quote_type', 'like' , '%'.$request->q.'%');
        }
       return $this->response->paginator($inspectionquotetype->orderBy($sortBy['column'], $sortBy['order'])->paginate($per_page), new InspectionQuoteTypeTransformer());
    }

    /**
     * Get Inspection Quote Type Detail
     *
     * @param  mixed $request
     * @param  mixed $inspectionquotetype
     *
     * @return void
     */
    public function show(Show $request, $inspectionquotetype)
    {
        $inspectionquotetype = InspectionQuoteType::where('id',$inspectionquotetype)->first();
        if($inspectionquotetype){
            return $this->response->item($inspectionquotetype, new InspectionQuoteTypeTransformer());
        }
        return response()->json([MESSAGE=>'No matching records found.'], 404);
    }

    /**
     * Store Inspection Quote Type
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function store(Store $request)
    {
        $requested_data = $request->all();
        $requested_data['company_id'] = Auth::user()->company_id;
        $model=new InspectionQuoteType;
        $model->fill($requested_data);
        if ($model->save()) {
            return $this->response->item($model, new InspectionQuoteTypeTransformer());
        } else {
              return $this->response->errorInternal('Error occurred while saving inspection quote type.');
        }
    }
 
    /**
     * Update Inspection Quote Type
     *
     * @param  mixed $request
     * @param  mixed $inspectionquotetype
     *
     * @return void
     */
    public function update(Update $request, $inspectionquotetype)
    {
        $requested_data = $request->all();
        InspectionQuoteType::where('id',$requested_data['id'])->update($requested_data);
        $inspectionquotetype = InspectionQuoteType::where('id',$requested_data['id'])->first();
        return $this->response->item($inspectionquotetype, new InspectionQuoteTypeTransformer());
    }

    public function destroy(Destroy $request, $inspectionquotetype)
    {
        
    }

}

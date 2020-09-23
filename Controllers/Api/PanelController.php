<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\ApiController;
use App\Models\Panel;
use App\Transformers\PanelTransformer;
use App\Http\Requests\Api\Panels\Index;
use App\Http\Requests\Api\Panels\Show;
use App\Http\Requests\Api\Panels\Create;
use App\Http\Requests\Api\Panels\Store;
use App\Http\Requests\Api\Panels\Edit;
use App\Http\Requests\Api\Panels\Update;
use App\Http\Requests\Api\Panels\Destroy;
use Auth;
use App\Helpers\Helper;

/**
 * Panel
 *
 * @Resource("Panel", uri="/panels")
 */

class PanelController extends ApiController
{
    
    /**
     * Get Panel Listing
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
        
        #start query with Search Filter
        $panel = Panel::select('*');

        if($request->has('active') && !empty($request->active)){   
            $active =  $request->active == 'true' ? 1 :0;  
            $panel->where('active',$active);
        }

        if($request->has('q') && !empty($request->q)){
            $panel->where('panel', 'LIKE', '%' . $request->q . '%');
        }
        return $this->response->paginator($panel->orderBy($sortBy['column'], $sortBy['order'])->paginate($per_page), new PanelTransformer());
    }

    /**
     * show Single Panel Detail
     *
     * @param  mixed $request
     * @param  mixed $panel
     *
     * @return void
     */
    public function show(Show $request, $panel)
    {
        
        $panel = Panel::where('id',$panel)->first();
        if($panel){
            return $this->response->item($panel, new PanelTransformer());
        }
        return response()->json([MESSAGE=>'No matching records found.'], 404);
    }

    /**
     * store Panel
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function store(Store $request)
    {
        $requested_data = $request->all();
        $requested_data['company_id'] = Auth::user()->company_id;
        $model=new Panel;
        $model->fill($requested_data);
        if ($model->save()) {
            return $this->response->item($model, new PanelTransformer());
        } else {
              return $this->response->errorInternal('Error occurred while saving panel.');
        }
    }
 
    /**
     * update Panel Detail
     *
     * @param  mixed $request
     * @param  mixed $panel
     *
     * @return void
     */
    public function update(Update $request,   $panel)
    {
        $requested_data = $request->all();
        Panel::where('id',$requested_data['id'])->update($requested_data);
        $panel = Panel::where('id',$requested_data['id'])->first();
        return $this->response->item($panel, new PanelTransformer());
    }

    public function destroy(Destroy $request, $panel)
    {
        
    }

}

<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\ApiController;
use App\Models\Part;
use App\Transformers\PartTransformer;
use App\Http\Requests\Api\Parts\Index;
use App\Http\Requests\Api\Parts\Show;
use App\Http\Requests\Api\Parts\Create;
use App\Http\Requests\Api\Parts\Store;
use App\Http\Requests\Api\Parts\Edit;
use App\Http\Requests\Api\Parts\Update;
use App\Http\Requests\Api\Parts\Destroy;
use App\Helpers\Helper;

/**
 * Part
 *
 * @Resource("Part", uri="/parts")
 */

class PartController extends ApiController
{
    
    /**
     * Get Part Listing
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
        
        #Start Vendor Query
        $part = Part::select('*');
        
        if($request->has('number') && !empty($request->number)){    
            $part->where('number','like','%'.$request->number.'%');
        }
        if($request->has('code') && !empty($request->code)){    
            $part->where('code','like','%'.$request->code.'%');
        }
        if($request->has('name') && !empty($request->name)){    
            $part->where('name','like','%'.$request->name.'%');
        }

        return $this->response->paginator($part->orderBy($sortBy['column'], $sortBy['order'])->paginate($per_page), new PartTransformer());
    }

    /**
     * show Single Part Detail
     *
     * @param  mixed $request
     * @param  mixed $part
     *
     * @return void
     */
    public function show(Show $request, $part)
    {
        $part = Part::where('id',$part)->first();
        if($part){
            return $this->response->item($part, new PartTransformer());  
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
        $model=new Part;
        $model->fill($request->all());
        if ($model->save()) {
            return $this->response->item($model, new PartTransformer());
        } else {
              return $this->response->errorInternal('Error occurred while saving part.');
        }
    }
 
    /**
     * update Part Detail
     *
     * @param  mixed $request
     * @param  mixed $part
     *
     * @return void
     */
    public function update(Update $request,  Part $part)
    {
        $requested_data = $request->all();
        Part::where('id',$requested_data['id'])->update($requested_data);
        $part = Part::where('id',$requested_data['id'])->first();
        return $this->response->item($part, new PartTransformer());
    }

    /**
     * delete Part
     *
     * @param  mixed $request
     * @param  mixed $part
     *
     * @return void
     */
    public function destroy(Destroy $request, $part)
    {
        $part = Part::findOrFail($part);

        if ($part->delete()) {
            return $this->response->array(['status' => 200, 'message' => 'Part successfully deleted.']);
        } else {
             return $this->response->errorInternal('Error occurred while deleting part.');
        }
    }

}

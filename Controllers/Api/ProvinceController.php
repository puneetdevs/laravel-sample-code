<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\ApiController;
use App\Models\Province;
use App\Models\Country;
use App\Transformers\ProvinceTransformer;
use App\Transformers\CountryTransformer;
use App\Http\Requests\Api\Province\Index;
use App\Http\Requests\Api\Province\Show;
use App\Http\Requests\Api\Province\Create;
use App\Http\Requests\Api\Province\Store;
use App\Http\Requests\Api\Province\Edit;
use App\Http\Requests\Api\Province\Update;
use App\Http\Requests\Api\Province\Destroy;
use App\Helpers\Helper;
use DB;

/**
 * Province
 *
 * @Resource("Province", uri="/province")
 */

class ProvinceController extends ApiController
{
    
    /**
     * Get Province Listing
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

        #Start province Query
        $province = Province::select('*');
        #Add check active/inactive
        if($request->has('active')){
            $province->where('active',$request->active);
        }
        #Add check search
        if($request->has('q')){
            $province->where(function ($q) use($request) {
                $q->orWhere('state', 'LIKE', '%' . $request->q . '%');
                $q->orWhereHas('country',function($q_countries) use($request){
                    $q_countries->where('country', 'LIKE', '%' . $request->q . '%');
                }); 
            });


        }
        
        $province->join('countries', 'province.country_id', '=', 'countries.id')
        ->select('province.*');
        return $this->response->paginator($province->orderBy($sortBy['column'], $sortBy['order'])->paginate($per_page), new ProvinceTransformer());
    }

    /**
     * Get Single Province
     *
     * @param  mixed $request
     * @param  mixed $province
     *
     * @return void
     */
    public function show(Show $request, $province)
    {
        $province = Province::where('id',$province)->first();
        if($province){
            return $this->response->item($province, new ProvinceTransformer());
        }
        return response()->json([MESSAGE=>'No matching records found.'], 404);
    }

    /**
     * Create New Province
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function store(Store $request)
    {
        $model=new Province;
        $model->fill($request->all());
        if ($model->save()) {
            return $this->response->item($model, new ProvinceTransformer());
        } else {
              return $this->response->errorInternal('Error occurred while saving province.');
        }
    }
 
    /**
     * update province
     *
     * @param  mixed $request
     * @param  mixed $province
     *
     * @return void
     */
    public function update(Update $request, $province)
    {
        $requested_data = $request->all();
        Province::where('id',$requested_data['id'])->update($requested_data);
        $province = Province::where('id',$requested_data['id'])->first();
        return $this->response->item($province, new ProvinceTransformer());
    }

    public function destroy(Destroy $request, $province)
    {
        
    }

    /**
     * get Country
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function getCountry(Request $request)
    {
        #Set Per Page Record
        $per_page = Helper::setPerPage($request);
        
        return $this->response->paginator(Country::paginate($per_page), new CountryTransformer());
    }

}

<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\ApiController;
use App\Models\InspectionQuote;
use App\Transformers\InspectionQuoteTransformer;
use App\Http\Requests\Api\InspectionQuotes\Index;
use App\Http\Requests\Api\InspectionQuotes\Show;
use App\Http\Requests\Api\InspectionQuotes\Create;
use App\Http\Requests\Api\InspectionQuotes\Store;
use App\Http\Requests\Api\InspectionQuotes\Edit;
use App\Http\Requests\Api\InspectionQuotes\Update;
use App\Http\Requests\Api\InspectionQuotes\Destroy;
use Auth;
use App\Helpers\Helper;
use DB;

/**
 * InspectionQuote
 *
 * @Resource("InspectionQuote", uri="/inspection_quotes")
 */

class InspectionQuoteController extends ApiController
{
    
    /**
     * Get Inspection Quotes
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
       
        #Start Inspection Quote Query
        $inspectionquote = InspectionQuote::select('*');

        #Search Inspection Quote Of Specific Place
        if($request->has('place_id') && !empty($request->place_id)){    
            $inspectionquote->where('inspection_quotes.place_id', $request->place_id );
        }

        #Search Inspection Quote With Number
        if($request->has('q') && !empty($request->q)){


            $inspection_values=$request->q;
                $count=strlen($inspection_values);
                
                $number_check=(substr($request->q, 4,1));
                #Add dash in the quote no
                if( $count>3 && is_numeric($inspection_values) )
                {
                    $number=substr_replace( $inspection_values,'-', 4, 0 );
                    $inspectionquote->orWhere('inspection_quotes.number', 'LIKE', '%' . $number . '%');
                }
                else if($number_check=='-')
                {
                    $inspectionquote->orWhere('inspection_quotes.number', 'LIKE', '%' . $request->q . '%');
                }

                $inspectionquote->orWhere('inspection_quotes.total', 'LIKE', '%' . $request->q . '%');
            
                $inspection_values = Helper::dateFormatConvert($request->q);
                if($inspection_values) {
                    $inspectionquote->orWhere('inspection_quotes.date', 'LIKE', '%' . $inspection_values . '%');
                    $inspectionquote->orWhere('inspection_quotes.due_date', 'LIKE', '%' . $inspection_values . '%');
                }
           

            $inspectionquote->orWhereHas('management',function($q) use($request){
                $q->where('management.name', 'LIKE', '%' . $request->q . '%');
            });

            $inspectionquote->orWhereHas('place',function($q) use($request){
                $q->where('suite', 'LIKE', '%' . $request->q . '%');
            });

            $inspectionquote->orWhereHas('place',function($q) use($request){
                $q->where('street_number', 'LIKE', '%' . $request->q . '%');
            });

            $inspectionquote->orWhereHas('place',function($q) use($request){
                $q->where('street_name', 'LIKE', '%' . $request->q . '%');
            });

            $inspectionquote->orWhereHas('place.street',function($q) use($request){
                $q->where('street_type', 'LIKE', '%' . $request->q . '%');
            });
        }

        $inspectionquote->join('inspection_quote_types', 'inspection_quotes.inspection_quote_type_id', '=', 'inspection_quote_types.id')
                ->join('places', 'inspection_quotes.place_id', '=', 'places.id')
                ->leftJoin('places_management', 'inspection_quotes.management_id', '=', 'places_management.id')
                ->leftJoin('management', 'places_management.management_id', '=', 'management.id')
                ->select('inspection_quotes.*', DB::raw('(inspection_quote_types.inspection_quote_type) quotetype,(places.suite) placesuite,(management.name) managementname'));

        return $this->response->paginator($inspectionquote->orderBy($sortBy['column'], $sortBy['order'])->paginate($per_page), new InspectionQuoteTransformer());
        
    }

    /**
     * Get Inspection Quote Detail
     *
     * @param  mixed $request
     * @param  mixed $inspectionquote
     *
     * @return void
     */
    public function show(Show $request, $inspectionquote)
    {
        $inspectionquote=InspectionQuote::where('id',$inspectionquote)->first();
        if($inspectionquote){
            return $this->response->item($inspectionquote, new InspectionQuoteTransformer());
        }
        return response()->json([MESSAGE=>'No matching records found.'], 404);
    }

    /**
     * Store Inspection Quote
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function store(Store $request)
    {
        $request_data = $request->all();
        $request_data['company_id'] = Auth::user()->company_id;
        $model=new InspectionQuote;
        $model->fill($request_data);
        if ($model->save()) {
            #Set Number here
            $inspectionquote_number = InspectionQuote::where('company_id',Auth::user()->company_id)->withTrashed()->count();
            $number= Helper::setNumberCompanyWise($inspectionquote_number,'inspection_quotes'); 
            InspectionQuote::where('id',$model->id)->update(['number' => $number]);
            $model->number=$number;
            return $this->response->item($model, new InspectionQuoteTransformer());
        } else {
              return $this->response->errorInternal('Error occurred while saving inspection quote.');
        }
    }
 
    /**
     * Update Inspection Quote
     *
     * @param  mixed $request
     * @param  mixed $inspectionquote
     *
     * @return void
     */
    public function update(Update $request, $inspectionquote)
    {
        $requested_data = $request->all();
        InspectionQuote::where('id',$requested_data['id'])->update($requested_data);
        $inspectionquote = InspectionQuote::where('id',$requested_data['id'])->first();
        return $this->response->item($inspectionquote, new InspectionQuoteTransformer());
    }

    public function destroy(Destroy $request, $inspectionquote)
    {
        $inspectionquote = InspectionQuote::findOrFail($inspectionquote);

        if ($inspectionquote->delete()) {
            return $this->response->array(['status' => 200, 'message' => 'Inspection quote successfully deleted.']);
        } else {
             return $this->response->errorInternal('Error occurred while deleting Inspection quote.');
        }
    }

}

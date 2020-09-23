<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\ApiController;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\QuoteStatus;
use App\Models\AppSetting;
use App\Models\PlacesManagementType;
use App\Models\PlacesManagement;
use App\Transformers\QuoteTransformer;
use App\Transformers\QuoteStatusTransformer;
use App\Transformers\QuoteItemTransformer;
use App\Transformers\PlaceManagementTransformer;
use App\Transformers\AppSettingTransformer;
use App\Http\Requests\Api\Quotes\Index;
use App\Http\Requests\Api\Quotes\Show;
use App\Http\Requests\Api\Quotes\Create;
use App\Http\Requests\Api\Quotes\Store;
use App\Http\Requests\Api\Quotes\Edit;
use App\Http\Requests\Api\Quotes\Update;
use App\Http\Requests\Api\Quotes\Destroy;
use App\Repositories\QuoteRepository;
use Auth;
use DB;
use App\Helpers\Helper;

/**
 * Quote
 *
 * @Resource("Quote", uri="/quotes")
 */

class QuoteController extends ApiController
{
    /*Construct here define quote repository */
    public function __construct(QuoteRepository $quoteRepository){
        $this->quoteRepository = $quoteRepository;
    }
    
    /**
     * Get Quote Listing 
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
        
        #Start Quote Query
        $quote = Quote::select('*');

        #Search Quote Of Specific Name
        if($request->has('q') && !empty($request->q)){

                $quote_values=$request->q;
                $count=strlen($quote_values);
                $number_check=(substr($request->q, 4,1));
                #Add dash in the quote no
                if( $count>3 && is_numeric($quote_values) )
                {
                    $number=substr_replace( $quote_values,'-', 4, 0 );
                    $quote->Where('quotes.number', 'LIKE', '%' . $number . '%');
                }
                else if($number_check=='-')
                {
                    $quote->Where('quotes.number', 'LIKE', '%' . $request->q . '%');
                }
                
                #quote search with date
                $converted_date = Helper::dateFormatConvert($request->q);
                if($converted_date) {
                    $quote->Where('quotes.date', 'LIKE', '%' . $converted_date . '%');
                }

                $quote->orWhereHas('billtoid',function($q) use($request){
                    $q->where('management.name', 'LIKE', '%' . $request->q . '%');
                });

                $quote->orWhereHas(PLACE,function($q) use($request){
                    $q->where('suite', 'LIKE', '%' . $request->q . '%');
                });

                $quote->orWhereHas(PLACE,function($q) use($request){
                    $q->where('street_number', 'LIKE', '%' . $request->q . '%');
                });

                $quote->orWhereHas(PLACE,function($q) use($request){
                    $q->where('street_name', 'LIKE', '%' . $request->q . '%');
                });

                $quote->orWhereHas('place.street',function($q) use($request){
                    $q->where('street_type', 'LIKE', '%' . $request->q . '%');
                });

        }

        $quote->join('places', 'quotes.place_id', '=', 'places.id')
                ->join('places_management', 'quotes.bill_to_id', '=', 'places_management.id')
                ->join('management', 'places_management.management_id', '=', 'management.id')
                ->select('quotes.*', DB::raw('(places.suite) placesuite,(management.name) billtomanage'));
            

       return $this->response->paginator($quote->orderBy($sortBy['column'], $sortBy['order'])->paginate($per_page), new QuoteTransformer());
    }

    /**
     * Get Single Quote
     *
     * @param  mixed $request
     * @param  mixed $quote
     *
     * @return void
     */
    public function show(Show $request,  $quote)
    {
        $quote = Quote::where('id',$quote)->first();
        if($quote){
            return $this->response->item($quote, new QuoteTransformer());  
        }
        return response()->json([MESSAGE=>'No matching records found.'], 404);
    }

    /**
     * Store Quote with Quote Item
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function store(Store $request)
    {
        $requested_data = $request->all();
        $model=new Quote;
        $requested_data['company_id'] = Auth::user()->company_id;
        $requested_data['created_by_id'] = Auth::user()->id;
        $requested_data['created_date'] = date('Y-m-d H:i:s');
        if(!isset($requested_data['bill_to_id']) && !isset($requested_data['management_id'])){
            $owner = PlacesManagementType::where('place_management_type','Owner')->first();
            $Property = PlacesManagementType::where('place_management_type','Property Manager')->first();
            $place_management_owner = PlacesManagement::where(PLACE_ID,$request->place_id)->where(TYPE_ID,$owner->id)->first();
            $place_management_property = PlacesManagement::where(PLACE_ID,$request->place_id)->where(TYPE_ID,$Property->id)->first();
            if($place_management_owner){
                $requested_data['bill_to_id'] = $place_management_owner->id;
            }else{
                return $this->response->array([STATUS => 466, MESSAGE => 'This place needs and owner and property manager attached before you can create a work order.']);
            }
            if($place_management_property){
                $requested_data['management_id'] = $place_management_property->id;
            }else{
                return $this->response->array([STATUS => 466, MESSAGE => 'This place needs and owner and property manager attached before you can create a work order.']);
            }
        }
        $model->fill($requested_data);
        if ($model->save()) {
            #Save Item Here
            $this->quoteRepository->saveQuoteItem($model->id,$request);
            $quote_number = Quote::where('company_id',Auth::user()->company_id)->withTrashed()->count();
            $number= Helper::setNumberCompanyWise($quote_number,'quotes');  
            Quote::where('id',$model->id)->update(['number' => $number]);
            $model->number=$number;
            return $this->response->item($model, new QuoteTransformer());
        }
        return $this->response->errorInternal('Error occurred while saving quote.');
    }
 
    /**
     * update Quote with Item
     *
     * @param  mixed $request
     * @param  mixed $quote
     *
     * @return void
     */
    public function update(Update $request,  Quote $quote)
    {
        $requested_data = $request->all();
        $input_data = $request->only('id','place_id','bill_to_id',MANAGEMENT_ID,'notes','subtotal',
         'pst', 'gst', 'total', 'override_gst', 'override_pst', 'bartec_number', 'profit', 'cost'
         , 'markup', 'quote_type', 'office_notes', 'quote_status_id','date');
        Quote::where('id',$requested_data['id'])->update($input_data);
        $quote = Quote::where('id',$requested_data['id'])->first();
        #Save Item Here
        $this->quoteRepository->saveQuoteItem($quote->id,$request);
        if($request->has('delete_quote_item') && !empty($request->delete_quote_item)){
            QuoteItem::whereIn('id',$request->delete_quote_item)->update(['deleted_at' => date('Y-m-d H:i:s')]);
        }
        return $this->response->item($quote, new QuoteTransformer());
    }

    /**
     * destroy
     *
     * @param  mixed $request
     * @param  mixed $quote
     *
     * @return void
     */
    public function destroy(Destroy $request, $quote)
    {
        $quote = Quote::findOrFail($quote);
        if ($quote->delete()) {
            return $this->response->array([STATUS => 200, MESSAGE => 'Quote successfully deleted.']);
        } 
        return $this->response->errorInternal('Error occurred while deleting quote.');
    }

    /**
     * Get Quote Status
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function getQuoteStatus(Request $request)
    {
        #Set Per Page Record
        $per_page = Helper::setPerPage($request);
        return $this->response->paginator(QuoteStatus::paginate($per_page), new QuoteStatusTransformer());
    }

    /**
     * Get Quote Item
     *
     * @param  mixed $request
     * @param  mixed $quote_id
     *
     * @return void
     */
    public function getQuoteItem(Request $request, $quote_id)
    {
        #Set Per Page Record
        $per_page = Helper::setPerPage($request);
        $quoteitem = QuoteItem::where('quote_id',$quote_id)->where('company_id',Auth::user()->company_id);
        return $this->response->paginator($quoteitem->paginate($per_page), new QuoteItemTransformer());
    }

    
    /**
     * check Managment For Winterization
     *
     * @param  mixed $request
     * @param  mixed $place_id
     *
     * @return void
     */
    public function checkManagmentForWinterization(Request $request, $place_id)
    {
        #owner_managment
        $owner_place_management_type = PlacesManagementType::where('place_management_type','Owner')->first();
        $owner_management = PlacesManagement::where(PLACE_ID,$place_id)
                                ->where(TYPE_ID,$owner_place_management_type->id)
                                ->where('company_id',Auth::user()->company_id)
                                ->orderBy('id','desc')->pluck('id')->first();
        
        if($owner_management)
        {
            #property_managment
            $property_place_management_type = PlacesManagementType::where('place_management_type','Property Manager')->first();
            $property_managment = PlacesManagement::where(PLACE_ID,$place_id)
                                ->where(TYPE_ID,$property_place_management_type->id)
                                ->where('company_id',Auth::user()->company_id)
                                ->orderBy('id','desc')->pluck('id')->first();
            if($property_managment){
                $ids[] = ($owner_management)?$owner_management:'';   
                $ids[] = ($property_managment)?$property_managment:''; 
                $PlacesManagementResult = PlacesManagement::whereIn('id',$ids);
                return $this->response->paginator($PlacesManagementResult->paginate(2), new PlaceManagementTransformer());
            }
        }
        return $this->response->array([STATUS => 466, MESSAGE => 'This place needs an owner and property manager as default attached before creating winterization notice.']);
    }

    /**
     * Get Quote Print
     *
     * @param  mixed $request
     * @param  mixed $quote_id
     *
     * @return void
     */
    public function getQuotePrint(Request $request,$quote_id)
    {
        if(!empty($quote_id))
        {
            $quote = $this->quoteRepository->getQuotePrint($quote_id);
            return $this->response->array([STATUS => 200, 'data' => $quote]);
        }
        return $this->response->errorInternal('Please send quote id.');
    }

    /**
     * get Winterization Detail
     *
     * @return void
     */
    public function getWinterizationDetail(Request $request)
    {

        $winterization = AppSetting::where('company_id',Auth::user()->company_id);

        if(empty($winterization->count()))
        {
            $winterization = AppSetting::where('company_id',0)->first();
            $request_data = $request->all();
            $request_data['company_id'] = Auth::user()->company_id;
            $request_data['company_name'] = Auth::user()->name;
            $request_data['company_email'] = Auth::user()->email;
            $request_data['winterization'] = ($request->has('winterization')) && !empty($request->winterization) ? $request->winterization : $winterization->winterization;
            $request_data['term_and_condition'] = ($request->has('term_and_condition')) && !empty($request->term_and_condition) ? $request->term_and_condition : $winterization->term_and_condition;
            $request_data['timezone_id'] = ($request->has('timezone_id')) && !empty($request->timezone_id) ? $request->timezone_id : $winterization->timezone_id;
            $model=new AppSetting;
            $model->fill($request_data);
            $model->save();
            $winterization = AppSetting::where('company_id',Auth::user()->company_id);
        }
        $winterization=$winterization->first();

        return $this->response->item($winterization, new AppSettingTransformer());
    }

    /**
     * store Winterization
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function storeWinterization(Request $request)
    {
        $winterization = AppSetting::where('company_id',Auth::user()->company_id)->first();

        if($winterization->count())
        {   
            $winterization_detail = ($request->has('winterization')) && !empty($request->winterization) ? $request->winterization : $winterization->winterization;
            $term_and_condition = ($request->has('term_and_condition')) && !empty($request->term_and_condition) ? $request->term_and_condition : $winterization->term_and_condition;
            $timezone_id = ($request->has('timezone_id')) && !empty($request->timezone_id) ? $request->timezone_id : $winterization->timezone_id;
            AppSetting::where('company_id',Auth::user()->company_id)->update(['winterization'=>$winterization_detail,'term_and_condition'=>$term_and_condition,'timezone_id'=>$timezone_id]);
        }
        else
        {
            $winterization = AppSetting::where('company_id',0)->first();
            $request_data = $request->all();
            $request_data['company_id'] = Auth::user()->company_id;
            $request_data['company_name'] = Auth::user()->name;
            $request_data['company_email'] = Auth::user()->email;
            $request_data['winterization'] = ($request->has('winterization')) && !empty($request->winterization) ? $request->winterization : $winterization->winterization;
            $request_data['term_and_condition'] = ($request->has('term_and_condition')) && !empty($request->term_and_condition) ? $request->term_and_condition : $winterization->term_and_condition;
            $request_data['timezone_id'] = ($request->has('timezone_id')) && !empty($request->timezone_id) ? $request->timezone_id : $winterization->timezone_id;
            $model=new AppSetting;
            $model->fill($request_data);
            $model->save();
        }
        $winterization = AppSetting::where('company_id',Auth::user()->company_id)->first();
        return $this->response->item($winterization, new AppSettingTransformer());
    }

}

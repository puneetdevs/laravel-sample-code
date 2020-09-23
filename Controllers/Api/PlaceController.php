<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\InspectionController;
use App\Models\Place;
use App\Models\PlaceType;
use App\Models\StreetType;
use App\Models\Area;
use App\Models\PlacesManagement;
use App\Models\PlacesManagementType;
use App\Models\InspectionType;
use App\Models\FileType;
use App\Models\PlacesFile;
use App\Models\PlacesContact;
use App\Models\Quote;
use App\Models\PlacesInspection;
use App\Models\PlacesInspectionArea;
use App\Models\PlacesInspectionDevice;
use App\Models\Invoice;
use App\Transformers\PlaceTransformer;
use App\Transformers\PlaceManagementTransformer;
use App\Transformers\PlaceContactTransformer;
use App\Transformers\AreaTransformer;
use App\Transformers\InspectionTypeTransformer;
use App\Transformers\FileTypeTransformer;
use App\Transformers\PlaceFileTransformer;
use App\Transformers\QuoteTransformer;
use App\Transformers\PlacesInspectionTransformer;
use App\Transformers\PlacesInspectionAreaTransformer;
use App\Transformers\PlacesInspectionDeviceTransformer;
use App\Transformers\InvoiceTransformer;
use App\Http\Requests\Api\Places\Index;
use App\Http\Requests\Api\Places\Show;
use App\Http\Requests\Api\Places\Create;
use App\Http\Requests\Api\Places\Store;
use App\Http\Requests\Api\Places\Edit;
use App\Http\Requests\Api\Places\Update;
use App\Http\Requests\Api\Places\Destroy;
use App\Http\Requests\Api\Places\StoreManagement;
use App\Http\Requests\Api\Places\UpdatePlaceManagementStatus;
use App\Http\Requests\Api\Places\DeletePlaceManagement;
use App\Http\Requests\Api\Places\UploadPlaceFile;
use App\Http\Requests\Api\Places\DeletePlaceFile;
use App\Http\Requests\Api\Places\StorePlaceInspection;
use App\Http\Requests\Api\Places\UpdatePlaceInspection;
use App\Http\Requests\Api\Places\StorePlaceInspectionArea;
use App\Http\Requests\Api\Places\UpdatePlaceInspectionArea;
use App\Http\Requests\Api\Places\DeletePlaceInspectionArea;
use App\Http\Requests\Api\Places\StorePlaceInspectionDevice;
use App\Http\Requests\Api\Places\UpdatePlaceInspectionDevice;
use App\Http\Requests\Api\Places\DeletePlaceInspectionDevice;
use App\Http\Requests\Api\Places\DeleteDevice;
use App\Repositories\PlaceRepository;
use App\Repositories\InspectionRepository;
use Auth;
use App\Helpers\Helper;
use DB;

/**
 * Place
 *
 * @Resource("Place", uri="/places")
 */

class PlaceController extends ApiController
{
    /*Construct here define place repository */
    public function __construct(PlaceRepository $placeRepository){
      $this->placeRepository = $placeRepository;
    }

    /**
     * Get Place Listing
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

        #Search Filter Add here
        $place = $this->SearchPlaceFilter($request);
        
        $place->LeftJoin('street_types', 'places.street_type_id', '=', 'street_types.id')
                ->join('cities', 'places.city_id', '=', 'cities.id')
                ->select('places.*', DB::raw('(street_types.street_type) streettype,(cities.city) cityname'));
        
        return $this->response->paginator($place->orderBy($sortBy['column'], $sortBy['order'])->paginate($per_page), new PlaceTransformer());
    }

    /**
     * Search Filter For Place
     *
     * @param  mixed $request
     *
     * @return void
     */
    private function SearchPlaceFilter($request){
        $place = Place::select('*'); 
        if($request->has('name') && !empty($request->name)){    
            $place->where('places.name','like','%'.$request->name.'%');
        }
        if($request->has('number') && !empty($request->number)){    
            $place->where('places.street_number','like','%'.$request->number.'%');
        }
        if($request->has('streetname') && !empty($request->streetname)){    
            $place->where('places.street_name','like','%'.$request->streetname.'%');
        }
        if($request->has('streetid') && !empty($request->streetid)){    
            $place->where('places.street_type_id',$request->streetid);
        }
        if($request->has('city') && !empty($request->city)){    
            $place->where('places.city_id',$request->city);
        }
        if($request->has('suite') && !empty($request->suite)){    
            $place->where('places.suite','like','%'.$request->suite.'%');
        }
        if($request->has('placetype') && !empty($request->placetype)){    
            $place->where('places.place_type_id',$request->placetype);
        }
        if($request->has('alert') && !empty($request->alert)){ 
            $alert =  $request->alert == 'true' ? 1 : 0;  
            $place->where('places.alert',$alert);
        }
        if($request->has('hold') && !empty($request->hold)){ 
            $hold =  $request->hold == 'true' ? 1 : 0;  
            $place->where('places.on_hold',$hold);
        }
        if($request->has('active') && !empty($request->active)){  
            $active =  $request->active == 'true' ? [1] : [0,1];  
            $place->whereIn('places.active',$active);
        }else{
            $place->where('places.active',1);
        }
        return $place;
    }

    /**
     * show
     * Get Single Place Listing
     * @param  mixed $request
     * @param  mixed $place
     *
     * @return void
     */
    public function show(Request $request,  $place)
    {
        $place=Place::where('id',$place)->first();
        if($place){
            return $this->response->item($place, new PlaceTransformer());
        }
        return response()->json([MESSAGE=>'No matching records found.'], 404);
    }

    /**
     * Add Place Here
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function store(Store $request)
    {
        $request_data = $request->all();
        $request_data['company_id'] = Auth::user()->company_id;
        $model=new Place;
        $model->fill($request_data);
        if ($model->save()) {
            return $this->response->item($model, new PlaceTransformer());
        }
        return $this->response->errorInternal('Error occurred while saving place.');
    }
 
    /**
     * update Place Detail
     *
     * @param  mixed $request
     * @param  mixed $place
     *
     * @return void
     */
    public function update(Update $request,  Place $place)
    {
        $requested_data = $request->all();
        if($request->has('is_default') && $request->is_default === 1){
            Place::where('company_id',Auth::user()->company_id)->where('is_default',1)->update(['is_default'=>0]);
        }
        Place::where('id',$requested_data['id'])->update($requested_data);
        $place = Place::where('id',$requested_data['id'])->first();
        return $this->response->item($place, new PlaceTransformer());
    }

    /**
     * destroy
     *
     * @param  mixed $request
     * @param  mixed $place
     *
     * @return void
     */
    public function destroy(Destroy $request, $place)
    {
        $place = Place::findOrFail($place);

        if ($place->delete()) {
            return $this->response->array([STATUS => 200, MESSAGE => 'Place successfully deleted.']);
        }
            return $this->response->errorInternal('Error occurred while deleting place.');
        
    }

    /**
     * Get Place Type Listing
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function getPlaceType(Request $request)
    {
        $place=PlaceType::get()->toArray();
        if($place){
            return $this->response->array(['data' => $place]);
        }
        return $this->response->errorInternal('Place type not found. Please try again.');
    }

    /**
     * Get Street Type
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function getStreetType(Request $request)
    {
        $streettype=StreetType::get()->toArray();
        if($streettype){
            return $this->response->array(['data' => $streettype]);
        }
        return $this->response->errorInternal('Street type not found. Please try again.');
    }

    /**
     * Add Management in Place
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function storeManagement(StoreManagement $request)
    {
        $request_data = $request->all();
        foreach($request_data['type_id'] as $key=>$type){
            $data[$key]['place_id'] = $request_data['place_id'];
            $data[$key]['management_id'] = $request_data['management_id'];
            $data[$key]['company_id'] = Auth::user()->company_id;
            $data[$key]['type_id'] = $type;
            $data[$key]['is_default'] = 0;
            $data[$key]['created_at'] = date('Y-m-d H:i:s');
            $data[$key]['updated_at'] = date('Y-m-d H:i:s');
        }
        if (PlacesManagement::insert($data)) {
            return $this->response->array([STATUS => 200, MESSAGE => 'Management has been added.']);
        }
        return $this->response->errorInternal('Error occurred while saving place.');
        
    }

    /**
     * Get Place Management Type
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function getManagementType(Request $request)
    {
        $PlacesManagementType=PlacesManagementType::get()->toArray();
        if($PlacesManagementType){
            return $this->response->array(['data' => $PlacesManagementType]);
        }
        return $this->response->errorInternal('Place management type not found. Please try again.');
    }

    /**
     * Delete Place Management
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function deletePlaceManagement(DeletePlaceManagement $request)
    {  
        if(PlacesManagement::whereIn('id',$request->id)->update([DELETED_AT => date('Y-m-d H:i:s')])){
            return $this->response->array([STATUS => 200, MESSAGE => DELETED_SUCCESSFULLY]);
        }
        return $this->response->errorInternal(DELETE_ERROR_SHORT);
    }

    /**
     * Get Area Listing
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function getArea(Request $request)
    {
        #Set Per Page Record
        $per_page = Helper::setPerPage($request);

        return $this->response->paginator(Area::paginate($per_page), new AreaTransformer());
    }

    /**
     * Get Inspection Type
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function getInspectionType(Request $request)
    {
        #Set Per Page Record
        $per_page = Helper::setPerPage($request);
        
        return $this->response->paginator(InspectionType::paginate($per_page), new InspectionTypeTransformer());
    }

    
    public function getPlaceManagement(Request $request, $place_id)
    {
        #Set Sort by & Sort by Column
        $sortBy = Helper::setSortByValue($request);

        #Set Per Page Record
        $per_page = Helper::setPerPage($request);
        $PlacesManagement = PlacesManagement::where(PLACE_ID,$place_id)->where('places_management.company_id',Auth::user()->company_id);
        
        $PlacesManagement->join('management', 'places_management.management_id', '=', 'management.id')
                ->join('places_management_types', 'places_management.type_id', '=', 'places_management_types.id')
                ->select('places_management.*', DB::raw('(management.name) name,(places_management_types.place_management_type) managementtype'));

        return $this->response->paginator($PlacesManagement->orderBy($sortBy['column'], $sortBy['order'])->paginate($per_page), new PlaceManagementTransformer());
    }

    /**
     * Update Place Management Status
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function updatePlaceManagementStatus(UpdatePlaceManagementStatus $request)
    {
        $requested_data = $request->all();
        foreach($requested_data['data'] as $data){
            PlacesManagement::where('id',$data['id'])->update(['is_default' => $data['is_default']]);
        }
        return $this->response->array([STATUS => 200, MESSAGE => 'Place Management updated successfully.']);
    }

    /**
     * Get File Type
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function getFileType(Request $request)
    {
        #Set Per Page Record
        $per_page = Helper::setPerPage($request);

        return $this->response->paginator(FileType::paginate($per_page), new FileTypeTransformer());
    }

    /**
     * Upload Place File
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function uploadPlaceFile(UploadPlaceFile $request){
        if($placefile = $this->placeRepository->uploadDocumentFile($request, 'place')){
            return $this->response->item($placefile, new PlaceFileTransformer());
        }
        return $this->response->errorInternal('Error while upload file in place. Please try again.');
    }

    /**
     * Get Place Files List
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function getPlaceFiles(Request $request)
    {
        #Set Sort by & Sort by Column
        $sortBy = Helper::setSortByValue($request);

        #Set Per Page Record
        $per_page = Helper::setPerPage($request);

        if($request->has(PLACE_ID) && !empty($request->place_id)){
            #Start Place File Query
            $placefile = PlacesFile::where(COMPANY_ID,Auth::user()->company_id);
            $placefile->where(PLACE_ID, $request->place_id );

            $placefile->join('file_types', 'places_files.type_id', '=', 'file_types.id')
                ->select('places_files.*', DB::raw('(file_types.file_type) filetype'));

            return $this->response->paginator($placefile->orderBy($sortBy['column'], $sortBy['order'])->paginate($per_page), new PlaceFileTransformer());
        }
        return $this->response->errorInternal('Please send place_id.');
    }

    /**
     * Delete Place File(Multiple)
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function deletePlaceFile(DeletePlaceFile $request)
    {  
        if(PlacesFile::whereIn('id',$request->id)->update([DELETED_AT => date('Y-m-d H:i:s')])){
            return $this->response->array([STATUS => 200, MESSAGE => DELETED_SUCCESSFULLY]);
        }
        return $this->response->errorInternal(DELETE_ERROR_SHORT);
    }

    /**
     * get Contact In Place
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function getPlaceContact(Request $request)
    {
        #Set Sort by & Sort by Column
        $sortBy = Helper::setSortByValue($request);

        #Set Per Page Record
        $per_page = Helper::setPerPage($request);
        if($request->has(PLACE_ID) && !empty($request->place_id)){
            $placecontact = $this->placeRepository->getPlaceContact(PLACE_ID ,$request->place_id);
            
            $placecontact->join('contacts', 'places_contacts.contact_id', '=', 'contacts.id')
                ->join('contact_types', 'places_contacts.type_id', '=', 'contact_types.id')
                ->select('places_contacts.*', DB::raw('(contacts.name) contactname,(contact_types.contact_type) contacttype'));

            return $this->response->paginator($placecontact->orderBy($sortBy['column'], $sortBy['order'])->paginate($per_page), new PlaceContactTransformer());
        }
        return $this->response->errorInternal('Please send place_id.');
    }

    /**
     * Get Place Quote
     *
     * @param  mixed $request
     * @param  mixed $place_id
     *
     * @return void
     */
    public function getPlaceQuote(Request $request, $place_id)
    {
        #Set Sort by & Sort by Column
        $sortBy = Helper::setSortByValue($request);

        #Set Per Page Record
        $per_page = Helper::setPerPage($request);
        $quote = Quote::where('quotes.place_id',$place_id)->where('quotes.company_id',Auth::user()->company_id);

        $quote->join('places_management', 'quotes.bill_to_id', '=', 'places_management.id')
                ->join('management', 'places_management.management_id', '=', 'management.id')
                ->select('quotes.*', DB::raw('(management.name) billto'));

        return $this->response->paginator($quote->orderBy($sortBy['column'], $sortBy['order'])->paginate($per_page), new QuoteTransformer());
    }

    /**
     * Store Place Information In Device List
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function storePlaceInspection(StorePlaceInspection $request)
    {
        $model=new PlacesInspection;
        $model->fill($request->all());
        if ($model->save()) {
            return $this->response->item($model, new PlacesInspectionTransformer());
        }
        return $this->response->errorInternal(PLACE_INS_ERROR);
    }

    /**
     * Update Place Information In Device List
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function updatePlaceInspection(UpdatePlaceInspection $request)
    {
        $requested_data = $request->all();
        PlacesInspection::where('id',$requested_data['id'])->update($requested_data);
        return $this->response->array([STATUS => 200, MESSAGE => 'Successfully Updated.']);
    }

    /**
     * Get Single Place Information In Device List
     *
     * @param  mixed $request
     * @param  mixed $placeinspection_id
     *
     * @return void
     */
    public function getPlaceInspection(Request $request, $placeinspection_id)
    {
        $placeinspection=PlacesInspection::where('id',$placeinspection_id)->first();
        if($placeinspection){
            return $this->response->item($placeinspection, new PlacesInspectionTransformer());
        }
        return $this->response->errorInternal(NO_RECORD_MSG);
    }

    /**
     * Get Devices In Device Listing
     *
     * @param  mixed $request
     * @param  mixed $place_id
     *
     * @return void
     */
    public function getDevicePlace(Request $request, $place_id)
    {
        #Set Sort by & Sort by Column
        $sortBy = Helper::setSortByValue($request);

        #Set Per Page Record
        $per_page = Helper::setPerPage($request);
        $place_info = PlacesInspection::where(PLACE_ID,$place_id)->where(COMPANY_ID,Auth::user()->company_id);
        $place_info->join('inspection_types', 'places_inspections.inspection_type_id', '=', 'inspection_types.id')
                ->select('places_inspections.*', DB::raw('(inspection_types.inspection_type) typename'));
        
        return $this->response->paginator($place_info->orderBy($sortBy['column'], $sortBy['order'])->paginate($per_page), new PlacesInspectionTransformer());
    }

    /**
     * Delete Devices In Device Listing
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function deleteDevice(DeleteDevice $request)
    {  
        if(PlacesInspection::whereIn('id',$request->id)->update([DELETED_AT => date('Y-m-d H:i:s')])){
            return $this->response->array([STATUS => 200, MESSAGE => DELETED_SUCCESSFULLY]);
        }
        return $this->response->errorInternal(DELETE_ERROR_SHORT);
    }

    /**
     * Store Device Area
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function storePlaceInspectionArea(StorePlaceInspectionArea $request)
    {   
        $model=new PlacesInspectionArea;
        $model->fill($request->all());
        if ($model->save()) {
            return $this->response->array([STATUS => 200, MESSAGE => 'Area has been added successfully ']);
        }
        return $this->response->errorInternal(PLACE_INS_ERROR);
    }

    /**
     * Update Device Area
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function updatePlaceInspectionArea(UpdatePlaceInspectionArea $request)
    {
        $requested_data = $request->all();
        PlacesInspectionArea::where('id',$requested_data['id'])->update($requested_data);
        return $this->response->array([STATUS => 200, MESSAGE => 'Area has been updated.']);
    }

    /**
     * Delete Device Area
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function deletePlaceInspectionArea(DeletePlaceInspectionArea $request)
    {  
        if(PlacesInspectionArea::where('id',$request->id)->update([DELETED_AT => date('Y-m-d H:i:s')])){
            #Query to delete device related to the area
            PlacesInspectionDevice::where('area_id',$request->id)->update([DELETED_AT => date('Y-m-d H:i:s')]);
            return $this->response->array([STATUS => 200, MESSAGE => 'Area has been removed successfully.']);
        }
        return $this->response->errorInternal(DELETE_ERROR_SHORT);
    }

    /**
     * Get Device Area
     *
     * @param  mixed $request
     * @param  mixed $placeinspection_id
     *
     * @return void
     */
    public function getPlaceInspectionArea(Request $request, $placeinspection_id)
    {
        #Set Sort by & Sort by Column
        $sortBy = Helper::setSortByValue($request);

        #Set Per Page Record
        $per_page = Helper::setPerPage($request);
        $place_area = PlacesInspectionArea::where('places_inspection_id',$placeinspection_id)->where(COMPANY_ID,Auth::user()->company_id);
        return $this->response->paginator($place_area->orderBy($sortBy['column'], $sortBy['order'])->paginate($per_page), new PlacesInspectionAreaTransformer());
    }

    /**
     * Get Device Area Detail
     *
     * @param  mixed $request
     * @param  mixed $placeinspectionarea_id
     *
     * @return void
     */
    public function getPlaceInspectionAreaDetail(Request $request, $placeinspectionarea_id)
    {
        $inspectionarea=PlacesInspectionArea::where('id',$placeinspectionarea_id)->first();
        if($inspectionarea){
            return $this->response->item($inspectionarea, new PlacesInspectionAreaTransformer());
        }
        return $this->response->errorInternal(NO_RECORD_MSG);
    }

    /**
     * Store Device In Device List
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function storePlaceInspectionDevice(StorePlaceInspectionDevice $request)
    {
        $model=new PlacesInspectionDevice;
        $model->fill($request->all());
        if ($model->save()) {
            
            $inpectionCont = new InspectionController(new InspectionRepository);
            $default_form = $inpectionCont->saveDeviceFormDetail($request,$request->device_type_id);
             if(!empty($default_form)){
                $details =  serialize($default_form['data']); 
                PlacesInspectionDevice::where('id',$model->id)->update(['details'=> ''.$details.'', 'form_id' => $default_form['form_id']]);
            }
            return $this->response->array([STATUS => 200, MESSAGE => 'Device has been added succesfully.']);
        }
        return $this->response->errorInternal(PLACE_INS_ERROR);
    }

    /**
     * Update Device In Device List
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function updatePlaceInspectionDevice(UpdatePlaceInspectionDevice $request)
    {
        $requested_data = $request->all();
        PlacesInspectionDevice::where('id',$requested_data['id'])->update($requested_data);
        return $this->response->array([STATUS => 200, MESSAGE => 'Device details has been updated.']);
    }

    /**
     * Delete Device In Device List
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function deletePlaceInspectionDevice(DeletePlaceInspectionDevice $request)
    {  
        if(PlacesInspectionDevice::where('id',$request->id)->update([DELETED_AT => date('Y-m-d H:i:s')])){
            return $this->response->array([STATUS => 200, MESSAGE => 'Device has been removed successfully.']);
        }
        return $this->response->errorInternal(DELETE_ERROR_SHORT);
    }

    /**
     * Get Device In Device List
     *
     * @param  mixed $request
     * @param  mixed $placeinspection_id
     *
     * @return void
     */
    public function getPlaceInspectionDevice(Request $request, $placeinspection_id)
    {
        #Set Per Page Record
        $per_page = Helper::setPerPage($request);

        #Set Sort by & Sort by Column
        $sortBy = Helper::setSortByValue($request);

        $device_info = PlacesInspectionDevice::where('places_inspection_devices.places_inspection_id',$placeinspection_id)->where('places_inspection_devices.company_id',Auth::user()->company_id);
        
        #Search Filter For Area
        if( $request->has('area') && !empty($request->area) ){
            $device_info->where('area_id',$request->area);
        }

        #Search Filter For Location
        if( $request->has('location') && !empty($request->location) ){
            $device_info->where('location','like','%'.$request->location.'%');
        }

        #Search Filter For Identifier
        if( $request->has('identifier') && !empty($request->identifier) ){
            $device_info->where('identifier','like','%'.$request->identifier.'%');
        }

        #Search Filter For Device
        if( $request->has('device') && !empty($request->device) ){
            $device_info->where('device_type_id',$request->device);
        }

        $device_info->join('places_inspection_areas', 'places_inspection_devices.area_id', '=', 'places_inspection_areas.id')
                ->join('device_types', 'places_inspection_devices.device_type_id', '=', 'device_types.id')
                ->select('places_inspection_devices.*', DB::raw('(places_inspection_areas.area) areaname,(device_types.device_type) devicetypename'));
        
        return $this->response->paginator($device_info->orderBy($sortBy['column'], $sortBy['order'])->paginate($per_page), new PlacesInspectionDeviceTransformer());
    }

    /**
     * Get Device Detail In Device List
     *
     * @param  mixed $request
     * @param  mixed $placeinspectiondevice_id
     *
     * @return void
     */
    public function getPlaceInspectionDeviceDetail(Request $request, $placeinspectiondevice_id)
    {
        $placeinspectiondevice=PlacesInspectionDevice::where('id',$placeinspectiondevice_id)->first();
        if($placeinspectiondevice){
            return $this->response->item($placeinspectiondevice, new PlacesInspectionDeviceTransformer());
        }
        return $this->response->errorInternal(NO_RECORD_MSG);
    }

    /**
     * get Place Invoices
     *
     * @param  mixed $request
     * @param  mixed $place_id
     *
     * @return void
     */
    public function getPlaceInvoices(Request $request, $place_id)
    {
        #Set Sort by & Sort by Column
        $sortBy = Helper::setSortByValue($request);

        #Set Per Page Record
        $per_page = Helper::setPerPage($request);
        $invoice = Invoice::where('invoices.place_id',$place_id)->where('invoices.company_id',Auth::user()->company_id);

        $invoice->join('places_management', 'invoices.bill_to_id', '=', 'places_management.id')
                ->join('management', 'places_management.management_id', '=', 'management.id')
                ->select('invoices.*', DB::raw('(management.name) managementname'));

        return $this->response->paginator($invoice->orderBy($sortBy['column'], $sortBy['order'])->paginate($per_page), new InvoiceTransformer());
    }

}

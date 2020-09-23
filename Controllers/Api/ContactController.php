<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\ApiController;
use App\Models\Contact;
use App\Models\ContactType;
use App\Models\PlacesContact;
use App\Models\ManagementContact;
use App\Transformers\ContactTransformer;
use App\Transformers\ContactTypeTransformer;
use App\Transformers\PlaceContactTransformer;
use App\Transformers\ManagementContactTransformer;
use App\Http\Requests\Api\Contacts\Index;
use App\Http\Requests\Api\Contacts\Show;
use App\Http\Requests\Api\Contacts\Create;
use App\Http\Requests\Api\Contacts\Store;
use App\Http\Requests\Api\Contacts\Edit;
use App\Http\Requests\Api\Contacts\Update;
use App\Http\Requests\Api\Contacts\Destroy;
use App\Http\Requests\Api\Contacts\StorePlaceContact;
use App\Http\Requests\Api\Contacts\StoreManagementContact;
use App\Http\Requests\Api\Contacts\UpdatePlaceContact;
use App\Http\Requests\Api\Contacts\UpdateManagementContact;
use App\Http\Requests\Api\Contacts\DeletePlaceContact;
use App\Http\Requests\Api\Contacts\DeleteManagementContact;
use App\Repositories\PlaceRepository;
use App\Repositories\ManagementRepository;
use Auth;
use App\Helpers\Helper;
use DB;

/**
 * Contact
 *
 * @Resource("Contact", uri="/contacts")
 */

class ContactController extends ApiController
{
    /*Construct here define place repository */
    public function __construct(PlaceRepository $placeRepository,ManagementRepository $managementRepository){
        $this->placeRepository = $placeRepository;
        $this->managementRepository = $managementRepository;
      }
      
    /**
     * Get Contact Listing
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

        #Start City Query
        $contact = Contact::where(COMPANY_ID,Auth::user()->company_id);
        #Add check active/inactive
        if($request->has(ACTIVE) && !empty($request->active)){
            $active =  $request->active == 'true' ? [1] : [0,1];  
            $contact->whereIn(ACTIVE,$active);
        }else{
            $contact->where(ACTIVE,1);
        }
        
        $columns_search = ['contacts.name','contacts.phone'];
        if($request->has('q') && !empty($request->q)){
            $contact->where(function ($query) use($columns_search, $request) {
                foreach($columns_search as $column) {
                    $query->orWhere($column, 'LIKE', '%' . $request->q . '%');
                }
            });
        }
        //dd($sortBy);
        return $this->response->paginator($contact->orderBy($sortBy['column'], $sortBy['order'])->paginate($per_page), new ContactTransformer());
    }

    /**
     * Get Single Contact Detail
     *
     * @param  mixed $request
     * @param  mixed $contact
     *
     * @return void
     */
    public function show(Show $request, $contact)
    {
        $contact = Contact::where('id',$contact)->first();
        if($contact){
            return $this->response->item($contact, new ContactTransformer());
        }
        return response()->json([MESSAGE=>'No matching records found.'], 404);
    }

    /**
     * Store Contact
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function store(Store $request)
    {
        $requested_data = $request->all();
        $requested_data['company_id'] = Auth::user()->company_id;
        $model=new Contact;
        $model->fill($requested_data);
        if ($model->save()) {
            return $this->response->item($model, new ContactTransformer());
        } else {
              return $this->response->errorInternal('Error occurred while saving contact.');
        }
    }
 
    /**
     * Update Contact
     *
     * @param  mixed $request
     * @param  mixed $contact
     *
     * @return void
     */
    public function update(Update $request, $contact)
    {
        $requested_data = $request->all();
        Contact::where('id',$requested_data['id'])->update($requested_data);
        $contact = Contact::where('id',$requested_data['id'])->first();
        return $this->response->item($contact, new ContactTransformer());
    }

    public function destroy(Destroy $request, $contact)
    {
        
    }

    /**
     * Get Contact Type
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function getContactType(Request $request)
    {
        #Set Per Page Record
        $per_page = Helper::setPerPage($request);
        
        return $this->response->paginator(ContactType::paginate($per_page), new ContactTypeTransformer());
    }

    
    /**
     * Store Place Contact
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function storePlaceContact(StorePlaceContact $request)
    {
        $request_data = $request->all();
        foreach($request_data['type_id'] as $key => $type){
            $data[$key]['place_id'] = $request_data['place_id'];
            $data[$key]['contact_id'] = $request_data['contact_id'];
            $data[$key]['company_id'] = Auth::user()->company_id;
            $data[$key]['type_id'] = $type;
            $data[$key]['is_default'] = 0;
            $data[$key]['created_at'] = date('Y-m-d H:i:s');
            $data[$key]['updated_at'] = date('Y-m-d H:i:s');
        }
        if (PlacesContact::insert($data)) {
            return $this->response->array([STATUS => 200, MESSAGE => 'Contact has been added.']);
        } else {
              return $this->response->errorInternal('Error occurred while saving place contact.');
        }
    }

    /**
     * Update Place Contact
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function updatePlaceContact(UpdatePlaceContact $request)
    {
        $requested_data = $request->all();
        foreach($requested_data['data'] as $data){
            PlacesContact::where('id',$data['id'])->update(['is_default' => $data['is_default']]);
        }
        return $this->response->array([STATUS => 200, MESSAGE => 'Place contact updated successfully.']);
       
    }

    /**
     * Delete Place Contact
     *
     * @param  mixed $request
     * @param  mixed $id
     *
     * @return void
     */
    public function deletePlaceContact(DeletePlaceContact $request)
    {       
        if(PlacesContact::whereIn('id',$request->id)->update(['deleted_at' => date('Y-m-d H:i:s')])){
            return $this->response->array([STATUS => 200, MESSAGE => 'Successfully deleted.']);
        }
        return $this->response->errorInternal('Error while delete action perform. Please try again.');
        
    }

    
    /**
     * Store Management Contact
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function storeManagementContact(StoreManagementContact $request)
    {
        $request_data = $request->all();
        foreach($request_data['type_id'] as $key => $type){
            $data[$key]['management_id'] = $request_data['management_id'];
            $data[$key]['contact_id'] = $request_data['contact_id'];
            $data[$key]['company_id'] = Auth::user()->company_id;
            $data[$key]['type_id'] = $type;
            $data[$key]['is_default'] = 0;
            $data[$key]['created_at'] = date('Y-m-d H:i:s');
            $data[$key]['updated_at'] = date('Y-m-d H:i:s');
        }
        if (ManagementContact::insert($data)) {
            return $this->response->array([STATUS => 200, MESSAGE => 'Place contact added successfully.']);
        } else {
              return $this->response->errorInternal('Error occurred while saving place contact.');
        }
    }

    /**
     * Update Management Contact
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function updateManagementContact(UpdateManagementContact $request)
    {

        $requested_data = $request->all();
        foreach($requested_data['data'] as $data){
            ManagementContact::where('id',$data['id'])->update(['is_default' => $data['is_default']]);
        }
        return $this->response->array([STATUS => 200, MESSAGE => 'Management contact updated successfully.']);
    }
  
    /**
     * Delete Management Contact
     *
     * @param  mixed $request
     * @param  mixed $id
     *
     * @return void
     */
    public function deleteManagementContact(DeleteManagementContact $request)
    {   
        if(ManagementContact::whereIn('id',$request->id)->update(['deleted_at' => date('Y-m-d H:i:s')])){
            return $this->response->array([STATUS => 200, MESSAGE => 'Successfully deleted.']);
        }
        return $this->response->errorInternal('Error while delete action perform. Please try again.');
    }

    /**
     * get Place In Contact
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function getContactPlace(Request $request)
    {
        #Set Sort by & Sort by Column
        $sortBy = Helper::setSortByValue($request);

        #Set Per Page Record
        $per_page = Helper::setPerPage($request);
        if($request->has('contact_id') && !empty($request->contact_id)){
            $placecontact = $this->placeRepository->getPlaceContact('contact_id' ,$request->contact_id);

            $placecontact->join('places', 'places_contacts.place_id', '=', 'places.id')
                ->join('contact_types', 'places_contacts.type_id', '=', 'contact_types.id')
                ->select('places_contacts.*', DB::raw('(places.name) placename,(contact_types.contact_type) contacttype'));


            return $this->response->paginator($placecontact->orderBy($sortBy['column'], $sortBy['order'])->paginate($per_page), new PlaceContactTransformer());
        }
        return $this->response->errorInternal('Please send contact_id.');
    }

    /**
     * get Management In Contact
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function getContactManagement(Request $request)
    {
        #Set Sort by & Sort by Column
        $sortBy = Helper::setSortByValue($request);

        #Set Per Page Record
        $per_page = Helper::setPerPage($request);
        if($request->has('contact_id') && !empty($request->contact_id)){
            $contactmanagement = $this->managementRepository->getManagementContact('contact_id' ,$request->contact_id);
            
            $contactmanagement->join('management', 'management_contacts.management_id', '=', 'management.id')
                ->join('contact_types', 'management_contacts.type_id', '=', 'contact_types.id')
                ->select('management_contacts.*', DB::raw('(management.name) managementname,(contact_types.contact_type) contacttype'));
            
            return $this->response->paginator($contactmanagement->orderBy($sortBy['column'], $sortBy['order'])->paginate($per_page), new ManagementContactTransformer());
        }
        return $this->response->errorInternal('Please send contact_id.');
    }

}

<?php

namespace App\Repositories;


use DB;
use App\Exceptions\Handler;
use App\Repositories\BaseRepository;
use App\Models\Place;
use App\Models\File;
use App\Models\PlacesFile;
use App\Models\PlacesContact;
use App\Models\ManagementFile;
use DateTime;
use Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
/**
 * Class PlaceRepository.
 */
class PlaceRepository extends BaseRepository
{
    /**
     * @return string
     */
    public function model()
    {
        return Place::class;
    }

    /**
     * Upload Document File
     *
     * @param  mixed $request
     * @param  mixed $type
     *
     * @return void
     */
    public function uploadDocumentFile($request, $type){
        #File path set here
        $file = $request->file('file');
        $destinationPath = 'public/document/';
        #Uploade File Here
        $file_orignal_name = $file->getClientOriginalName();
        $ext = $file->getClientOriginalExtension();
        $file_name = str_replace('.'.$ext,"", $file_orignal_name ).time().'.'.$ext;
        $file_name = str_replace(' ', '-', $file_name);
        $uploaded = Storage::put($destinationPath.$file_name, (string) file_get_contents($file), 'public');
        #Save File in Files Table
        if($uploaded) {
            $file_path = 'app/'.$destinationPath.$file_name;
            $request_data['file_name'] = $file_orignal_name;
            $request_data['path'] = $file_path;
            $request_data['file_type'] = 'document';
            $request_data['object_type'] = $type == 'place' ? 'place_document' : 'management_document' ;
            $request_data['object_id'] = $type == 'place' ? $request->place_id : $request->management_id ;
            $request_data['upload_by'] = Auth::user()->id;
            if($image = File::create($request_data)){
                #Save File Data in place file table
                if($type == 'place'){
                    $data['company_id'] = Auth::user()->company_id;
                    $data['place_id'] = $request->place_id;
                    $data['type_id'] = $request->type_id;
                    $data['created_date'] = date('Y-m-d H:i:s');
                    $data['ext'] = $ext;
                    $data['description'] = ($request->has('description')) ? $request->description : '';
                    $data['file_id'] = $image->id;
                    if($place_file = PlacesFile::create($data)){
                        return PlacesFile::where('id',$place_file->id)->first();
                    }
                    
                #Save File Data in management file table
                }else{
                    $data['company_id'] = Auth::user()->company_id;
                    $data['management_id'] = $request->management_id;
                    $data['type_id'] = $request->type_id;
                    $data['created_date'] = date('Y-m-d H:i:s');
                    $data['ext'] = $ext;
                    $data['description'] = ($request->has('description')) ? $request->description : '';
                    $data['file_id'] = $image->id;
                    if($management_file = ManagementFile::create($data)){
                        return ManagementFile::where('id',$management_file->id)->first();
                    }
                }
            }
        }
        return false;
    }

    /**
     * get Place Contact
     *
     * @param  mixed $field
     * @param  mixed $place_id
     *
     * @return void
     */
    public function getPlaceContact($field,$place_id)
    {
        return PlacesContact::where($field, $place_id );
    }
}

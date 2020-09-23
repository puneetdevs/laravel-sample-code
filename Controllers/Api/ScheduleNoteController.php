<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\ApiController;
use App\Models\ScheduleNote;
use App\Transformers\ScheduleNoteTransformer;
use App\Http\Requests\Api\ScheduleNotes\Index;
use App\Http\Requests\Api\ScheduleNotes\Show;
use App\Http\Requests\Api\ScheduleNotes\Create;
use App\Http\Requests\Api\ScheduleNotes\Store;
use App\Http\Requests\Api\ScheduleNotes\Edit;
use App\Http\Requests\Api\ScheduleNotes\Update;
use App\Http\Requests\Api\ScheduleNotes\Destroy;


/**
 * ScheduleNote
 *
 * @Resource("ScheduleNote", uri="/schedule_notes")
 */

class ScheduleNoteController extends ApiController
{
    
    /**
     * Get Schedule Notes Listing
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function index(Index $request)
    {
       return $this->response->paginator(ScheduleNote::paginate(10), new ScheduleNoteTransformer());
    }

    /**
     * Get Single Schedule Note Detail
     *
     * @param  mixed $request
     * @param  mixed $schedulenote
     *
     * @return void
     */
    public function show(Show $request, $schedulenote)
    {
        $schedulenote = ScheduleNote::where('id',$quote)->first();
        if($schedulenote){
            return $this->response->item($schedulenote, new ScheduleNoteTransformer());  
        }
        return response()->json([MESSAGE=>'No matching records found.'], 404);
    }

    /**
     * store Schedule Note
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function store(Store $request)
    {
        $stafflist=implode(",",$request->staff_list);
        $requested_data = $request->all();
        $requested_data['staff_list'] = $stafflist;
        $model=new ScheduleNote;
        $model->fill($requested_data);
        if ($model->save()) {
            return $this->response->item($model, new ScheduleNoteTransformer());
        }
        return $this->response->errorInternal('Error occurred while saving schedule note.');
    }
 
    /**
     * update Schedule Note Details
     *
     * @param  mixed $request
     * @param  mixed $schedulenote
     *
     * @return void
     */
    public function update(Update $request,  ScheduleNote $schedulenote)
    {
        $stafflist=implode(",",$request->staff_list);
        $requested_data = $request->all();
        $requested_data['staff_list'] = $stafflist;
        
        ScheduleNote::where('id',$requested_data['id'])->update($requested_data);
        $note = ScheduleNote::where('id',$requested_data['id'])->first();
        return $this->response->item($note, new ScheduleNoteTransformer());
    }

    public function destroy(Destroy $request, $schedulenote)
    {
        if(ScheduleNote::where('id',$schedulenote)->update(['deleted_at' => date('Y-m-d H:i:s')])){
            return $this->response->array(['status' => 200, 'message' => 'Successfully deleted.']);
        }
        return $this->response->errorInternal('Error while delete action perform. Please try again.');
    }

}

<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\ApiController;
use App\Models\GlAccount;
use App\Transformers\GlAccountTransformer;
use App\Http\Requests\Api\GlAccounts\Index;
use App\Http\Requests\Api\GlAccounts\Show;
use App\Http\Requests\Api\GlAccounts\Create;
use App\Http\Requests\Api\GlAccounts\Store;
use App\Http\Requests\Api\GlAccounts\Edit;
use App\Http\Requests\Api\GlAccounts\Update;
use App\Http\Requests\Api\GlAccounts\Destroy;
use Auth;
use App\Helpers\Helper;

/**
 * GlAccount
 *
 * @Resource("GlAccount", uri="/gl_accounts")
 */

class GlAccountController extends ApiController
{
    
    /**
     * Get GI Account with Search
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

        $Gl_Account = GlAccount::select('*');
        $columns_search = ['gl_number','description','type'];
        if($request->has('q')){
            $Gl_Account->where(function ($query) use($columns_search, $request) {
                foreach($columns_search as $column) {
                    $query->orWhere($column, 'LIKE', '%' . $request->q . '%');
                }
            });
        }

        return $this->response->paginator($Gl_Account->orderBy($sortBy['column'], $sortBy['order'])->paginate($per_page), new GlAccountTransformer());
    }

    /**
     * Get Single GI Account
     *
     * @param  mixed $request
     * @param  mixed $glaccount
     *
     * @return void
     */
    public function show(Show $request, $glaccount)
    {
        $glaccount = GlAccount::where('id',$glaccount)->first();
        if($glaccount){
            return $this->response->item($glaccount, new GlAccountTransformer());
        }
        return response()->json([MESSAGE=>'No matching records found.'], 404);
    }

    /**
     * Create GI Account
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function store(Store $request)
    {
        $requested_data = $request->all();
        $requested_data['company_id'] = Auth::user()->company_id;
        $model=new GlAccount;
        $model->fill($requested_data);
        if ($model->save()) {
            return $this->response->item($model, new GlAccountTransformer());
        } else {
              return $this->response->errorInternal('Error occurred while saving GL account.');
        }
    }
 
    /**
     * Update GI Account
     *
     * @param  mixed $request
     * @param  mixed $glaccount
     *
     * @return void
     */
    public function update(Update $request, $glaccount)
    {
        $requested_data = $request->all();
        GlAccount::where('id',$requested_data['id'])->update($requested_data);
        $glaccount = GlAccount::where('id',$requested_data['id'])->first();
        return $this->response->item($glaccount, new GlAccountTransformer());
    }

    public function destroy(Destroy $request, $glaccount)
    {
        
    }

}

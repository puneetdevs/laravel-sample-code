<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\ApiController;
use App\Models\Vendor;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Transformers\VendorTransformer;
use App\Transformers\PurchaseOrderTransformer;
use App\Transformers\PurchaseOrderItemTransformer;
use App\Http\Requests\Api\Vendors\Index;
use App\Http\Requests\Api\Vendors\Show;
use App\Http\Requests\Api\Vendors\Create;
use App\Http\Requests\Api\Vendors\Store;
use App\Http\Requests\Api\Vendors\Edit;
use App\Http\Requests\Api\Vendors\Update;
use App\Http\Requests\Api\Vendors\Destroy;
use App\Http\Requests\Api\Vendors\StorePurchaseOrder;
use App\Http\Requests\Api\Vendors\StorePurchaseOrderItem;
use App\Http\Requests\Api\Vendors\DeletePurchaseOrderItem;
use Auth;
use App\Helpers\Helper;
use Illuminate\Support\Facades\Config;
use DB;

/**
 * Vendor
 *
 * @Resource("Vendor", uri="/vendors")
 */

class VendorController extends ApiController
{
    
    /**
     * Vendor Listing
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
        $vendor = Vendor::select('*');

        $columns_search = ['vendors.name','vendors.phone'];
        if($request->has('q') && !empty($request->q)){
            $vendor->where(function ($query) use($columns_search, $request) {
                foreach($columns_search as $column) {
                    $query->orWhere($column, 'LIKE', '%' . $request->q . '%');
                }
            });
        } 

        return $this->response->paginator($vendor->orderBy($sortBy['column'], $sortBy['order'])->paginate($per_page), new VendorTransformer());
    }

    /**
     * Vendor Detail
     *
     * @param  mixed $request
     * @param  mixed $vendor
     *
     * @return void
     */
    public function show(Show $request, $vendor)
    {
        $vendor = Vendor::where('id',$vendor)->first();
        if($vendor){
            return $this->response->item($vendor, new VendorTransformer());  
        }
        return response()->json([MESSAGE=>'No matching records found.'], 404);
    }

    /**
     * Create Vendor
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function store(Store $request)
    {
        $model=new Vendor;
        $model->fill($request->all());
        if ($model->save()) {
            return $this->response->item($model, new VendorTransformer());
        } else {
              return $this->response->errorInternal('Error occurred while saving vendor.');
        }
    }
 
    /**
     * Update Vendor
     *
     * @param  mixed $request
     * @param  mixed $vendor
     *
     * @return void
     */
    public function update(Update $request,  Vendor $vendor)
    {
        $requested_data = $request->all();
        Vendor::where('id',$requested_data['id'])->update($requested_data);
        $vendor = Vendor::where('id',$requested_data['id'])->first();
        return $this->response->item($vendor, new VendorTransformer());
    }

    /**
     * destroy
     *
     * @param  mixed $request
     * @param  mixed $vendor
     *
     * @return void
     */
    public function destroy(Destroy $request, $vendor)
    {
        
    }
    
    /**
     * Store Purchase Order
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function storePurchaseOrder(StorePurchaseOrder $request)
    {
        $model=new PurchaseOrder;
        $requested_data = $request->all();
        $requested_data['company_id'] = Auth::user()->company_id;
        $requested_data['date'] = date('Y-m-d');
        $requested_data['total'] = 0.00;
        $requested_data['subtotal'] = 0.00;
        $requested_data['pst'] = 0.00;
        $requested_data['gst'] = 0.00;
        $requested_data['override_gst'] = 0.00;
        $requested_data['override_pst'] = 0.00;
        $requested_data['created_by_id'] = Auth::user()->id;
        $model->fill($requested_data);
        if ($model->save()) {
            #Set Number here
            $purchase_number = PurchaseOrder::where('company_id',Auth::user()->company_id)->withTrashed()->count();
            $number= Helper::setNumberCompanyWise($purchase_number,'purchase_orders'); 
            PurchaseOrder::where('id',$model->id)->update(['number' => $number]);
            $model->number=$number;
            return $this->response->item($model, new PurchaseOrderTransformer());
        } else {
            return $this->response->errorInternal('Error occurred while saving purchase order.');
        }
    }

    /**
     * Store PurchaseOrder Item
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function storePurchaseOrderItem(StorePurchaseOrderItem $request)
    {
        if($request->has('purchase_item') && !empty($request->purchase_item)){
            $data = array();
            foreach($request->purchase_item as $key=>$value){
                if(isset($value['id']) && !empty($value['id'])){
                    PurchaseOrderItem::where('id',$value['id'])->update($value);
                }else{
                    $value['purchase_order_id'] = $request->purchase_order_id;
                    $value['company_id'] = Auth::user()->company_id;
                    $value['created_at'] = date('Y-m-d H:i:s');
                    $value['created_at'] = date('Y-m-d H:i:s');
                    $data[$key] = $value;
                }
            }
            if(!empty($data)){
                PurchaseOrderItem::insert($data);
            }
        }

        #Update Purchase Order data
        $requested_data['id'] = $request->purchase_order_id;
        $requested_data['vendor_id'] = $request->vendor_id;
        $requested_data['date'] = $request->date;
        $requested_data['notes'] = $request->notes;
        $requested_data['subtotal'] = $request->subtotal;
        $requested_data['total'] = $request->total;
        $requested_data['pst'] = $request->pst;
        $requested_data['gst'] = $request->gst;
        $requested_data['posted'] = $request->posted;
        $requested_data['posted_date'] = $request->posted == '1'  ? date('Y-m-d H:i:s') : NULL;
        $requested_data['override_gst'] = $request->override_gst;
        $requested_data['override_pst'] = $request->override_pst;
        $requested_data['updated_at'] = date('Y-m-d H:i:s');
        PurchaseOrder::where('id',$requested_data['id'])->update($requested_data);
        return $this->response->array(['status' => 200, 'message' => 'Added Successfully']);
        
    }

    /**
     * Get PurchaseOrder Item
     *
     * @param  mixed $request
     * @param  mixed $purchase_order_id
     *
     * @return void
     */
    public function getPurchaseOrderItem(Request $request, $purchase_order_id)
    {
        #Set Per Page Record
        $per_page = Helper::setPerPage($request);
        $purchaseorder = PurchaseOrder::where('id',$purchase_order_id)->where('company_id',Auth::user()->company_id);
        
        return $this->response->paginator($purchaseorder->paginate($per_page), new PurchaseOrderTransformer());
    }
       
    /**
     * Delete PurchaseOrder Item
     *
     * @param  mixed $request
     *
     * @return array
     */
    public function deletePurchaseOrderItem(DeletePurchaseOrderItem $request)
    {  
        if($item=PurchaseOrderItem::whereIn('id',$request->id)->update(['deleted_at' => date('Y-m-d H:i:s')])){
            $purchaseitem = PurchaseOrderItem::where('id',$item)->withTrashed()->first();

            $requested_data['subtotal'] = $request->subtotal;
            $requested_data['total'] = $request->total;
            $requested_data['pst'] = $request->pst;
            $requested_data['gst'] = $request->gst;
            $requested_data['override_gst'] = $request->override_gst;
            $requested_data['override_pst'] = $request->override_pst;
            $requested_data['updated_at'] = date('Y-m-d H:i:s');
            PurchaseOrder::where('id',$purchaseitem->purchase_order_id)->update($requested_data);
            return $this->response->array(['status' => 200, 'message' => 'Successfully deleted.']);
        }
        return $this->response->errorInternal('Error while delete action perform. Please try again.');
    }

    /**
     * Get PurchaseOrder
     *
     * @param  mixed $request
     * @param  mixed $vendor_id
     *
     * @return void
     */
    public function getPurchaseOrder(Request $request, $vendor_id)
    {
        #Set Sort by & Sort by Column
        $sortBy = Helper::setSortByValue($request);

        #Set Per Page Record
        $per_page = Helper::setPerPage($request);

        $purchaseorder = PurchaseOrder::where(VENDOR_ID,$vendor_id)->where('company_id',Auth::user()->company_id);
        return $this->response->paginator($purchaseorder->orderBy($sortBy['column'], $sortBy['order'])->paginate($per_page), new PurchaseOrderTransformer());
    }

    /**
     * get Purchase Order List
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function getPurchaseOrderList(Request $request)
    {
        //Set Sort by & Sort by Column
        $sortBy = Helper::setSortByValue($request);
        
        //per page set here
        $per_page = Helper::setPerPage($request);
        
        //Start PurchaseOrder Query
        $purchaseorder = PurchaseOrder::select('*');

        //Search Filter For PurchaseOrder Number
        if($request->has('number') && !empty($request->number)){

            $number=$request->number;
            $count=strlen($number);
            //Add dash in the invoice no
            if( $count>3 && (substr($number, 4, 1))!='-' )
            {
                $number=substr_replace( $number,'-', 4, 0 );
            }

            $purchaseorder->where('number','like','%'.$number.'%');
        }

        //Search Filter For Vendor
        if($request->has('vendor') && !empty($request->vendor)){
            $purchaseorder->where(VENDOR_ID,$request->vendor);
        }

        //Search Filter For Vendor
        if($request->has('workorder') && !empty($request->workorder)){

            $purchaseorder->orWhereHas('purchaseitem',function($q) use($request){
                $q->where('workorder_id',$request->workorder);
            });
        }
        $purchaseorder->join('vendors', 'purchase_orders.vendor_id', '=', 'vendors.id')
                ->select('purchase_orders.*', DB::raw('(vendors.name) vendorname'));

        return $this->response->paginator($purchaseorder->orderBy($sortBy['column'], $sortBy['order'])->paginate($per_page), new PurchaseOrderTransformer());
    }

}

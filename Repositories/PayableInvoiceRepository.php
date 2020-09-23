<?php

namespace App\Repositories;


use DB;
use App\Exceptions\Handler;
use App\Models\File;
use App\Repositories\BaseRepository;
use App\Models\PayableInvoice;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PayableInvoiceItem;
use App\Models\PayableInvoiceFile;
use Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Class PayableInvoiceRepository.
 */
class PayableInvoiceRepository extends BaseRepository
{
    /**
     * @return string
     */
    public function model()
    {
        return PayableInvoice::class;
    }

    /**
     * Create Payable Invoice From Purchase Order
     *
     * @param  mixed $requested_data
     *
     * @return void
     */
    public function createPurchaseOrderPayable($requested_data)
    {
        $payable_check = PayableInvoice::where('purchase_order_id',$requested_data['purchase_order_id'])->first();

        if(!$payable_check)
        {
            $purchase_order = PurchaseOrder::where('id',$requested_data['purchase_order_id'])->first();
            $model=new PayableInvoice;
            $requested_data['date'] = date('Y-m-d');
            $requested_data['company_id'] = Auth::user()->company_id;
            $requested_data['number'] = $purchase_order->number;
            $requested_data['purchase_order_id'] = $purchase_order->id;
            $requested_data['vendor_id'] = $purchase_order->vendor_id;
            $requested_data['notes'] = $purchase_order->notes;
            $requested_data['subtotal'] = $purchase_order->subtotal;
            $requested_data['pst'] = $purchase_order->pst;
            $requested_data['gst'] = $purchase_order->gst;
            $requested_data['total'] = $purchase_order->total;
            $requested_data['override_gst'] = $purchase_order->override_gst;
            $requested_data['override_pst'] = $purchase_order->override_pst;
            $requested_data['created_by_id'] = Auth::user()->id;
            $model->fill($requested_data);
            if ($model->save()) {
                
                $items = PurchaseOrderItem::where('purchase_order_id',$purchase_order->id)->get()->toArray();
                if(!empty($items)){
                    foreach($items as $value){
                        if(!empty($value)){
                            $this->createPayableItem($model->id,$value);
                        }
                    }
                }
                return $model;
            }
        }
        return $payable_check;
    }

    /**
     * Create Payable Invoice Item
     *
     * @param  mixed $payable_id
     * @param  mixed $items
     *
     * @return void
     */
    public function createPayableItem($payable_id,$items){
        $model=new PayableInvoiceItem;

        $items['payable_invoice_id']=$payable_id;
        unset($items['purchase_order_id'],$items['created_at'],$items['updated_at'],$items['deleted_at']);
        $model->fill($items);
        $model->save();
    }


    /**
     * update Invoice Item
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function updatePayableItem($request){
        if(isset($request['payable_item']) && !empty($request['payable_item'])){
            foreach($request['payable_item'] as $payable_item){
                if(isset($payable_item['id']) && !empty($payable_item['id']))
                {
                    $data=$payable_item;
                    unset($data['id']);
                    PayableInvoiceItem::where('id',$payable_item['id'])->update($data);
                }
                else{
                    $model=new PayableInvoiceItem;
                    $data=$payable_item;
                    $data['payable_invoice_id'] = $request['id'];
                    $model->fill($data);
                    $model->save();
                }
            }
        }
    }

    /**
     * delete Payable Items
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function deletePayableItem($request){
        if(isset($request['delete_payable_item']) && !empty($request['delete_payable_item'])){
            PayableInvoiceItem::whereIn('id',$request['delete_payable_item'])->update(['deleted_at'=>date('Y-m-d H:i:s')]);
        }
    }
    
    /**
     * upload Document File
     *
     * @param  mixed $request
     * @return void
     */
    public function uploadDocumentFile($request)
    {
        //File path set here
        $file = $request->file('file');
        $destinationPath = 'public/document/';
        //Uploade File Here
        $file_orignal_name = $file->getClientOriginalName();
        $ext = $file->getClientOriginalExtension();
        $file_name = str_replace('.'.$ext,"", $file_orignal_name ).time().'.'.$ext;
        $file_name = str_replace(' ', '-', $file_name);
        $uploaded = Storage::put($destinationPath.$file_name, (string) file_get_contents($file), 'public');
        //Save File in Files Table
        if($uploaded) {
            $file_path = 'app/'.$destinationPath.$file_name;
            $request_data['file_name'] = $file_orignal_name;
            $request_data['path'] = $file_path;
            $request_data['file_type'] = 'document';
            $request_data['object_type'] = 'payable_document' ;
            $request_data['object_id'] = $request->payable_invoice_id ;
            $request_data['upload_by'] = Auth::user()->id;
            if($image = File::create($request_data)){
                //Save File Data in payable file table
                $data['company_id'] = Auth::user()->company_id;
                $data['payable_invoice_id'] = $request->payable_invoice_id;
                $data['created_date'] = date('Y-m-d H:i:s');
                $data['ext'] = $ext;
                $data['file_id'] = $image->id;
                if($payable_file = PayableInvoiceFile::create($data)){
                    return PayableInvoiceFile::where('id',$payable_file->id)->first();
                }
            }
        }
        return false;
    }

}

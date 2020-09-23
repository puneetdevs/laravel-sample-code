<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\ApiController;
use App\Models\File;
use App\Models\PayableInvoice;
use App\Models\PayableInvoiceFile;
use App\Transformers\PayableInvoiceTransformer;
use App\Transformers\PayableFileTransformer;
use App\Http\Requests\Api\PayableInvoices\Index;
use App\Http\Requests\Api\PayableInvoices\Show;
use App\Http\Requests\Api\PayableInvoices\Create;
use App\Http\Requests\Api\PayableInvoices\Store;
use App\Http\Requests\Api\PayableInvoices\Edit;
use App\Http\Requests\Api\PayableInvoices\Update;
use App\Http\Requests\Api\PayableInvoices\Destroy;
use App\Http\Requests\Api\PayableInvoices\StorePurchasePayable;
use App\Http\Requests\Api\PayableInvoices\UploadPayableFile;
use App\Http\Requests\Api\PayableInvoices\DeletePayableFile;
use App\Repositories\PayableInvoiceRepository;
use App\Helpers\Helper;
use Illuminate\Support\Facades\Storage;


/**
 * PayableInvoice
 *
 * @Resource("PayableInvoice", uri="/payable_invoices")
 */

class PayableInvoiceController extends ApiController
{
    /*Construct here define invoice repository */
    public function __construct(PayableInvoiceRepository $payableInvoiceRepository){
        $this->payableInvoiceRepository = $payableInvoiceRepository;
    }
    
    /**
     * Get Payable Invoice Listing
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function index(Index $request)
    {

        //Set Sort by & Sort by Column
        $sortBy = Helper::setSortByValue($request);

        //per page set here
        $per_page = Helper::setPerPage($request);

        $payable = PayableInvoice::select('*');

        if($request->has('number') && !empty($request->number)){

            $number=$request->number;
            $count=strlen($number);
            //Add dash in the payable invoice no
            if( $count>3 && (substr($number, 4, 1))!='-' )
            {
                $number=substr_replace( $number,'-', 4, 0 );
            }
            $payable->where('payable_invoices.number', 'like' , '%'.$number.'%');
        }

        if($request->has(VENDOR_ID) && !empty($request->vendor_id)){    
            $payable->where('payable_invoices.vendor_id','like','%'.$request->vendor_id.'%');
        }

        if($request->has('invoice_number') && !empty($request->invoice_number)){    
            $payable->where('payable_invoices.invoice_number','like','%'.$request->invoice_number.'%');
        }

        return $this->response->paginator($payable->orderBy($sortBy['column'], $sortBy['order'])->paginate($per_page), new PayableInvoiceTransformer());
    }

    /**
     * Get Single Payable Invoice
     *
     * @param  mixed $request
     * @param  mixed $id
     *
     * @return void
     */
    public function show(Show $request, $id)
    {
        $payable=PayableInvoice::where('id',$id)->first();
        if($payable){
            return $this->response->item($payable, new PayableInvoiceTransformer());
        }
        return response()->json([MESSAGE=>'No matching records found.'], 404);
    }

    public function store(Store $request)
    {
        $model=new PayableInvoice;
        $model->fill($request->all());
        if ($model->save()) {
            return $this->response->item($model, new PayableInvoiceTransformer());
        } else {
              return $this->response->errorInternal('Error occurred while saving PayableInvoice');
        }
    }
 
    /**
     * Update Payable Invoice And Items
     *
     * @param  mixed $request
     * @param  mixed $payableinvoice_id
     *
     * @return void
     */
    public function update(Update $request ,$payableinvoice_id)
    {
        $input_data = $request->only('id','date','invoice_number','vendor_id',
        'notes', 'subtotal', 'pst', 'gst', 'total', 'override_gst',
        'override_pst', 'posted','posted_date');
        
        $input_data['posted_date']=$request->posted == '1'  ? date('Y-m-d H:i:s') : NULL;
        PayableInvoice::where('id',$request->id)->update($input_data);
        $this->payableInvoiceRepository->updatePayableItem($request->all());
        $this->payableInvoiceRepository->deletePayableItem($request->all());
        return $this->response->array([STATUS => 200, MESSAGE => 'Details has been saved.']);
    }

    public function destroy(Destroy $request, $payableinvoice)
    {
        $payableinvoice = PayableInvoice::findOrFail($payableinvoice);

        if ($payableinvoice->delete()) {
            return $this->response->array(['status' => 200, 'message' => 'PayableInvoice successfully deleted']);
        } else {
             return $this->response->errorInternal('Error occurred while deleting PayableInvoice');
        }
    }

    /**
     * Store Payable Invoice From Purchase Order
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function storePayableFromPurchaseOrder(StorePurchasePayable $request)
    {
        if ($model = $this->payableInvoiceRepository->createPurchaseOrderPayable($request->all())) {
            return $this->response->item($model, new PayableInvoiceTransformer());
        } else {
              return $this->response->errorInternal('Error occurred while saving payable invoice.');
        }
    }

    /**
     * Payable Invoices Listing In Vendor
     *
     * @param  mixed $request
     * @param  mixed $vendor_id
     *
     * @return void
     */
    public function getVendorPayableInvoices(Request $request, $vendor_id)
    {
        //Set Sort by & Sort by Column
        $sortBy = Helper::setSortByValue($request);

        //Set Per Page Record
        $per_page = Helper::setPerPage($request);

        $payable = PayableInvoice::where(VENDOR_ID,$vendor_id);
        return $this->response->paginator($payable->orderBy($sortBy['column'], $sortBy['order'])->paginate($per_page), new PayableInvoiceTransformer());
    }
           
    /**
     * Payable Invoice File Upload
     *
     * @param  mixed $request
     * @return void
     */
    public function payableInvoiceFileUpload(UploadPayableFile $request)
    {
        if($payablefile = $this->payableInvoiceRepository->uploadDocumentFile($request)){
            return $this->response->item($payablefile, new PayableFileTransformer());
        }
        return $this->response->errorInternal('Error while uploading file in payable invoice. Please try again.');
    }
    
    /**
     * Payable File Listing
     *
     * @param  mixed $request
     * @return void
     */
    public function payableFileListing(Request $request)
    {
        //Set Sort by & Sort by Column
        $sortBy = Helper::setSortByValue($request);

        //Set Per Page Record
        $per_page = Helper::setPerPage($request);

        if($request->has('payable_invoice_id') && !empty($request->payable_invoice_id)){
            //Start Payable File Query
            $payablefile = PayableInvoiceFile::where('payable_invoice_id', $request->payable_invoice_id );

            return $this->response->paginator($payablefile->orderBy($sortBy['column'], $sortBy['order'])->paginate($per_page), new PayableFileTransformer());
        }
        return $this->response->errorInternal('Please send payable_invoice_id.');
    }
    
    /**
     * Delete Payable Files
     *
     * @param  mixed $request
     * @return void
     */
    public function deletePayableFiles(DeletePayableFile $request)
    {
        if(PayableInvoiceFile::whereIn('id',$request->id)->update([DELETED_AT => date('Y-m-d H:i:s')])){
            
            foreach($request->id as $payable_file)
            {
                $file_id=PayableInvoiceFile::where('id',$payable_file)->withTrashed()->pluck('file_id');
                $file_path=File::where('id',$file_id)->pluck('path')->first();
                unlink(storage_path($file_path));
            }
            return $this->response->array([STATUS => 200, MESSAGE => DELETED_SUCCESSFULLY]);
        }
        return $this->response->errorInternal(DELETE_ERROR_MSG);
    }
}

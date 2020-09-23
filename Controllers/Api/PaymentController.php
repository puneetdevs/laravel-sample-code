<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\ApiController;
use App\Models\Payment;
use App\Models\Invoice;
use App\Transformers\PaymentTransformer;
use App\Transformers\InvoiceTransformer;
use App\Http\Requests\Api\Payments\Index;
use App\Http\Requests\Api\Payments\Show;
use App\Http\Requests\Api\Payments\Create;
use App\Http\Requests\Api\Payments\Store;
use App\Http\Requests\Api\Payments\Edit;
use App\Http\Requests\Api\Payments\Update;
use App\Http\Requests\Api\Payments\Destroy;
use App\Http\Requests\Api\Payments\DeletePayment;
use App\Helpers\Helper;

/**
 * Payment
 *
 * @Resource("Payment", uri="/payments")
 */

class PaymentController extends ApiController
{
    
    /**
     * Get Payment Listing
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function index(Index $request)
    {
       return $this->response->paginator(Payment::paginate(10), new PaymentTransformer());
    }

    /**
     * show Single Payment Detail
     *
     * @param  mixed $request
     * @param  mixed $payment
     *
     * @return void
     */
    public function show(Show $request, $payment)
    {
        $payable=Payment::where('id',$payment)->first();
        if($payable){
            return $this->response->item($payable, new PaymentTransformer());
        }
        return response()->json([MESSAGE=>'No matching records found.'], 404);
    }

    /**
     * store Payment
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function store(Store $request)
    {
        foreach($request->payment as $value){
           
            $invoicedata=Invoice::where('id',$value['invoice_id'])->first();
            $due=$invoicedata->due-$value['amount'];
            Invoice::where('id',$value['invoice_id'])->update(['due' => $due ]);

            $request_data = $value;
            $request_data['management_id'] = $invoicedata->management_id;
            $model=new Payment;
            $model->fill($request_data);
            $model->save();
        }
        return $this->response->array([STATUS => 200, MESSAGE => 'Payment has been added.']);
    }
 
    /**
     * update Payment Detail
     *
     * @param  mixed $request
     * @param  mixed $payment
     *
     * @return void
     */
    public function update(Update $request,  Payment $payment)
    {
        $payment->fill($request->all());

        if ($payment->save()) {
            return $this->response->item($payment, new PaymentTransformer());
        } else {
             return $this->response->errorInternal('Error occurred while saving payment.');
        }
    }

    /**
     * destroy
     *
     * @param  mixed $request
     * @param  mixed $payment
     *
     * @return void
     */
    public function destroy(Destroy $request, $payment)
    {
        $payment = Payment::findOrFail($payment);

        if ($payment->delete()) {
            return $this->response->array([STATUS => 200, MESSAGE => 'Payment successfully deleted.']);
        } else {
             return $this->response->errorInternal('Error occurred while deleting payment.');
        }
    }

    /**
     * delete Payment
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function deletePayment(DeletePayment $request)
    {
        foreach($request->id as $id)
        {
            $paymentinfo=Payment::where('id',$id)->first();
            #Update invoice due amount 
            $invoice = Invoice::find( $paymentinfo->invoice_id );
            $invoice->due += $paymentinfo->amount;
            $invoice->save();

            Payment::where('id',$id)->update(['deleted_at' => date('Y-m-d H:i:s')]);
        }
        return $this->response->array([STATUS => 200, MESSAGE => 'Successfully deleted.']);
    }

    

}

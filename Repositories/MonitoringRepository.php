<?php

namespace App\Repositories;


use DB;
use App\Exceptions\Handler;
use App\Repositories\BaseRepository;
use App\Models\Monitoring; 
use App\Models\Workorder; 
use App\Models\Invoice; 
use App\Models\Place; 
use App\Models\Management; 
use App\Models\InvoiceItem; 
use App\Models\GlAccount; 
use App\Models\StreetType; 
use Auth;
use Carbon\Carbon;
use App\Helpers\Helper;
/**
 * Class MonitoringRepository.
 */
class MonitoringRepository extends BaseRepository
{
    /**
     * @return string
     */
    public function model()
    {
        return Monitoring::class;
    }

    /**
     * Get Monitoring Report
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function getMonitoringReport($request){
        return Monitoring::where('company_id',Auth::user()->company_id)
                                ->where('next_billing_date','<=',$request->due_date)
                                ->with('invoice');
    }

    /**
     * Create Monitoring Invoice
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function createMonitoringInvoice($request){
        foreach($request->ids as $key => $id){
            $monitoring = Monitoring::where('id',$id)->first();
            #Create workorder
            $model=new Workorder;
            $requested_data['company_id'] = $monitoring->company_id;
            $requested_data['created_by_id'] = Auth::user()->id;
            $requested_data['created_date'] = date('Y-m-d H:i:s');
            $requested_data['place_id'] = $monitoring->place_id;
            $requested_data['bill_to_id'] = $monitoring->bill_to_id;
            $requested_data['management_id'] = $monitoring->bill_to_id;
            $requested_data['workorder_type_id'] = 9;
            $requested_data['workorder_status_id'] = 5;
            $requested_data['instructions'] = 'Monitoring ending '.$request->due_date;
            $model->fill($requested_data);
            if ($model->save()) {
                $workorder_number = Workorder::where('company_id',Auth::user()->company_id)->whereRaw('MONTH(created_at) = ?',[date('m')])->count();
                $number = Helper::setWorkorderNumber($workorder_number);
                Workorder::where('id',$model->id)->update(['number' => $number]);
                $model->number=$number;

                $billing_date=date("Y-m-d", strtotime('+'.$monitoring->billing_months.' months', strtotime($monitoring->next_billing_date)));
                Monitoring::where('id',$monitoring->id)->update(['next_billing_date' => $billing_date]);

                #Create Invoice
                $effectiveDate = strtotime($monitoring->billing_months." months", strtotime($monitoring->next_billing_date));
                $data['company_id'] = Auth::user()->company_id;
                $data['date'] = $request->invoice_date;
                $data['place_id'] = $monitoring->place_id;
                $data['monitoring_id'] = $monitoring->id;
                $data['bill_to_id'] = $monitoring->bill_to_id;
                $data['management_id'] = $monitoring->bill_to_id;
                $data['notes'] = 'Monitoring for account '.$monitoring->account.' for the period '.$monitoring->next_billing_date.' - '.date('Y-m-d',$effectiveDate);
                $data['workorder_id'] = $model->id;                          
                $data['subtotal'] = $monitoring->billing_amount;                         
                $data['pst'] = '10';                          
                $data['gst'] = '10';                          
                $data['total'] = '10';                          
                $data['due'] = '10';                          
                $data['posted_date'] = $request->invoice_date;
                $data['created_date'] = $request->invoice_date;
                $data['created_by_id'] = Auth::user()->id;
                $workorder[$key] = $model->toArray();
                $invoice = $this->createInvoice($data,$monitoring->billing_months,$monitoring->billing_amount,$number,$monitoring->id);
                $invoices[$key] = ($invoice) ? $invoice : '';
                
            }else{
                $workorder[$key] = '';
            }
        }
        return $this->sendFinalData($workorder, $invoices);
    }

    /**
     * Create Invoice
     *
     * @param  mixed $data
     * @param  mixed $month
     * @param  mixed $price
     *
     * @return void
     */
    public function createInvoice($data,$month,$price,$number,$monitoring_id){
        $model=new Invoice;
        $model->fill($data);
        if ($model->save()) {
            $number = substr($number, 0, 9);
            Invoice::where('id',$model->id)->update(['number' => $number]);
            $model->number=$number;
            Monitoring::where('id',$monitoring_id)->update(['last_invoice_id' => $model->id]);
            $this->createInvoiceItem($model->id,$month,$price);
            return $model->toArray();
        } 
        return null;
    }

    /**
     * Create Invoice Item
     *
     * @param  mixed $invoic_id
     * @param  mixed $month
     * @param  mixed $price
     *
     * @return void
     */
    public function createInvoiceItem($invoic_id,$month,$price){
        $gl = GlAccount::where('description','like','%Monitoring%')->where('type','s')->where('status',1)->first();
        if(!$gl){
            $glmodel=new GlAccount;
            $gldata['company_id'] = Auth::user()->company_id;
            $gldata['gl_number'] = '9999';
            $gldata['description'] = 'Monitoring';
            $gldata['type'] = 's';
            $gldata['status'] = 1;
            $glmodel->fill($gldata);
            if ($glmodel->save()) {
                $gl = $glmodel->id;
            }
        }else{
            $gl = $gl->id;
        }
        $model=new InvoiceItem;
        $data['company_id'] = Auth::user()->company_id;
        $data['invoice_id'] = $invoic_id;
        $data['quantity'] = 1;
        $data['description'] = 'Monitoring, '.$month.' months service.';
        $data['unit_price'] = $price;
        $data['price'] = $price;
        $data['charge_pst'] = 0;
        $data['gl_id'] = $gl;
        $model->fill($data);
        $model->save();
    }

    /**
     * Send Final Data
     *
     * @param  mixed $workorder
     * @param  mixed $invoice
     *
     * @return void
     */
    public function sendFinalData($workorder , $invoice){
        $final_data = array();
        foreach($workorder as $key => $value){
            $place = Place::where('id',$value['place_id'])->first();
            $final_data[$key]['place'] = '';
            if($place){
                $final_data[$key]['place'] = $place->name;
                $final_data[$key]['street_number'] = $place->street_number;
                $final_data[$key]['street_name'] = $place->street_name;

                $street = StreetType::where('id',$place->street_type_id)->first();
                if(!empty($street))
                {
                    $final_data[$key]['street_type'] = $street->street_type;
                }
            }
            $management = Management::where('id',$value['bill_to_id'])->first();
            $final_data[$key]['management'] = '';
            if($management){
                $final_data[$key]['management'] = $management->name;
            }
            $final_data[$key]['workorder_id'] = $value['id'];
            $final_data[$key]['workorder_number'] = $value['number'];
            $final_data[$key]['invoice_id'] = ($invoice[$key]['id']) ? $invoice[$key]['id'] : '';
            $final_data[$key]['invoice_number'] = ($invoice[$key]['number']) ? $invoice[$key]['number'] : '';
        }
        return $final_data;
    }
}

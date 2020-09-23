<?php

namespace App\Repositories;


use DB;
use App\Exceptions\Handler;
use App\Repositories\BaseRepository;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Workorder;
use App\Models\WorkorderTime;
use App\Models\PlacesManagement;
use App\Models\GlAccount;
use App\Models\Monitoring;
use App\Models\WorkorderStatus;
use App\Models\QuoteItem;
use App\Models\Quote;
use App\Models\Management;
use App\Models\Payment;
use DateTime;
use Auth;
use Carbon\Carbon;
use App\Helpers\Helper;
use App\Repositories\WorkorderRepository;
use App\Models\Company;
/**
 * Class InvoiceRepository.
 */
class InvoiceRepository extends BaseRepository
{
    public function __construct(WorkorderRepository $workorderRepository){
        $this->workorderRepository = $workorderRepository;
    }

    /**
     * @return string
     */
    public function model()
    {
        return Invoice::class;
    }

    /**
     * Get Single Invoice Detail
     *
     * @param  mixed $invoice
     *
     * @return void
     */
    Public function getSingleInvoiceDetail($invoice){
        
        //$LoggedInUserTz = Company::find(Auth::user()->company_id)->timezone; // Get LoggedIn user Timezone
        
        $invoice = Invoice::where('id',$invoice)
                            ->with([
                                'invoiceitem','invoiceitem.part','invoiceitem.glaccount',
                                'place','place.city','place.city.province','place.city.province.country','place.street','billto','billto.management' => function($q){
                                    $q->select('id','name',ADDRESS);
                                },'placemanagement','placemanagement.management' => function($q){
                                    $q->select('id','name',ADDRESS);
                                }])->first();
        if($invoice){
            //Time convertion according to Timezone
            /* $invoice->posted_date=($invoice->posted_date)?$invoice->posted_date->timezone($LoggedInUserTz)->format('Y-m-d H:i:s'):$invoice->posted_date;
            $invoice->date=$invoice->date->timezone($LoggedInUserTz)->format('Y-m-d');
            $invoice->created_date=($invoice->created_date)?$invoice->created_date->timezone($LoggedInUserTz)->format('Y-m-d H:i:s'):$invoice->created_date;
             */
            $invoice = $invoice->toArray();
            $workorder = Workorder::where('id',$invoice['workorder_id'])->first();
            $workorderNumber = explode("-",$workorder->number);
            $workorderNumber = isset($workorderNumber[0]) && isset($workorderNumber[1]) ? $workorderNumber[0].'-'.$workorderNumber[1] : $workorder->number;
            $workorderData = Workorder::where('number','like','%'.$workorderNumber.'%')
                                        ->with(['workordertime','workorderpart','workorderpart.part','purchaseorderitem.purchaseorder','purchaseorderitem.part'])->get()->toArray();
            $invoice['workorder'] = $workorderData;
        }
        return $invoice;
    }

    /**
     * Get Multiple Invoice Detail
     *
     * @param  mixed $invoice
     *
     * @return void
     */
    Public function getMultipleInvoiceDetail($invoice){
        
        //$LoggedInUserTz = Company::find(Auth::user()->company_id)->timezone; // Get LoggedIn user Timezone
                
        $invoice = Invoice::whereIn('id',$invoice)
                            ->with([
                                'invoiceitem',
                                'place','place.city','place.city.province','place.city.province.country','place.street','billto','billto.management' => function($q){
                                    $q->select('id','name',ADDRESS);
                                },'placemanagement','placemanagement.management' => function($q){
                                    $q->select('id','name',ADDRESS);
                                }])->get();
        if($invoice){
            $invoice = $invoice->toArray();
            foreach($invoice as $key=>$value)
            {
                $workorder = Workorder::where('id',$value['workorder_id'])->first();
                $workorderNumber = explode("-",$workorder->number);
                $workorderNumber = isset($workorderNumber[0]) && isset($workorderNumber[1]) ? $workorderNumber[0].'-'.$workorderNumber[1] : $workorder->number;
                $workorderData = Workorder::where('number','like','%'.$workorderNumber.'%')
                                            ->with(['workordertime','workorderpart','workorderpart.part','purchaseorderitem'])->get()->toArray();
                $invoice[$key]['workorder'] = $workorderData;
            }
        }
        return $invoice;
    }

    /**
     * Create Workorder Invoice
     *
     * @param  mixed $request
     *
     * @return void
     */
    Public function createWorkorderInvoice($requested_data){
        $invoice_data = Invoice::where('workorder_id',$requested_data['workorder_id'])->first();
        if(!$invoice_data){
            $workorder = Workorder::where('id',$requested_data['workorder_id'])->with('workorderpart','workorderpart.part')->first();
            
            $quotedata = Quote::where('id',$workorder->quote_id)->first();
            $model=new Invoice;
            $requested_data['date'] = date('Y-m-d');
            $requested_data['place_id'] = $workorder->place_id;
            $requested_data['bill_to_id'] = $workorder->bill_to_id;
            $requested_data['management_id'] = $workorder->management_id;
            $requested_data['notes'] = $workorder->notes;
            $requested_data['purchase_order'] = $workorder->purchase_order;
            $requested_data['quote_id'] = $workorder->quote_id;
            $requested_data['workorder_id'] = $workorder->id;
            $requested_data['subtotal'] = ($quotedata)?$quotedata->subtotal:0;
            $requested_data['pst'] = ($quotedata)?$quotedata->pst:0;
            $requested_data['gst'] = ($quotedata)?$quotedata->gst:0;
            $requested_data['total'] = ($quotedata)?$quotedata->total:0;
            $requested_data['due'] = ($quotedata)?$quotedata->total:0;
            $requested_data['override_gst'] = ($quotedata)?$quotedata->override_gst:0;
            $requested_data['override_pst'] = ($quotedata)?$quotedata->override_pst:0;
            $requested_data['created_by_id'] = Auth::user()->id;
            $model->fill($requested_data);
            if ($model->save()) {
                
                $number=explode('-',$workorder->number);
                $invoice_number=$number[0].'-'.$number[1];
                
                Invoice::where('id',$model->id)->update(['number' => $invoice_number]);
                $model->number = $invoice_number;

                //Update workorder status to invoiced and update completed date(current date)
                $workorderstatus = WorkorderStatus::where('workorder_status','Invoiced')->first();
                Workorder::where('id',$requested_data['workorder_id'])->update(['workorder_status_id' => $workorderstatus->id,'completed_date'=>date('Y-m-d H:i:s') ]);

                $items = QuoteItem::where('quote_id',$workorder->quote_id)->get()->toArray();
                if(!empty($items)){
                    foreach($items as $value){
                        if(!empty($value)){
                            $this->createInvoiceItem($model->id,$value);
                        }
                    }
                }
                return $model;
            }else{
                return false;
            }
        }
        return $invoice_data;
    }

    /**
     * Create Invoice Item
     *
     * @param  mixed $invoice
     * @param  mixed $part
     *
     * @return void
     */
    public function createInvoiceItem($invoice,$items){
        $model=new InvoiceItem;
        $data['company_id'] = Auth::user()->company_id;
        $data['invoice_id'] = $invoice;
        $data['quantity'] = $items['quantity'];
        $data['description'] = $items['description'];
        $data['part_id'] = $items['part_id'];
        $data['unit_price'] = $items['unit_price'];
        $data['price'] = $items['price'];
        $data['charge_pst'] = $items['charge_pst'];
        $data['gl_id'] = $items['gl_id'];
        $model->fill($data);
        $model->save();
    }

    /**
     * update Invoice Item
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function updateInvoiceItem($request){
        if(isset($request['invoice_item']) && !empty($request['invoice_item'])){
            foreach($request['invoice_item'] as $invoice_item){
                if(isset($invoice_item['id']) && !empty($invoice_item['id'])){
                    $data['quantity'] = $invoice_item['quantity'];
                    $data['description'] = $invoice_item['description'];
                    isset($invoice_item['part_id'])? $data['part_id'] =  $invoice_item['part_id'] : '';
                    $data['unit_price'] = $invoice_item['unit_price'];
                    $data['price'] = $invoice_item['price'];
                    $data['charge_pst'] = $invoice_item['charge_pst'];
                    $data['gl_id'] = $invoice_item['gl_id'];
                    InvoiceItem::where('id',$invoice_item['id'])->update($data);
                }else{
                    $model=new InvoiceItem;
                    $data['company_id'] = Auth::user()->company_id;
                    $data['invoice_id'] = $request['id'];
                    $data['quantity'] = $invoice_item['quantity'];
                    $data['description'] = $invoice_item['description'];
                    isset($invoice_item['part_id'])? $data['part_id'] =  $invoice_item['part_id'] : '';
                    $data['unit_price'] = $invoice_item['unit_price'];
                    $data['price'] = $invoice_item['price'];
                    $data['charge_pst'] = $invoice_item['charge_pst'];
                    $data['gl_id'] = $invoice_item['gl_id'];
                    $model->fill($data);
                    $model->save();
                }
            }
        }
    }

    /**
     * delete Invoice Item
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function deleteInvoiceItem($request){
        if(isset($request['delete_invoice_item']) && !empty($request['delete_invoice_item'])){
            InvoiceItem::whereIn('id',$request['delete_invoice_item'])->update(['deleted_at'=>date('Y-m-d H:i:s')]);
        }
    }

    /**
     * get Invoice Aging Detail Report
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function getInvoiceAgingDetailReport($request)
    {
        //Set Dates here
        $days_30 = Carbon::now()->subDays(30)->format('Y-m-d');
        $days_45 = Carbon::parse($days_30)->subDays(15)->format('Y-m-d');
        $days_60 = Carbon::parse($days_45)->subDays(15)->format('Y-m-d');
        $days_90 = Carbon::parse($days_60)->subDays(30)->format('Y-m-d');

        //Get All Invoices
        $invoices = Invoice::where('company_id', Auth::user()->company_id)->where(POSTED,1)
                        ->whereNull('deleted_at')->where('due','>',0)
                        ->with('billto','billto.management')->orderBy('number','desc')
                        ->get()->groupBy('billto.management.id')->toArray();
        //Set Empty Array here
        $totals['final_total'] = 0;
        $totals['final_thirty'] = 0;
        $totals['final_forty_five'] = 0;
        $totals['final_sixty'] = 0;
        $totals['final_ninety'] = 0;
        $totals['final_ninety_plus'] = 0;
        $final_array = array();
        $i = 0;
        foreach($invoices as $invoice)
        {   
            $final_array[$i]['sub_total_invoice']['thirty_sub_total'] = 0;
            $final_array[$i]['sub_total_invoice']['forty_five_sub_total'] = 0;
            $final_array[$i]['sub_total_invoice']['sixty_sub_total'] = 0;
            $final_array[$i]['sub_total_invoice']['ninety_sub_total'] = 0;
            $final_array[$i]['sub_total_invoice']['ninety_plus_sub_total'] = 0;
            
            foreach($invoice as $keys=>$value)
            {
                $final_array[$i]['managment'] = $value['billto']['management'];
                
                //Get 30 Day before and future all records
                $invoice_30 = Invoice::where('company_id', Auth::user()->company_id)->where(POSTED,1)
                                    ->where('date', '>=' , $days_30)
                                    ->where('id', $value['id'])
                                    ->whereNull('deleted_at')
                                    ->get()->sum('due');
                                    
                //Get 30-45 Days before current date
                $invoice_45 = Invoice::where('company_id', Auth::user()->company_id)->where(POSTED,1)
                                    ->whereBetween('date', [$days_45,date('Y-m-d', strtotime('-1 day', strtotime($days_30)))])
                                    ->whereNull('deleted_at')
                                    ->where('id', $value['id'])
                                    ->get()->sum('due');
                                    
                //Get 45-60 Days before current date
                $invoice_60 = Invoice::where('company_id', Auth::user()->company_id)->where(POSTED,1)
                                    ->whereBetween('date', [$days_60,date('Y-m-d', strtotime('-1 day', strtotime($days_45)))])
                                    ->whereNull('deleted_at')
                                    ->where('id', $value['id'])
                                    ->get()->sum('due');
                //Get 60-90 Days before current date
                $invoice_90 = Invoice::where('company_id', Auth::user()->company_id)->where(POSTED,1)
                                    ->whereBetween('date', [$days_90,date('Y-m-d', strtotime('-1 day', strtotime($days_60)))])
                                    ->whereNull('deleted_at')
                                    ->where('id', $value['id'])
                                    ->get()->sum('due');
                                    
                //Get 90+ Days before current date
                $invoice_90_plus = Invoice::where('company_id', Auth::user()->company_id)->where(POSTED,1)
                                    ->where('date', '<', $days_90)
                                    ->where('id', $value['id'])
                                    ->whereNull('deleted_at')
                                    ->get()->sum('due');

                //Get Total and Subtotal here
                $final_array[$i]['invoice'][$keys]['id'] = $value['id'];
                $final_array[$i]['invoice'][$keys]['number'] = $value['number'];
                $final_array[$i]['invoice'][$keys]['zero_thirty'] = $invoice_30;
                $final_array[$i]['invoice'][$keys]['thirty_forty_five'] = $invoice_45;
                $final_array[$i]['invoice'][$keys]['forty_five_sixty'] = $invoice_60;
                $final_array[$i]['invoice'][$keys]['sixty_ninety'] = $invoice_90;
                $final_array[$i]['invoice'][$keys]['ninety_plus'] = $invoice_90_plus;
                $final_array[$i]['sub_total_invoice']['thirty_sub_total'] += $invoice_30;
                $final_array[$i]['sub_total_invoice']['forty_five_sub_total'] += $invoice_45;
                $final_array[$i]['sub_total_invoice']['sixty_sub_total'] += $invoice_60;
                $final_array[$i]['sub_total_invoice']['ninety_sub_total'] += $invoice_90;
                $final_array[$i]['sub_total_invoice']['ninety_plus_sub_total'] += $invoice_90_plus;
                $totals['final_total'] += $invoice_30 + $invoice_45 + $invoice_60 + $invoice_90 + $invoice_90_plus;
                $totals['final_thirty'] += $invoice_30;
                $totals['final_forty_five'] += $invoice_45;
                $totals['final_sixty'] += $invoice_60;
                $totals['final_ninety'] += $invoice_90;
                $totals['final_ninety_plus'] += $invoice_90_plus;
            }
            $i++;
        }
        return array('final_array' => $final_array, 'totals' => $totals);
    }

    /**
     * Get Invoice Aging Summary Report
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function getInvoiceAgingSummaryReport($request)
    {
        //Set Dates here
        $days_30 = Carbon::now()->subDays(30)->format('Y-m-d');
        $days_45 = Carbon::parse($days_30)->subDays(15)->format('Y-m-d');
        $days_60 = Carbon::parse($days_45)->subDays(15)->format('Y-m-d');
        $days_90 = Carbon::parse($days_60)->subDays(30)->format('Y-m-d');
        //Get All Invoices
        $invoice = Invoice::select('bill_to_id')->where('company_id', Auth::user()->company_id)
                        ->where(POSTED,1)
                        ->whereNull('deleted_at')
                        ->with('billto','billto.management')->distinct('bill_to_id')->orderBy('bill_to_id','asc')
                        ->get()->groupBy('billto.management.id')->toArray();
        
        
        $invoice_billto=[];$i=0;                
        foreach($invoice as $invoice_value)
        {
            $j=0;
            foreach($invoice_value as $billto)
            {
                $invoice_billto[$i]['management_detail']=$billto['billto']['management'];
                $invoice_billto[$i]['billtoid'][$j]=$billto['bill_to_id'];
                $j++;
            }
            $i++;
        }

        //Set Empty Array here
        $totals['final_total'] = 0;
        $totals['final_thirty'] = 0;
        $totals['final_forty_five'] = 0;
        $totals['final_sixty'] = 0;
        $totals['final_ninety'] = 0;
        $totals['final_ninety_plus'] = 0;
        $final_array = array();
            
            foreach($invoice_billto as $key=>$value)
            { 
                $final_array[$key]['billto'] = $value['management_detail'];
                
                //Get 30 Day before and future all records
                $invoice_30 = Invoice::where('company_id', Auth::user()->company_id)->where(POSTED,1)
                                    ->whereIn('bill_to_id',$value['billtoid'])
                                    ->where('date', '>=' , $days_30)
                                    ->whereNull('deleted_at')
                                    ->get()->sum('due');
                                    
                //Get 30-45 Days before current date
                $invoice_45 = Invoice::where('company_id', Auth::user()->company_id)->where(POSTED,1)
                                    ->whereIn('bill_to_id',$value['billtoid'])
                                    ->whereBetween('date', [$days_45,date('Y-m-d', strtotime('-1 day', strtotime($days_30)))])
                                    ->whereNull('deleted_at')
                                    ->get()->sum('due');
                                    
                //Get 45-60 Days before current date
                $invoice_60 = Invoice::where('company_id', Auth::user()->company_id)->where(POSTED,1)
                                    ->whereIn('bill_to_id',$value['billtoid'])
                                    ->whereBetween('date', [$days_60,date('Y-m-d', strtotime('-1 day', strtotime($days_45)))])
                                    ->whereNull('deleted_at')
                                    ->get()->sum('due');
                //Get 60-90 Days before current date
                $invoice_90 = Invoice::where('company_id', Auth::user()->company_id)->where(POSTED,1)
                                    ->whereIn('bill_to_id',$value['billtoid'])
                                    ->whereBetween('date', [$days_90,date('Y-m-d', strtotime('-1 day', strtotime($days_60)))])
                                    ->whereNull('deleted_at')
                                    ->get()->sum('due');
                                    
                //Get 90+ Days before current date
                $invoice_90_plus = Invoice::where('company_id', Auth::user()->company_id)->where(POSTED,1)
                                    ->whereIn('bill_to_id',$value['billtoid'])     
                                    ->where('date', '<', $days_90)
                                    ->whereNull('deleted_at')
                                    ->get()->sum('due');

                //Get Total and Subtotal here
                $final_array[$key]['zero_thirty'] = $invoice_30;
                $final_array[$key]['thirty_forty_five'] = $invoice_45;
                $final_array[$key]['forty_five_sixty'] = $invoice_60;
                $final_array[$key]['sixty_ninety'] = $invoice_90;
                $final_array[$key]['ninety_plus'] = $invoice_90_plus;
                $final_array[$key]['sub_total'] = $invoice_30 + $invoice_45 + $invoice_60 + $invoice_90 + $invoice_90_plus;
                $totals['final_total'] += $invoice_30 + $invoice_45 + $invoice_60 + $invoice_90 + $invoice_90_plus;
                $totals['final_thirty'] += $invoice_30;
                $totals['final_forty_five'] += $invoice_45;
                $totals['final_sixty'] += $invoice_60;
                $totals['final_ninety'] += $invoice_90;
                $totals['final_ninety_plus'] += $invoice_90_plus;
            }
        return array('final_array' => $final_array, 'totals' => $totals);
    }

    /**
     * get Invoice Payment Report
     *
     * @param  mixed $request
     *
     * @return void
     */
    /* public function getInvoicePaymentReport($request)
    {
        $all_days = $this->workorderRepository->getDatesFromRange($request->start_date, $request->end_date);
        $placemanagement=PlacesManagement::join('payments', 'places_management.id', '=', 'payments.management_id')
                ->select('places_management.*', DB::raw('(payments.cheque) cheque'))
                ->get()->toArray();
        $i=0;
        $final_array=array();
        dd($all_days);
        foreach($all_days as $value)
        {
            foreach($placemanagement as $management)
            {
               
                $invoice=Invoice::where('payments.date', 'like' , '%'.$value.'%')
                    ->where('invoices.management_id',$management['id'])
                    ->whereNull('invoices.deleted_at')
                    ->whereNull('payments.deleted_at')
                    ->join('payments', 'invoices.id', '=', 'payments.invoice_id')
                    ->select('invoices.*', DB::raw('(payments.cheque) cheque,(payments.amount) amount'))
                    ->with('billto.management')
                    ->get()->toArray();
                
            }
            if(!empty($invoice)){
                $final_array[$i]['date'] = $value;
                $final_array[$i]['invoice'] = $invoice;
                $i++;
            } 
        }
       
        return $final_array;
    } */

    public function getInvoicePaymentReport($request)
    {
        $all_days = $this->workorderRepository->getDatesFromRange($request->start_date, $request->end_date);
        $i = 0;
        $final_array = array();
       
        foreach($all_days as $value)
        {
            $invoice=Invoice::join('payments', 'invoices.id', '=', 'payments.invoice_id')
                ->where('payments.date', 'like' , '%'.$value.'%')
                ->whereNull('invoices.deleted_at')
                ->whereNull('payments.deleted_at')
                ->select('invoices.*', DB::raw('(payments.cheque) cheque,(payments.amount) amount'))
                ->with('billto.management')
                ->get()->toArray();
                
            if(!empty($invoice)){
                $final_array[$i]['date'] = $value;
                $final_array[$i]['invoice'] = $invoice;
                $i++;
            } 
        }
       
        return $final_array;
    }

    /**
     * get Invoice Gl Report
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function getInvoiceGlReport($request)
    {
        $invoice=Invoice::whereBetween('invoices.date', [$request->start_date, $request->end_date])
                ->where('invoices.posted',1)
                ->whereNull('invoice_items.deleted_at')
                ->join('invoice_items', 'invoices.id', '=', 'invoice_items.invoice_id')
                ->join('gl_accounts', 'invoice_items.gl_id', '=', 'gl_accounts.id')
                ->select('invoices.gst','invoices.pst','invoice_items.*', DB::raw('(gl_accounts.gl_number) gl_number,(gl_accounts.description) description'))
                ->get()->groupBy('gl_id')->toArray();
        
        $final_array=array();
        $i=0;$gltotal=0;
        foreach($invoice as $value)
        {
            $total=0;
            foreach($value as $gldata)
            {
                $total=$total+$gldata['price'];
            }
            $gltotal=$gltotal+$total;
            $final_array['gl_detail'][$i]['gl_total'] = number_format((float)$total, 2, '.', '');
            $final_array['gl_detail'][$i]['gl_data']['glnumber'] = $value[0]['gl_number'];
            $final_array['gl_detail'][$i]['gl_data']['description'] = $value[0]['description'];
            $i++;
        }
        $final_array['gl_total']=number_format((float)$gltotal, 2, '.', '');

        $invoice_q=Invoice::whereBetween('invoices.date', [$request->start_date, $request->end_date])
                ->where('invoices.posted',1)->get()->toArray();
        $gst=0;$pst=0;$totalfortax=0;
        foreach($invoice_q as $value)
        {
            $gst += (float) $value['gst'];
            $pst += (float) $value['pst'];                
            $totalfortax+= $value['total'];
        }
        
        $final_array['taxdetail']['gst']=number_format((float)$gst, 2, '.', '');
        $final_array['taxdetail']['pst']=number_format((float)$pst, 2, '.', '');
        $final_array['taxdetail']['totaltax']=number_format((float)$totalfortax, 2, '.', '');
        
        return $final_array;
    }

    /**
     * Get Invoice Unposted Report
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function getInvoiceUnpostedReport()
    {
        return Invoice::whereNull('invoices.posted_date')
                ->join('places_management', 'invoices.management_id', '=', 'places_management.id')
                ->join('management', 'places_management.management_id', '=', 'management.id')
                ->select('invoices.*', DB::raw('(management.name) managementname'))->orderBy('number','desc')
                ->get()->toArray();
        
    }
    
    /**
     * Get Invoice Monitoring Report
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function getInvoiceMonitoringReport()
    {
        return Monitoring::whereNull('monitoring.removal_date')
                ->select('monitoring.*')
                ->with(['place.street'])
                ->with(['billto.management'])
                ->with(['monitoringinvoice'])->orderBy('account','asc')
                ->get()->toArray();
    }

    /**
     * get Invoice Timetotals Report
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function getInvoiceTimetotalsReport($request)
    {
        $totaltime=WorkorderTime::whereBetween('workorder_time.date', [$request->start_date, $request->end_date])
                ->join('workorders', 'workorder_time.workorder_id', '=', 'workorders.id')
                ->select('workorder_time.*')->orderBy('code','asc')
                ->get()->groupBy('code')->toArray();
        $final_array=array();
        $i=0;
        foreach($totaltime as $value)
        {
            $time=0;
            foreach($value as $timedata)
            {
                $time=$time+$timedata['time'];
                $code=$timedata['code'];
                
            }
            if(!empty($time))
            {
                $time=$this->secondToHourConvert($time);
                $final_array[$i]['totalhours'] = number_format((float)$time, 2, '.', '');
                $final_array[$i]['timecode'] = $code;
                $i++;
            } 
        }
        return $final_array;    
    }

    /**
     * second To Hour Convert
     *
     * @param  mixed $seconds
     *
     * @return void
     */
    public function secondToHourConvert($seconds)
    {
        return ($seconds/60);
    }

    /**
     * invoice Payment Detail
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function invoicePaymentDetail($request)
    {
        $management_id=array();
        if($request->has('invoice_no') && !empty($request->invoice_no)){
            
            $management=Invoice::where('number', 'like' , '%'.$request->invoice_no.'%')
                ->with(['billto'])->whereHas('billto')->get()->toArray();
                foreach($management as $data)
                {    
                    array_push($management_id,$data['billto']['id']);
                }
        } 
        
        if($request->has('management') && !empty($request->management)){
            $management=Management::where('id', $request->management)
                ->with(['management'])->whereHas('management')->get()->toArray();
                foreach($management as $data)
                {
                    foreach($data['management'] as $id)
                    {
                        array_push($management_id,$id['id']);
                    }
                }
        }

        $paid =  $request->paid == 1 ? 1 : 0;
        if($paid==1)
        {
            $invoice=Invoice::whereIn('invoices.bill_to_id', $management_id)
                            ->where(POSTED,1);
            
        }
        else
        {
            $invoice=Invoice::whereIn('bill_to_id', $management_id)
                            ->where(POSTED,1)
                            ->where('due','>',0);
        }

        return $invoice;
    }

    /**
     * get Invoice Payment Management
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function getInvoicePaymentManagement($request)
    {
        $management_id=array();
        $managementid['bill_to_id']=null;
        if($request->has('invoice_no') && !empty($request->invoice_no)){
            
            $management=Invoice::where('number', 'like' , '%'.$request->invoice_no.'%')
                ->with(['billto'])->whereHas('billto')->get()->toArray();
                foreach($management as $data)
                {    
                    array_push($management_id,$data['billto']['id']);
                }
        } 
        if(!empty($management_id))
        {
            $billtoid=Invoice::select('bill_to_id')->whereIn('invoices.bill_to_id', $management_id)
            ->with(['billto'])->whereHas('billto')->first()->toArray();

            if(!empty($billtoid))
            {
                $managementid['bill_to_id']=$billtoid['billto']['management_id'];
            }
        }
        

        return $managementid;        
    }

    /**
     * get Management Invoice Payment
     *
     * @param  mixed $request
     *
     * @return void
     */
    public function getManagementInvoicePayment($request)
    {
        $management_id = array();
        if($request->has('management') && !empty($request->management)){
            $management_id = PlacesManagement::where('management_id',$request->management)->pluck('id')->toArray();
        }
            $paid =  $request->has('paid') && $request->paid == 1 ? 1 : 0;
            if($paid==1)
            {
                $invoice=Payment::whereIn('invoices.bill_to_id', $management_id)
                        ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
                        ->join('places', 'invoices.place_id', '=', 'places.id')
                        ->select('payments.*', DB::raw('(places.suite) placesuite,(invoices.number) invoicenumber'));
                return array('type' => 'paid', 'invoice' => $invoice);
            }
            else
            {
                $invoice=Invoice::whereIn('bill_to_id', $management_id);
                $invoice->join('places', 'invoices.place_id', '=', 'places.id')
                        ->select('invoices.*', DB::raw('(places.suite) placesuite'));
                return array('type' => 'unpaid', 'invoice' => $invoice);
            }
            
    }

}

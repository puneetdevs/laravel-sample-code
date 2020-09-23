<?php

namespace App\Repositories;


use DB;
use App\Exceptions\Handler;
use App\Repositories\BaseRepository;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\QuoteStatus;
use DateTime;
use Auth;
use Carbon\Carbon;
/**
 * Class QuoteRepository.
 */
class QuoteRepository extends BaseRepository
{
    /**
     * @return string
     */
    public function model()
    {
        return Quote::class;
    }

    public function saveQuoteItem($quote_id, $request){
        if($request->has('quote_item') && !empty($request->quote_item)){
            $data = array();
            
            foreach($request->quote_item as $key=>$value){
                if(isset($value['id']) && !empty($value['id'])){
                    if(isset($value['part'])){unset($value['part']);} 
                    if(isset($value['gl'])){unset($value['gl']);} 
                    if(isset($value['created_at'])){unset($value['created_at']);} 
                    if(isset($value['updated_at'])){unset($value['updated_at']);} 
                    if(isset($value['deleted_at'])){unset($value['deleted_at']);}
                    QuoteItem::where('id',$value['id'])->update($value);
                }else{
                    $value['quote_id'] = $quote_id;
                    $value['company_id'] = Auth::user()->company_id;
                    $value['created_at'] = date('Y-m-d H:i:s');
                    $value['created_at'] = date('Y-m-d H:i:s');
                    $data[$key] = $value;
                }
            }
            if(!empty($data)){
                QuoteItem::insert($data);
            }
        }
    }

    public function getQuotePrint($quote_id){
        $quoteprice = Quote::select('subtotal','pst','gst','total')->where('id',$quote_id)->first()->toArray();
        $quoteitem = QuoteItem::where('quote_id',$quote_id)->where('company_id',Auth::user()->company_id)->get()->toArray();
        return array('quoteitem'=>$quoteitem,'pricedetail'=>$quoteprice);
    }
}

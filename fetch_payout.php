<?php /* Template Name: Fetch Batch Template */

namespace Sample;
require __DIR__ . '/Payouts-PHP-SDK/vendor/autoload.php';
use Sample\PayPalClient;
use PaypalPayoutsSDK\Payouts\PayoutsGetRequest;
use PayPalHttp\HttpException;

class FetchPayout
{ 
  /**
   * This function can be used to get payout. 
   */
  public static function FetchPayout($debug = false)
  {
    global $wpdb; 
      $batch_id = "";
      if(isset($_GET['id'])){
            $campID = $_GET['id'];
            $result_batch = $wpdb->get_results ( "SELECT * FROM wp_payout_batch_results WHERE `campaign_id` = $campID");
            if(count($result_batch)>0){
              foreach($result_batch as $res){
                  $batch_id = $res->batch_id;
              }
            }
       }else{
          echo "Missing campaign ID";
          exit();
      }  
      
      if($batch_id !=""){         
      
      $request = new PayoutsGetRequest($batch_id);
      $client = PayPalClient::client();
      $table_batch = 'wp_payout_batch_results';
      $table_batch_email = 'wp_payout_accounts';

      $response = $client->execute($request);
      //echo "<pre>";print_r($response);exit();
      $batch_status =  $response->result->batch_header->batch_status;
      if($batch_status != ""){
        $data = array(
             'status' => $batch_status
         );
         $data_where = array(
             'batch_id' => $batch_id
         );
         $format = array(
             '%s'
         );
         //I would not put it in a variable - just send it directly.
         $wpdb->update($table_batch, $data, $data_where);
      }
      $items = $response->result->items;
      foreach($items as $item){
        $payout_item_id = $item->payout_item_id;
        $transaction_id = $item->transaction_id;
        $transaction_status = $item->transaction_status;
        $email = $item->payout_item->receiver;
        //echo "<pre>";
        //print_r($item);        
        if(isset($item->errors)){
            $error = $item->errors->name;
        }else{
            $error = "";
        }
        if($email != ""){
            $data1 = array(
                 'status' => $transaction_status,
                 'error' => "$error",
                 'payout_item_id' => $payout_item_id,
                 'transaction_id' => $transaction_id
             );
             $data_where1 = array(
                 'receiver_email' => $email
             );
             $format = array(
                 '%s'
             );
             //I would not put it in a variable - just send it directly.
             $wpdb->update($table_batch_email, $data1, $data_where1);
        }
      }  
      }   
      echo "Payout Fetched Successfully: ".$batch_id."<br/><br/>";
      echo "<script>window.location.href = 'http://www.paypal.blade-marketing.com/wp-admin/admin.php?page=campaign';</script>";
      exit();  
  }
}
FetchPayout::FetchPayout(true);
?>
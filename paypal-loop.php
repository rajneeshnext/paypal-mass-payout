<?php /* Template Name: Payout Loop Template */

namespace Sample;
require __DIR__ . '/Payouts-PHP-SDK/vendor/autoload.php';
use Sample\PayPalClient;
use PaypalPayoutsSDK\Payouts\PayoutsPostRequest;
use PayPalHttp\HttpException;

class CreatePayoutSample
{

  public static function buildRequestBody()
  {
    global $wpdb;    
    if(isset($_GET['id'])){
        $campID = $_GET['id'];
        $result_batch = $wpdb->get_results ( "SELECT * FROM wp_payout_batch_results WHERE `campaign_id` = $campID and status='pending'");
        if(count($result_batch)>0){
          echo "PayPal Mass PayOut in progress";
          exit(); 
        }
    }else{
      echo "Missing campaign ID";
      exit();
    }
    $result = $wpdb->get_results ( "SELECT * FROM wp_payout_accounts WHERE `campaign_id` = $campID");
    if(count($result)>0){
      foreach($result as $res){
          //echo $res->receiver_email.'<br/>';
          $message = $res->message;
          $msg = str_replace("custom_field","$res->custom_field",$message);
          $items[] = array(
                      'recipient_type' => "EMAIL",
                      'receiver' => "$res->receiver_email",
                      'note' => "$msg",
                      'sender_item_id' => "giveaway_".$res->id,
                      'amount' => array(
                              'currency' => "$res->currency", 
                              'value' => "$res->amount", 
                      )
                  );          
      }
      $items_json = json_encode($items);
      return json_decode(
        '{
                  "sender_batch_header":
                  {
                    "email_subject": "Your weekly Fan rewards"
                  },
                  "items": '.$items_json.'
                }',
        true
      );
    }else{
      echo "No record found inside the campaign.";
      exit();
    }

  }
  /**
   * This function can be used to create payout. 
   */
  public static function CreatePayout($debug = false)
  {
    global $wpdb; 

    try {
            
      $request = new PayoutsPostRequest();
      $request->body = self::buildRequestBody();
      $client = PayPalClient::client();

      if(isset($_GET['id'])){
          $campID = $_GET['id'];
      }else{
        echo "Missing campaign ID";
        exit();
      }

      $response = $client->execute($request);
      if ($debug) {
        //print_r($response);
        //print "Status Code: {$response->statusCode}\n<br>";
        //print "Status: {$response->result->batch_header->batch_status}\n<br>";
        //print "Batch ID: {$response->result->batch_header->payout_batch_id}\n<br>";
        //print "Links:\n";
        foreach ($response->result->links as $link) {
          //print "\t{$link->rel}: {$link->href}\tCall Type: {$link->method}\n<br>";
        }
        // To toggle printing the whole response body comment/uncomment below line
        //echo json_encode($response->result, JSON_PRETTY_PRINT), "\n<br>";
      }
      $sql = $wpdb->prepare( "INSERT INTO wp_payout_batch_results (batch_id, campaign_id, status, message ) VALUES ( %s,%s, %s, %s )", "{$response->result->batch_header->payout_batch_id}", "$campID", "{$response->result->batch_header->batch_status}", "" );
      $wpdb->query($sql);
      //return $response;
    } catch (HttpException $e) {
      //Parse failure response
      //echo $e->getMessage() . "\n";
      $error = json_decode($e->getMessage());
      //echo $error->details[0]->issue . "\n";
      $error->message . "\n";
      echo $error->name . "\n";
      echo $error->debug_id . "\n";

      $sql = $wpdb->prepare( "INSERT INTO wp_payout_batch_results (batch_id, campaign_id, status, message ) VALUES ( %s,%s, %s, %s )", "", "$campID","{$error->name}", "$error->message" );
      $wpdb->query($sql);

    }
      echo "<script>window.location.href = 'http://www.paypal.blade-marketing.com/wp-admin/admin.php?page=campaign';</script>";
  }
}
CreatePayoutSample::CreatePayout(true);
?>
<?php /* Template Name: Download CSV */
   
   //http://localhost/auction/download-csv/
   global $wpdb;
    //Make sure this is spelled correct.
    $table = 'wp_campaign';
    $table_account = 'wp_payout_accounts';
   $status_sql = "";
   $lineData = array();
   if(isset($_GET['status']) && $_GET['status']!=""){
      $status_sql = ' and status="'.$_GET['status'].'"';
      $status = $_GET['status'];
   }

   $delimiter = ","; 
   $f = fopen('php://memory', 'w');
  
   if(isset($_GET['download']) && $_GET['download']>0){

      if(isset($_GET['id']) && $_GET['id']>0)
       {         
          $resultA = $wpdb->get_results ( "SELECT *  FROM  $table_account WHERE campaign_id =".$_GET['id'].$status_sql); 
          if(count($resultA)>0){
             foreach ( $resultA as $r )
             {
                $status = "pending";
                $lineData = array($r->receiver_email, $r->amount, $r->currency, $status); 
                fputcsv($f, $lineData, $delimiter); 
             }
            ob_start();     
      
            fseek($f, 0); 
            $filename = "campaign-users_" . date('Y-m-d') . ".csv";      
            // Set headers to download file rather than displayed 
            header('Content-Type: text/csv'); 
            header('Content-Disposition: attachment; filename="' . $filename . '";'); 
           
            //output all remaining data on a file pointer 
             fpassthru($f); 
             ob_flush();
          }else{
          }
       }else{
       }      
       exit();
   }
?>
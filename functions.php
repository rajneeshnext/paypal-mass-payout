<?php 
 
/* =============================================================================
   LOAD IN FRAMEWORK
   ========================================================================== */
 	
/* =============================================================================
   ADD YOUR CUSTOM CODE BELOW THIS LINE
   ========================================================================== */ 
 
 add_action('admin_menu', 'custom_menu');

 function custom_menu() { 
    add_menu_page( 
      'Campaign', 
      'Campaign', 
      'manage_options', 
      'campaign', 
      'campaign_callback_function', 
      'dashicons-media-spreadsheet' 
     );
    add_submenu_page( 'campaign', 'List', 'List', 'manage_options', 'campaign_list','campaignlist_callback_function');
}
 
function campaign_callback_function(){
   global $wpdb;
   //Make sure this is spelled correct.
   $table = 'wp_campaign';
   $table_account = 'wp_payout_accounts';
   $table_batch = 'wp_payout_batch_results';
   $select = "";
   $result_account = $wpdb->get_results ( "
    SELECT * 
    FROM  $table" );
   foreach ( $result_account as $r )
   {
      $select .=  '<option value="'.$r->id.'">'.$r->name.' ('.$r->id.')</option>';
   }         
   echo '<div class="wrap">
      <h1 class="wp-heading-inline">Manage Campaigns</h1><br/><br/><h2 class="title">Add / Edit</h2><form method="POST" action="?page=campaign" enctype="multipart/form-data">
 <table class="form-table" role="presentation"><tr class="user-user-login-wrap">
<th><label for="user_login">Select</label></th>
                  <td>
                     <input type="radio" name="campaign_type" value="new" class="regular-text"> Add new
                     <input placeholder="Campaign name" type="text" class="campaign_name" name="campaign_name" style="display:none;"/>
                     &nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="campaign_type" value="existing" class="regular-text"> Existing
                     <select class="selected_campaign" name="selected_campaign" style="display:none;"><option value="0">-Select Campaign-</option>'.$select.'</select>
                  </td>
</tr><tr class="user-user-login-wrap">
<th><label for="user_login">Choose file</label></th>
                  <td>
                     <input type="file" name="csv" class="regular-text"> 
                     <a href="'.get_theme_file_uri().'/payapl_payout_csv - Sheet1.csv" target="_blank">Sample File</a>
                  </td>
</tr><tr class="user-user-login-wrap">
<th><label for="user_login"></label></th>
                  <td>
                     <input type="submit" class="page-title-action" value="submit" />
                  </td>
</tr></table>
</form></div>
<script>
jQuery("input[name=campaign_type]").on("change", function() {  
  if(jQuery("input[name=campaign_type]:checked").val() == "new"){
      jQuery(".campaign_name").show();
      jQuery(".selected_campaign").hide();
  }else{
      jQuery(".selected_campaign").show(); 
      jQuery(".campaign_name").hide();
  }
});
</script>


';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    //output will check to see if it was posted correctly.
    $output = ['status' => 1];
    //You have to sanitize the fields first - for security reasons.
    if(isset($_POST["campaign_type"])){
      $campaign_type = sanitize_text_field($_POST["campaign_type"]);
    }
    if(isset($_POST["campaign_name"])){
      $campaign_name = sanitize_text_field($_POST["campaign_name"]);
    }
    if(isset($_POST["selected_campaign"])){
      $campaign_id = sanitize_text_field($_POST["selected_campaign"]);
    }
    
    $csv = array();
    $success = 0 ;
    $failed = 0 ;

    if($campaign_type == "existing" && $campaign_id!=""){

       $data = array(
           'name' => $campaign_name
       );
       $data_where = array(
           'id' => $campaign_id
       );
       $format = array(
           '%s'
       );
       //I would not put it in a variable - just send it directly.
       $wpdb->update($table, $data, $data_where);
       //Here is your post check - to console if it prints 2 - your good.
       //$output['status'] = 2;
       //check reference material to learn more about wp_send_json.
       //wp_send_json($output);
       // insert/update csv records

    }else{
      $data = array(
           'name' => $campaign_name
       );
       $format = array(
           '%s'
       );

       //I would not put it in a variable - just send it directly.
       $wpdb->insert( $table, $data, $format );
       $campaign_id = $wpdb->insert_id;       
       //Here is your post check - to console if it prints 2 - your good.
       //$output['status'] = 2;
       //check reference material to learn more about wp_send_json.
       //wp_send_json($output);
       // insert csv records
    }  
   if($campaign_id >0){
       // check there are no errors
       if($_FILES['csv']['error'] == 0){
          $name = $_FILES['csv']['name'];
          $ext = strtolower(end(explode('.', $_FILES['csv']['name'])));
          $type = $_FILES['csv']['type'];
          $tmpName = $_FILES['csv']['tmp_name'];

          // check the file is a csv
          if($ext === 'csv'){
              if(($handle = fopen($tmpName, 'r')) !== FALSE) {
                  // necessary if a large csv file
                  set_time_limit(0);

                  $row = 0;

                  while(($data_csv = fgetcsv($handle, 1000, ',')) !== FALSE) {
                      // number of fields in the csv
                      $col_count = count($data_csv);

                      $data_csv_row = array(
                          'receiver_email' => $data_csv[0],
                          'amount' => $data_csv[1],
                          'currency' => $data_csv[2],
                          'campaign_id' => $campaign_id,
                          'message' => $data_csv[4],
                          'custom_field' => $data_csv[3],
                      );
                      $format = array(
                          '%s',
                          '%s',
                          '%s',
                          '%d',
                          '%s',
                          '%s'
                      );

                      $result_check = $wpdb->insert( $table_account, $data_csv_row, $format );
                      //Here is your post check - to console if it prints 2 - your good.
                      $output['status'] = 2;
                      //check reference material to learn more about wp_send_json.
                      //wp_send_json($output);
                     if($result_check){
                        $success++;
                     }else{
                        $failed++;
                     }

                      // inc the row
                      $row++;
                  }
                  fclose($handle);
              }
          }
       } 
       echo '<div id="setting-error-settings_updated" class="notice notice-success settings-error is-dismissible"> 
         <p><strong>Data Imported.</strong></p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
            Total record inserted: '.$success.' <br/> Total record skipped: '. $failed.' <br/> Campaign ID: '.$campaign_id.'<br/><br>
         </div>';
   }else{
      echo '<div id="setting-error-settings_updated" class="notice notice-success settings-error is-dismissible"> 
      <p><strong style="color:red;">Error:</strong> Campaign with same name exist.</strong></p></div>';
   }

   
}


echo '<style>.payoutButton:hover{
    background-color: #2271b1;
    color: white;} .payoutButton {
    background-color: #2271b1;
    border: none;
    color: white;
    padding: 8px 15px;
    text-align: center;
    display: inline-block;
    font-size: 16px;
}</style><br/><br/><br/><h2 class="title">List</h2><table class="wp-list-table widefat fixed striped table-view-list pages">
      <tr>
          <th scope="col" id="author" class="manage-column column-author"><span>ID</span></th>  
          <th scope="col" id="author" class="manage-column column-author"><span>Campaign Name</span></th>          
          <th scope="col" id="author" class="manage-column column-author">Records</th>
          <th scope="col" id="author" class="manage-column column-author">Date Updated</th>
          <th scope="col" id="author" class="manage-column column-author">Action</th>
          <th scope="col" id="author" class="manage-column column-author">Status</th>
      </tr>';
$result_account = $wpdb->get_results ( "
    SELECT * 
    FROM  $table" );
foreach ( $result_account as $r )
{
   $resultA = $wpdb->get_results ( "SELECT *  FROM  $table_account WHERE campaign_id =".$r->id ); 
   $records = count($resultA);
   $resultBatch = $wpdb->get_results ( "SELECT *  FROM  $table_batch WHERE campaign_id =".$r->id ); 
   if(isset($resultBatch[0]->status)){
        $recordStatus = $resultBatch[0]->status ? $resultBatch[0]->status: "-";
        $mass_payout_link = '';
	    $mass_payout_fetch_status = '| <a class="payoutButton1" href="'.home_url().'/fetch-payout/?id='.$r->id.'">Latest Status</a>';
   }
   else{
        $recordStatus = "-";
        $mass_payout_link = '| <a class="payoutButton1" href="'.home_url().'/payout-loop/?id='.$r->id.'">Mass PayOut</a>';
	    $mass_payout_fetch_status = '';
   }
   if($resultBatch[0]->message != ""){
	   $recordMsg = "<br/>".$resultBatch[0]->message;
   }else{
	   $recordMsg = "";
   }

   echo '<tr>
         <td><span>'.$r->id.'</span></td>
         <td><span>'.$r->name.'</span></td>
         <td><span>'.$records.' </span></td>
         <td><span>'.$r->date_updated.'</span></td>
         <td><span> <a href="javascript:void(0);" camp_id="'.$r->id.'" class="delete_campaign">Delete</a> | <a href="?page=campaign_list&id='.$r->id.'" target="_blank">View</a> '.$mass_payout_link.$mass_payout_fetch_status.'</span></td>
         <td>'.$recordStatus.$recordMsg.'</a></td>
      </tr> ';
}
echo '</table>
<script>
   jQuery("body").on("click", ".delete_campaign", function(e){
      if (confirm("Campaign and its records will be deleted?") == true) {
        var camp_id = jQuery(this).attr("camp_id");
        jQuery.ajax({
            type : "POST",
            dataType : "json",
            url : "'.admin_url("admin-ajax.php").'",
            data : {action: "delete_campaign", camp_id: camp_id},
            success: function(response) {
                alert("Campaign Deleted");
				location.reload(true);
            }
        });
      } else {
      }        
    });
</script>';
   
}


function campaignlist_callback_function(){
    global $wpdb;
    //Make sure this is spelled correct.
    $table = 'wp_campaign';
    $table_account = 'wp_payout_accounts';
    $campaignName="";
    $status="";
   $status_sql = "";
   $lineData = array();
   $campaign_id="";
   if(isset($_GET['status']) && $_GET['status']!=""){
      $status_sql = ' and status="'.$_GET['status'].'"';
      $status = $_GET['status'];
      $status_sql = " and UPPER(status) LIKE UPPER('$status')";
   }

   if(isset($_GET['id']) && $_GET['id']>0)
   {
       $campaign_id = $_GET['id']; 
       $result_account = $wpdb->get_results ( "
       SELECT * 
       FROM  $table where id =".$_GET['id']);
       foreach ( $result_account as $rr )
       {
         $campaignName = $rr->name;
       }
   }

    $select = "";
    $select_option= "";
    $arr_status = array("Success","Claimed","Unclaimed");
    if($status !=""){
        $where = " where LOWER(status) LIKE LOWER('%$status%')";
    }else{
        $where = "";
    }

    $result_account = $wpdb->get_results ( "
    SELECT * 
    FROM  $table" );
    foreach ( $result_account as $r )
    {
      if($campaign_id == $r->id){
         $selected = "selected";
      }else{
         $selected = "";
      }
      $select .=  '<option '.$selected.' value="'.$r->id.'">'.$r->name.' ('.$r->id.')</option>';
    } 
    foreach( $arr_status as $arr_stat)
    {
      if($status == $arr_stat){
         $selected1 = "selected";
      }else{
         $selected1 = "";
      }
      $select_option .=  '<option '.$selected1.' value="'.$arr_stat.'">'.$arr_stat.'</option>';
    } 
    //$select_option = '<option value="0">Pending</option><option value="1">Claimed</option><option value="2">Unclaimed</option>';
    echo '<br/><div style="text-align: center;margin-right: 20px;"><h3>Search: </h3><form method="get">
    <input type="hidden" name="page" value="campaign_list" /><select class="selected_campaign" name="id" ><option value="0">-Select Campaign-</option>'.$select.'</select>&nbsp;&nbsp;&nbsp;<select class="selected_status" name="status" ><option value="">-Select Status-</option>'.$select_option.'</select></form></div>
    <script>
    jQuery(document).ready(function() {
        jQuery(".selected_campaign, .selected_status").on("change", function() {
           this.form.submit();
        });
      });
      </script>';

   if(isset($_GET['id']) && $_GET['id']>0)
   {         
      $resultA = $wpdb->get_results ( "SELECT *  FROM  $table_account WHERE campaign_id =".$_GET['id'].$status_sql); 
      if(count($resultA)>0){
            echo '<strong>Total records: </strong>'.count($resultA).'<br/><br/><h2 class="title">Campaign: '.$campaignName.'</h2><a style="margin-right: 20px;float:right;margin-bottom: 20px;position: absolute;margin-top: -28px;right: 0;" href="'.home_url().'/download-csv?id='.$campaign_id.'&status='.$status.'&download=1">Download Results</a><table class="wp-list-table widefat fixed striped table-view-list pages">
          <tr>
              <th scope="col" id="author" class="manage-column column-author"><span>Transaction ID</span></th>       
              <th scope="col" id="author" class="manage-column column-author">Email</th>
              <th scope="col" id="author" class="manage-column column-author">Amount</th>
			  <th scope="col" id="author" class="manage-column column-author">Message</th>
              <th scope="col" id="author" class="manage-column column-author">Currency</th>
              <th scope="col" id="author" class="manage-column column-author">Status</th>
          </tr>';
         foreach ( $resultA as $r )
         {
			$message = $r->message;
            $msg = str_replace("custom_field","<strong>$r->custom_field</strong>",$message);
            $status = "pending";
            echo '<tr>
               <td><span>'.$r->transaction_id.'</span></td>
               <td><span>'.$r->receiver_email.'</span></td>
			   <td><span>'.$r->amount.' </span></td>
			   <td><span>'.$msg.' </span></td>
               <td><span>'.$r->currency.'</span></td>
               <td><span>'.$r->status.'</span></td>
            </tr> ';
         }
      }else{
         echo '<tr>
            <td><span> No record found</span></td>
         </tr> ';
      }
   }else{
      echo '<tr>
            <td><span> No record found</span></td>
         </tr> ';
   }
   echo '</table>';
   
} 

add_action( 'wp_ajax_nopriv_delete_campaign', 'delete_campaign' );
add_action( 'wp_ajax_delete_campaign', 'delete_campaign' );

function delete_campaign() {   
    global $wpdb;
    //Make sure this is spelled correct.
    $table = 'wp_campaign';
    $table_account = 'wp_payout_accounts';

    echo $id = $_POST['camp_id'];
    $wpdb->delete( $table, array( 'id' => $id ) );
    $wpdb->delete( $table_account, array( 'campaign_id' => $id ) );
}
?>

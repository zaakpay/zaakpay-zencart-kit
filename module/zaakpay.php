<?php


/*

  Zencart

 

*/

class Checksum {

	var $hash, $checksum, $secret_key, $all;
	
	
	function calculateChecksum($secret_key, $all) {
			
		
		$hash = hash_hmac('sha256', $all , $secret_key);
		$checksum = $hash;
		
		return $checksum;
	}
	
	 function verifyChecksum($checksum, $all, $secret) {
		
		$hash = hash_hmac('sha256', $all , $secret);
		$cal_checksum = $hash;
		$bool = 0;
		if($checksum == $cal_checksum)	{
			$bool = 1;
		}

		return $bool;
	}

	
	function sanitizedParam($param) {
			
			$pattern[0] = "%,%";
	        $pattern[1] = "%#%";
	        $pattern[2] = "%\(%";
       		$pattern[3] = "%\)%";
	        $pattern[4] = "%\{%";
	        $pattern[5] = "%\}%";
	        $pattern[6] = "%<%";
	        $pattern[7] = "%>%";
	        $pattern[8] = "%`%";
	        $pattern[9] = "%!%";
	        $pattern[10] = "%\\$%";
	        $pattern[11] = "%\%%";
	        $pattern[12] = "%\^%";
	        $pattern[13] = "%=%";
	        $pattern[14] = "%\+%";
	        $pattern[15] = "%\|%";
	        $pattern[16] = "%\\\%";
	        $pattern[17] = "%:%";
	        $pattern[18] = "%'%";
	        $pattern[19] = "%\"%";
	        $pattern[20] = "%;%";
	        $pattern[21] = "%~%";
	        $pattern[22] = "%\[%";
	        $pattern[23] = "%\]%";
	        $pattern[24] = "%\*%";
	        $pattern[25] = "%&%";
        	$sanitizedParam = preg_replace($pattern, "", $param);
		
		return $sanitizedParam;
	}
	
	function sanitizedURL($param) {
	
			$pattern[0] = "%,%";
	        $pattern[1] = "%\(%";
       		$pattern[2] = "%\)%";
	        $pattern[3] = "%\{%";
	        $pattern[4] = "%\}%";
	        $pattern[5] = "%<%";
	        $pattern[6] = "%>%";
	        $pattern[7] = "%`%";
	        $pattern[8] = "%!%";
	        $pattern[9] = "%\\$%";
	        $pattern[10] = "%\%%";
	        $pattern[11] = "%\^%";
	        $pattern[12] = "%\+%";
	        $pattern[13] = "%\|%";
	        $pattern[14] = "%\\\%";
	        $pattern[15] = "%'%";
	        $pattern[16] = "%\"%";
	        $pattern[17] = "%;%";
	        $pattern[18] = "%~%";
	        $pattern[19] = "%\[%";
	        $pattern[20] = "%\]%";
	        $pattern[21] = "%\*%";
        	$sanitizedParam = preg_replace($pattern, "", $param);
			
		return $sanitizedParam;
	}
	

	
}

  class zaakpay extends base{

    var $code, $title, $description, $enabled;


	// class constructor

    function zaakpay() {

      global $order;

      $this->code = 'zaakpay';

      $this->title = MODULE_PAYMENT_ZAAKPAY_TEXT_TITLE;

      $this->description = MODULE_PAYMENT_ZAAKPAY_TEXT_DESCRIPTION;

      $this->sort_order = MODULE_PAYMENT_ZAAKPAY_SORT_ORDER;

      $this->enabled = ((MODULE_PAYMENT_ZAAKPAY_STATUS == 'True') ? true : false);

      $this->form_action_url = 'https://api.zaakpay.com/transact';

    }



    function update_status() {
		/* Check whether the zones/geo_zones is valid */
		global $order;
		if (((int) MODULE_PAYMENT_ZAAKPAY_VALID_ZONE > 0)) {
			$checkFlag = false;
			global $db;
			$sql = "select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_ZAAKPAY_VALID_ZONE . "' and zone_country_id = '".$order->delivery['country']['id']."' order by zone_id";
			$result = $db->Execute($sql);
			if($result) while(!$result->EOF) {
				if ($result->fields['zone_id'] < 1) {
					$checkFlag = true;
					break;
				}
				elseif ($result->fields['zone_id'] == $order->delivery['zone_id']) {
					$checkFlag = true;
					break;
				}
				// Move Next
			}
			
			/* Set whether this should be valid or not */
			if ($checkFlag == false) {
				$this->enabled = false;
			}
		}
	}


	function javascript_validation() {
	
	  return '';
	}



    function selection() {

      global $order;

      $selection = array('id' => $this->code,

						'module' => MODULE_PAYMENT_ZAAKPAY_TEXT_LOGO,
						'icon' => MODULE_PAYMENT_ZAAKPAY_TEXT_LOGO
						);
		
		return $selection;

    }


	function pre_confirmation_check() {
	
	  return false;
	}

	
	function confirmation() {

      $confirmation = array ('title' => $this->description);			 
	  return $confirmation;

    }

	function process_button() {

      global $order,$order_total_modules,$currencies,$mode;

	  $temp=mysql_query("select value from currencies where code='INR'")or die(mysql_error());
	
	  $currency_value=mysql_fetch_array($temp);
	  
	
	 $log = MODULE_PAYMENT_ZAAKPAY_LOG ;
	  if(MODULE_PAYMENT_ZAAKPAY_TESTMODE == "TEST")
	  {
		$mode = 0;
	  }
	  else
	  {
		$mode = 1;
	  }
	  
	 $order_id = date('YmdHis');
	
   	  $post_variables = array(
	  
			"securityToken" => $_SESSION['securityToken'],
			"merchantIdentifier" => MODULE_PAYMENT_ZAAKPAY_MERCHANTID,
			"orderId" => $order_id,
			"returnUrl" => zen_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL'),
			"buyerEmail" => $order->customer['email_address'],
			"buyerFirstName" => $order->customer['firstname'],
			"buyerLastName" => $order->customer['lastname'],
			"buyerAddress" => $order->customer['street_address'],
			"buyerCity" => $order->customer['city'],
			"buyerState" => $order->customer['state'],
			"buyerCountry" => $order->customer['country']['title'],
			"buyerPincode" =>  $order->customer['postcode'],
			"buyerPhoneNumber" => $order->customer['telephone'],
			"currency" => 'INR',	//$order->customer['country']['iso_code_2'],
			"amount" => 100 * (number_format(($order->info['total'] * $currency_value[0]),2,'.','')),	//should be in paisa
			"productDescription" => 'Order ID'." ".$order_id,
		    "shipToAddress" => $order->delivery['street_address'],	
			"shipToCity" => $order->delivery['city'],			
			"shipToState" => $order->delivery['state'],
			"shipToCountry" => $order->delivery['country']['title'],
		    "shipToPincode" => $order->delivery['postcode'],
		    "shipToPhoneNumber" => $order->delivery['telephone'],
			"shipToFirstname" => $order->delivery['firstname'],
			"shipToLastname" => $order->delivery['lastname'],
			"txnType" => 1,
			"zpPayOption" => 1,
			"mode" => $mode,
			"merchantIpAddress" => "127.0.0.1",  	//Merchant Ip Address
			"purpose" => 1,
			"txnDate" => date('Y-m-d'),
			
		);
		

		$all = '';
		foreach($post_variables as $name => $value)	{
			if($name != 'checksum') {
				$all .= "'";
				if ($name == 'returnUrl') {
					$all .= Checksum::sanitizedURL($value);
				} else {				
					
					$all .= Checksum::sanitizedParam($value);
				}
				$all .= "'";
			}
		}
		
		$secret_key = MODULE_PAYMENT_ZAAKPAY_SECRET_KEY;
		
		if($log == "Yes")
		{
			//define('All Params','$all');
			error_log("All Params : ".$all);
			error_log("Zaakpay Secret Key : ".$secret_key);
		
		}
		
		$checksum = Checksum::calculateChecksum($secret_key,$all);
		 
		 
    $process_button_string = zen_draw_hidden_field('merchantIdentifier', MODULE_PAYMENT_ZAAKPAY_MERCHANTID) . 
	
							 //zen_draw_hidden_field('securityToken', $_SESSION['securityToken']);
	                           
							 zen_draw_hidden_field('orderId', date('YmdHis')) .
							 
							 zen_draw_hidden_field('returnUrl', zen_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL')) .
							 
							 zen_draw_hidden_field('buyerEmail', $order->customer['email_address']) .

                             zen_draw_hidden_field('buyerFirstName', $order->customer['firstname']) . 
							 
							 zen_draw_hidden_field('buyerLastName', $order->customer['lastname']) . 

                             zen_draw_hidden_field('buyerAddress', Checksum::sanitizedParam($order->customer['street_address'])) .

                             zen_draw_hidden_field('buyerCity', $order->customer['city']) .

                             zen_draw_hidden_field('buyerState', $order->customer['state']) .
							 
							 zen_draw_hidden_field('buyerCountry', $order->customer['country']['title']) .
							 
							 zen_draw_hidden_field('buyerPincode', $order->customer['postcode']) .

							 zen_draw_hidden_field('buyerPhoneNumber', $order->customer['telephone']) .
							 
							 zen_draw_hidden_field('currency', 'INR') .	//$order->customer['country']['iso_code_2']) .
							 
							 zen_draw_hidden_field('amount', 100 * (number_format(($order->info['total'] * $currency_value[0]),2,'.',''))).
							 
							 zen_draw_hidden_field('productDescription','Order ID'." ".$order_id).
							 
							 zen_draw_hidden_field('shipToAddress', Checksum::sanitizedParam($order->delivery['street_address'])) .
							 
							 zen_draw_hidden_field('shipToCity', $order->delivery['city']) .

                             zen_draw_hidden_field('shipToState', $order->delivery['state']) .
							 
							 zen_draw_hidden_field('shipToCountry', $order->delivery['country']['title']) .

                             zen_draw_hidden_field('shipToPincode', $order->delivery['postcode']) .

							 zen_draw_hidden_field('shipToPhoneNumber', $order->delivery['telephone']) .

							 zen_draw_hidden_field('shipToFirstname', $order->delivery['firstname']) .

							 zen_draw_hidden_field('shipToLastname', $order->delivery['lastname']) .

							 zen_draw_hidden_field('txnType', 1) .
							 
							 zen_draw_hidden_field('zpPayOption', 1) .
							 
							 zen_draw_hidden_field('mode', "$mode") .
							 
							 zen_draw_hidden_field('merchantIpAddress', '127.0.0.1') .
							 
							 zen_draw_hidden_field('purpose', 1) .
							 
							 zen_draw_hidden_field('txnDate', date('Y-m-d')) .
							 
							 zen_draw_hidden_field('checksum', $checksum); 
							 
							 
		return $process_button_string;

    }

	
	
 function before_process() {
 
	 global $messageStack;
 
	  $order_id = $_POST['orderId'];
	  $res_code = $_POST['responseCode'];
	  $res_desc = $_POST['responseDescription'];
	  $checksum_recv = $_POST['checksum'];
	  
	  $all = ("'". $order_id ."''". $res_code ."''". $res_desc."'");

	  $secret_key = MODULE_PAYMENT_ZAAKPAY_SECRET_KEY;
	  
	  $bool = 0;
	  $bool = Checksum::verifyChecksum($checksum_recv, $all, $secret_key);
	  
	  if($bool == "1")
	  {
		if($res_code != "100")
		{
			$messageStack->add_session('checkout_payment', MODULE_PAYMENT_ZAAKPAY_TEXT_ERROR_MESSAGE, 'error');
			zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL', true, false));
		}
	  }
		
	  else
	  {
		$messageStack->add_session('checkout_payment', MODULE_PAYMENT_ZAAKPAY_TEXT_ERROR, 'error');
		zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL', true, false));
			  
	  }
	 
    }


	function after_process() {

      return false;

    }

    function check() {

      global $db;
	    
      if (!isset($this->_check)) {

        $check_query = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_ZAAKPAY_STATUS'");

        $this->_check = $check_query->RecordCount();

      }

      return $this->_check;

    }



    function install() {
	
	  global $db;

      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable ZAAKPAY Payment Module', 'MODULE_PAYMENT_ZAAKPAY_STATUS', 'True', 'Do you want to accept ZAAKPAY payments?', '6', '0', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");

      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Merchant User ID', 'MODULE_PAYMENT_ZAAKPAY_MERCHANTID', 'Merchant Id', 'Your Zaakpay Merchant ID', '5087', '0', now())");

      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Secret key', 'MODULE_PAYMENT_ZAAKPAY_SECRET_KEY', 'Secret Key', 'Your Zaakpay Secret Key', '6', '0', now())");	  

      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Test Mode', 'MODULE_PAYMENT_ZAAKPAY_TESTMODE', 'TEST', 'Select Mode you want to work on', '6', '0', 'zen_cfg_select_option(array(\'TEST\', \'LIVE\'), ', now())");
	  
	  $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Logging Params', 'MODULE_PAYMENT_ZAAKPAY_LOG', 'Yes', 'Want to log params posting to Zaakpay ..?', '6', '0', 'zen_cfg_select_option(array(\'Yes\', \'No\'), ', now())");
	  
	  $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of display', 'MODULE_PAYMENT_ZAAKPAY_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6','0', now())");

    }



    function remove() {
	
	  global $db;

     $db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");

    }



    function keys() {

      return array('MODULE_PAYMENT_ZAAKPAY_STATUS', 'MODULE_PAYMENT_ZAAKPAY_MERCHANTID', 'MODULE_PAYMENT_ZAAKPAY_SECRET_KEY', 'MODULE_PAYMENT_ZAAKPAY_TESTMODE', 'MODULE_PAYMENT_ZAAKPAY_LOG');

    }

  }

?>
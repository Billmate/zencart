<?php
/**
 *  Copyright 2010 BILLMATEBANK AB. All rights reserved.
 *
 *  Redistribution and use in source and binary forms, with or without modification, are
 *  permitted provided that the following conditions are met:
 *
 *     1. Redistributions of source code must retain the above copyright notice, this list of
 *        conditions and the following disclaimer.
 *
 *     2. Redistributions in binary form must reproduce the above copyright notice, this list
 *        of conditions and the following disclaimer in the documentation and/or other materials
 *        provided with the distribution.
 *
 *  THIS SOFTWARE IS PROVIDED BY BILLMATEBANK AB "AS IS" AND ANY EXPRESS OR IMPLIED
 *  WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND
 *  FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL BILLMATEBANK AB OR
 *  CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 *  CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 *  SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
 *  ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 *  NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF
 *  ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *  The views and conclusions contained in the software and documentation are those of the
 *  authors and should not be interpreted as representing official policies, either expressed
 *  or implied, of BILLMATEBANK AB.
 *
 */

error_reporting(E_ERROR);
ini_set('display_errors', true);

$includeLoopVariable = $i;

@include_once(DIR_FS_CATALOG . DIR_WS_CLASSES . 'billmate/billmate_lang.php');
if(!class_exists('Encoding',false)){
    require_once DIR_FS_CATALOG . DIR_WS_CLASSES.'billmate/utf8.php';
	require_once(DIR_FS_CATALOG . DIR_WS_CLASSES . 'billmate/billmate_api.php');
	require_once(DIR_FS_CATALOG . DIR_WS_CLASSES . 'billmate/billmateutils.php');
}

class billmatebank {
    var $code, $title, $description, $enabled, $billmatebank_livemode, $billmatebank_testmode, $jQuery, $form_action_url;

    // class constructor
    function billmatebank() {
        global $order, $currency, $currencies, $customer_id, $customer_country_id, $billmatebank_livemode, $billmatebank_testmode, $db;
        $this->jQuery = true;
        $this->code = 'billmatebank';

        if(strpos($_SERVER['SCRIPT_FILENAME'],'admin')) {
            $this->title = MODULE_PAYMENT_BILLMATEBANK_TEXT_TITLE;
        }
        else {
            $this->title = MODULE_PAYMENT_BILLMATEBANK_TEXT_TITLE;
        }

        $this->billmatebank_testmode = false;
        if ((MODULE_PAYMENT_BILLMATEBANK_TESTMODE == 'True')) {
            $this->title .= ' '.MODULE_PAYMENT_BILLMATEBANK_LANG_TESTMODE;
            $this->billmatebank_testmode = true;
        }

        if (MODULE_PAYMENT_BILLMATEBANK_TESTMODE == 'True') {
            $this->form_action_url = 'https://cardpay.billmate.se/pay/test';
        } else {
            $this->form_action_url = 'https://cardpay.billmate.se/pay';
        }
		
				if( $order->billing == null ){
					$billing = $_SESSION['billmate_billing'];
				}else{
					$billing = $_SESSION['billmate_billing'] = $order->billing;
				}
		

        (MODULE_PAYMENT_BILLMATEBANK_TESTMODE != 'True') ? $this->billmatebank_livemode = true : $this->billmatebank_livemode = false;

        $this->description = MODULE_PAYMENT_BILLMATEBANK_TEXT_DESCRIPTION . "<br />Version: 1.2";
        $this->enabled = ((MODULE_PAYMENT_BILLMATEBANK_STATUS == 'True') ?
                true : false);

        $currency = $_SESSION['currency'];
				$currencyValid = array('SE','SEK','EU', 'EUR','NOK','NO', 'SE','sek','eu', 'eur','nok','no' );
        $countryValid  = array('SE', 'DK', 'FI', 'NO','se', 'dk', 'fi', 'no');
        $disabled_countries = explode(',',
                                trim( 
                                    strtolower(MODULE_PAYMENT_BILLMATEBANK_DISABLED_COUNTRYIES),
                                    ','
                                ).','.
                                trim( 
                                    strtoupper(MODULE_PAYMENT_BILLMATEBANK_DISABLED_COUNTRYIES),
                                    ','
                                 )
                              );
		if( IS_ADMIN_FLAG == false ) {
			if(is_array($billing)) {
				if(in_array($billing['country']['iso_code_2'],$disabled_countries)) {
					$this->enabled = false;
				}
			}
			else {
				$query = ("SELECT countries_iso_code_2 FROM countries WHERE countries_id = " . (int)$_SESSION['customer_country_id']);
				$result = $db->Execute($query);
		
				if(is_array($result->fields)) {
					if(in_array($result->fields['countries_iso_code_2'],$disabled_countries)) {
						$this->enabled = false;
					}
					$this->enabled = $this->enabled && !in_array($result->fields['countries_iso_code_2'],$disabled_countries);
				}
				else {
					$this->enabled = false;
				}
			}
			
			if(is_object($currencies)) {
				$er = $currencies->get_value($currency);
			}
			else {
				$er = 1;
			}
	
			if ($order->info['total']*$er > MODULE_PAYMENT_BILLMATEBANK_ORDER_LIMIT)
				$this->enabled = false;
	
			if ((int)MODULE_PAYMENT_BILLMATEBANK_ORDER_STATUS_ID > 0)
				$this->order_status = MODULE_PAYMENT_BILLMATEBANK_ORDER_STATUS_ID;
	
			if (is_object($order))
				$this->update_status();
		}
		$this->sort_order = MODULE_PAYMENT_BILLMATEBANK_SORT_ORDER;
    }

    // class methods
    function update_status() {
        global $order, $db;

        if ($this->enabled == true && (int)MODULE_PAYMENT_BILLMATEBANK_ZONE > 0) {
            $check_flag = false;
            $check_query = ("select zone_id from " .
                    TABLE_ZONES_TO_GEO_ZONES .
                    " where geo_zone_id = '" .
                    MODULE_PAYMENT_BILLMATEBANK_ZONE .
                    "' and zone_country_id = '" .
                    $order->billing['country']['id'] .
                    "' order by zone_id");
			$check = $db->Execute($check_query);

            while (!$check->EOF) {
                if ($check->fields['zone_id'] < 1) {
                    $check_flag = true;
                    break;
                }
                elseif ($check->fields['zone_id'] == $order->billing['zone_id']) {
                    $check_flag = true;
                    break;
                }
	            $check->MoveNext();
			}

            if ($check_flag == false)
                $this->enabled = false;
        }
    }

    function javascript_validation() {
        return false;
    }

    function selection() {
        global $order, $customer_id, $currencies, $currency, $user_billing, $db;

        $find_personnummer_field_query =
                $db->Execute("show columns from " . TABLE_CUSTOMERS);

        $has_personnummer = false;
        $has_dob = false;

        while(!$find_personnummer_field_query->EOF) {
            if ($find_personnummer_field_query->fields['Field'] == "customers_personnummer")
                $has_personnummer = true;
            if ($find_personnummer_field_query->fields['Field'] == "customers_dob")
                $has_dob = true;
        	$find_personnummer_field_query->MoveNext();
		}

        if ($has_personnummer) {
            $customer = $db->Execute("select customers_personnummer from " .
				                    TABLE_CUSTOMERS . " where customers_id = '" . (int)$customer_id."'");

            $personnummer = $customer->fields['customers_personnummer'];
        }
        else if ($has_dob) {
            $customer = $db->Execute("select DATE_FORMAT(customers_dob, '%Y%m%d') AS customers_dob from " .
                    				TABLE_CUSTOMERS . " where customers_id = '" . (int)$customer_id."'");

            $personnummer = $customer->fields['customers_dob'];
        }
        else {
            $personnummer = "";
        }

        $personnummer = "";

        $er = $currencies->get_value($currency);
        $user_billing = $_SESSION['user_billing'];

        empty($user_billing['billmatebank_pnum']) ? $billmatebank_pnum = $personnummer : $billmatebank_pnum = $user_billing['billmatebank_pnum'];
        empty($user_billing['billmatebank_phone']) ? $billmatebank_phone = $order->customer['telephone'] : $billmatebank_phone = $user_billing['billmatebank_phone'];

        //Fade in/fade out code for the module
        $fields[] = array('title' => BILLMATE_LANG_SE_IMGBANK, 'field' => '');

        return array('id' => $this->code,
                'module' => $this->title,
                'fields' => $fields);
    }

    function pre_confirmation_check() {
        global $billmatebank_testmode, $billmatebank_livemode, $order, $GA_OLD, $BILL_SE_PNO, $user_billing;
        //Store values into Session
		$_SESSION['user_billing'] = $user_billing;

        $eid = MODULE_PAYMENT_BILLMATEBANK_EID;
        $secret = MODULE_PAYMENT_BILLMATEBANK_SECRET;
    }

    function confirmation() {
        return array('title' => MODULE_PAYMENT_BILLMATEBANK_TEXT_CONFIRM_DESCRIPTION);
    }

    function process_button() {
        global $order, $order_total_modules, $billmatebank_ot, $shipping, $db, $languages_id;
				
        $counter = 1;
        $process_button_string= '<script type="text/javascript">jQuery(".hiddenFields").remove();document.getElementsByName(\'securityToken\').item(0).remove();</script>';

				$sql = "select code from " . TABLE_LANGUAGES . " where directory = '{$_SESSION['language']}'";
        $check_language = $db->Execute($sql);
				$languageCode = strtoupper( $check_language->fields['code'] );

				$languageCode = $languageCode == 'DA' ? 'DK' : $languageCode;
				$languageCode = $languageCode == 'SV' ? 'SE' : $languageCode;
				$languageCode = $languageCode == 'EN' ? 'GB' : $languageCode;
    
        $eid = MODULE_PAYMENT_BILLMATEBANK_EID;
        $secret = substr( MODULE_PAYMENT_BILLMATEBANK_SECRET ,0 ,12 );
		unset($_SESSION['bank_api_called']);
        $_ = array();
				$_['merchant_id']   = $eid;
				$_['currency']      = $order->info['currency'];
				$_['order_id']      = time();
				$_['callback_url'] = 'http://api.billmate.se/callback.php';
				$_['amount']        = round($order->info['total'], 2)*100;
				$_['accept_url']    = zen_href_link(FILENAME_CHECKOUT_PROCESS);
				$_['cancel_url']    = zen_href_link(FILENAME_CHECKOUT_PAYMENT);
				$_['pay_method']    = 'BANK';
				$_['return_method'] = 'GET';
				$_['language']      = $languageCode;
				$_['capture_now']   = 'YES';
				
       	$mac_str = $_['accept_url'] . $_['amount'] . $_['callback_url'] . $_['cancel_url'] . $_['capture_now'] . $_['currency'] . $_['language'] . $_['merchant_id'] . $_['order_id'] . $_['pay_method'] . $_['return_method'] . $secret;
        $mac = hash( "sha256", $mac_str );

		$_['mac']					= $mac;

        foreach($_ as $key => $col ){
            $process_button_string.=zen_draw_hidden_field($key,$col);
        }
        $order_totals = $order_total_modules->modules;

        if (is_array($order_totals)) {
            reset($order_totals);
            $j = 0;
            $table = preg_split("/[,]/", MODULE_PAYMENT_BILLMATE_ORDER_TOTAL_IGNORE);

            while (list(, $value) = each($order_totals)) {
				
                $class = substr($value, 0, strrpos($value, '.'));
				if( in_array($class, array('ot_subtotal','ot_total')) ) continue;

                if (empty($GLOBALS[$class]->output)) {
                    continue;
                }
                $code = $GLOBALS[$class]->code;
                $ignore=false;


                for ($i=0 ; $i<sizeof($table) && $ignore == false ; $i++) {
                    if ($table[$i] == $code) {
                        $ignore = true;
                    }
                }

                $size = sizeof($GLOBALS[$class]->output);

                if ($ignore == false && $size > 0) {
                    $billmatebank_ot['code_size_'.$j] = $size;
                    for ($i=0; $i<$size; $i++) {
                        $billmatebank_ot['title_'.$j.'_'.$i] = html_entity_decode($GLOBALS[$class]->output[$i]['title']);

                        $billmatebank_ot['text_'.$j.'_'.$i] = $GLOBALS[$class]->output[$i]['text'];
                        if (is_numeric($GLOBALS[$class]->deduction) &&
                                $GLOBALS[$class]->deduction > 0) {
                            $billmatebank_ot['value_'.$j.'_'.$i] = -$GLOBALS[$class]->deduction;
                        }
                        else {
                            $billmatebank_ot['value_'.$j.'_'.$i] = $GLOBALS[$class]->output[$i]['value'];

                            // Add tax rate for shipping address and invoice fee
                            if ($class == 'ot_shipping') {
                                //Set Shipping VAT
                                $shipping_id = @explode('_', $_SESSION['shipping']['id']);
                                $tax_class = @$GLOBALS[$shipping_id[0]]->tax_class;
                                $tax_rate = 0;
                                if($tax_class > 0) {
                                    $tax_rate = zen_get_tax_rate($tax_class, $order->billing['country']['id'], ($order->billing['zone_id'] > 0) ? $order->billing['zone_id'] : null);
                                }
                                $billmatebank_ot['tax_rate_'.$j.'_'.$i] = $tax_rate;
                            } else {
                                $billmatebank_ot['tax_rate_'.$j.'_'.$i] = $GLOBALS[$class]->output[$i]['tax_rate'];
                            }
                        }

                        $billmatebank_ot['code_'.$j.'_'.$i] = $GLOBALS[$class]->code;
                    }
                    $j += 1;
                }
            }
            $billmatebank_ot['code_entries'] = $j;
        }

        $_SESSION['billmatebank_ot'] = $billmatebank_ot;
		$this->doInvoice();
        return $process_button_string;
    }
		function doInvoice(){
		     global $order, $customer_id, $currency, $currencies, $sendto, $billto,
	               $billmatebank_ot, $billmatebank_livemode, $billmatebank_testmode,$insert_id, $db;
	
					$billmatebank_ot = $_SESSION['billmatebank_ot'];
	
	        //Set the right Host and Port
	        $livemode = $this->billmatebank_livemode;
	
	        $estoreUser = $customer_id;
	        $goodsList = array();
	        $n = sizeof($order->products);
	
	        // First all the ordinary items
	        for ($i = 0 ; $i < $n ; $i++) {
	            //    $price_without_tax = ($order->products[$i]['final_price'] * 100/
	            //				  (1+$order->products[$i]['tax']/100));
	            
	            //Rounding off error fix starts
	            // Products price with tax
	            $price_with_tax = $currencies->get_value($currency) *
	                    $order->products[$i]['final_price'] * (1 + $order->products[$i]['tax'] / 100) * 100;
	            // Products price without tax
	            $price_without_tax = $currencies->get_value($currency) *
	                    $order->products[$i]['final_price'] * 100;
	            $attributes = "";
	
	
	
	            if(isset($order->products[$i]['attributes'])) {
	                foreach($order->products[$i]['attributes'] as $attr) {
	                    $attributes = $attributes . ", " . $attr['option'] . ": " .
	                            $attr['value'];
	                }
	            }
	
	            if (MODULE_PAYMENT_BILLMATEBANK_ARTNO == 'id' ||
	                    MODULE_PAYMENT_BILLMATEBANK_ARTNO == '') {
	                $goodsList[] =
	                        mk_goods_flags($order->products[$i]['qty'],
	                        (string)$order->products[$i]['id'],
	                        $order->products[$i]['name'] . $attributes,
	                        $price_without_tax,
	                        $order->products[$i]['tax'],
	                        0,
	                        0); //incl VAT
	            } else {
	                $goodsList[] =
	                        mk_goods_flags($order->products[$i]['qty'],
	                        $order->products[$i][MODULE_PAYMENT_BILLMATEBANK_ARTNO],
	                        $order->products[$i]['name'] . $attributes,
	                        $price_without_tax,
	                        $order->products[$i]['tax'],
	                        0,
	                        0); //incl VAT
	            }
	        }

	        // Then the extra charnges like shipping and invoicefee and
	        // discount.
	
	        $extra = $billmatebank_ot['code_entries'];
	        //end hack
	
	        for ($j=0 ; $j<$extra ; $j++) {
	            $size = $billmatebank_ot["code_size_".$j];
	            for ($i=0 ; $i<$size ; $i++) {
	                $value = $billmatebank_ot["value_".$j."_".$i];
	                $name = $billmatebank_ot["title_".$j."_".$i];
	                $tax = $billmatebank_ot["tax_rate_".$j."_".$i];
	                $name = rtrim($name, ":");
	                $code = $billmatebank_ot["code_".$j."_".$i];
	                $flags = 0; //INC VAT
	                if($code == 'ot_shipping') {
	                    $flags += 8; //IS_SHIPMENT
	                }
	                else if($code == 'ot_'.$this->code.'_fee') {
	                    $flags += 16; //IS_HANDLING
	                }
	
	/*                if(DISPLAY_PRICE_WITH_TAX == 'true') {
	                } else {
	                    $price_with_tax = $currencies->get_value($currency) * $value * 100*(($tax/100)+1);
	                }*/
	
					$price_with_tax = $currencies->get_value($currency) * $value * 100;
	
	                if ($value != "" && $value != 0) {
	                    $goodsList[] = mk_goods_flags(1, "", BillmateUtils::convertData($name), $price_with_tax, $tax, 0, $flags);
	                }
	
	            }
	        }
	
	        $secret = (float)MODULE_PAYMENT_BILLMATEBANK_SECRET;
	        $eid = (int)MODULE_PAYMENT_BILLMATEBANK_EID;
	
			$pclass = -1;
			$ship_address = $bill_address = array();
	
	        //$countryData = BillmateCountry::getCountryData($order->billing['country']['iso_code_3']);
			$countryData = BillmateCountry::getSwedenData();
			
		    $ship_address = array(
			    'email'           => $order->customer['email_address'],
			    'telno'           => $order->customer['telephone'],
			    'cellno'          => '',
			    'fname'           => $order->delivery['firstname'],
			    'lname'           => $order->delivery['lastname'],
			    'company'         => $order->delivery['company'],
			    'careof'          => '',
			    'street'          => $order->delivery['street_address'],
			    'zip'             => $order->delivery['postcode'],
			    'city'            => $order->delivery['city'],
			    'country'         => $order->delivery['country']['title'],
		    );
		    $bill_address = array(
			    'email'           => $order->customer['email_address'],
			    'telno'           => $order->customer['telephone'],
			    'cellno'          => '',
			    'fname'           => $order->billing['firstname'],
			    'lname'           => $order->billing['lastname'],
			    'company'         => $order->billing['company'],
			    'careof'          => '',
			    'street'          => $order->billing['street_address'],
			    'house_number'    => '',
			    'house_extension' => '',
			    'zip'             => $order->billing['postcode'],
			    'city'            => $order->billing['city'],
			    'country'         => $order->billing['country']['title'],
		    );
	
	       foreach($ship_address as $key => $col ){
	            if(is_numeric($col) ) continue;
	            $ship_address[$key] = utf8_decode(Encoding::fixUTF8( $col ));
	        }
	       foreach($bill_address as $key => $col ){
	            if(is_numeric($col) ) continue;
	            $bill_address[$key] = utf8_decode(Encoding::fixUTF8( $col ));
	        }
	   
	        //extract($countryData);
			
	
			$transaction = array(
				"order1"=>(string)time(),
				"comment"=>(string)"",
				"flags"=>0,
				"reference"=>"",
				"reference_code"=>"",
				"currency"=>$countryData['currency'],
				"country"=>209,
				"language"=>$countryData['language'],
				"pclass"=>$pclass,
				"shipInfo"=>array("delay_adjust"=>"1"),
				"travelInfo"=>array(),
				"incomeInfo"=>array(),
				"bankInfo"=>array(),
				"sid"=>array("time"=>microtime(true)),
				"extraInfo"=>array(array("cust_no"=>(string)$customer_id,"creditcard_data"=>$_POST))
			);
			
			
			$transaction["extraInfo"][0]["status"] = 'Paid';
			
			$ssl = true;
			$debug = false;
			
			$k = new BillMate($eid,$secret,$ssl,$debug);
			$result1 = $k->AddOrder('',$bill_address,$ship_address,$goodsList,$transaction);
		}
    function before_process() {
	     global $order, $customer_id, $currency, $currencies, $sendto, $billto,
               $billmatebank_ot, $billmatebank_livemode, $billmatebank_testmode,$insert_id, $db;

			$billmatebank_ot = $_SESSION['billmatebank_ot'];
			if( empty( $_POST ) ){
					$_POST = $_GET;
			}	
        if(!isset($_POST['status']) || $_POST['status'] != 0){
        		$_SESSION['error'] = $_POST['error_message'];
						zen_redirect( zen_href_link(FILENAME_CHECKOUT_PAYMENT).'payment_error=billmatebank&error=true');
            return;
        } 
        
        //Set the right Host and Port
        $livemode = $this->billmatebank_livemode;

        $estoreUser = $customer_id;
        $goodsList = array();
        $n = sizeof($order->products);

        // First all the ordinary items
        for ($i = 0 ; $i < $n ; $i++) {
            //    $price_without_tax = ($order->products[$i]['final_price'] * 100/
            //				  (1+$order->products[$i]['tax']/100));
            
            //Rounding off error fix starts
            // Products price with tax
            $price_with_tax = $currencies->get_value($currency) *
                    $order->products[$i]['final_price'] * (1 + $order->products[$i]['tax'] / 100) * 100;
            // Products price without tax
            $price_without_tax = $currencies->get_value($currency) *
                    $order->products[$i]['final_price'] * 100;
            $attributes = "";



            if(isset($order->products[$i]['attributes'])) {
                foreach($order->products[$i]['attributes'] as $attr) {
                    $attributes = $attributes . ", " . $attr['option'] . ": " .
                            $attr['value'];
                }
            }

            if (MODULE_PAYMENT_BILLMATEBANK_ARTNO == 'id' ||
                    MODULE_PAYMENT_BILLMATEBANK_ARTNO == '') {
                $goodsList[] =
                        mk_goods_flags($order->products[$i]['qty'],
                        (string)$order->products[$i]['id'],
                        $order->products[$i]['name'] . $attributes,
                        $price_without_tax,
                        $order->products[$i]['tax'],
                        0,
                        0); //incl VAT
            } else {
                $goodsList[] =
                        mk_goods_flags($order->products[$i]['qty'],
                        $order->products[$i][MODULE_PAYMENT_BILLMATEBANK_ARTNO],
                        $order->products[$i]['name'] . $attributes,
                        $price_without_tax,
                        $order->products[$i]['tax'],
                        0,
                        0); //incl VAT
            }
        }

        // Then the extra charnges like shipping and invoicefee and
        // discount.

        $extra = $billmatebank_ot['code_entries'];
        //end hack

        for ($j=0 ; $j<$extra ; $j++) {
            $size = $billmatebank_ot["code_size_".$j];
            for ($i=0 ; $i<$size ; $i++) {
                $value = $billmatebank_ot["value_".$j."_".$i];
                $name = $billmatebank_ot["title_".$j."_".$i];
                $tax = $billmatebank_ot["tax_rate_".$j."_".$i];
                $name = rtrim($name, ":");
                $code = $billmatebank_ot["code_".$j."_".$i];
                $flags = 0; //INC VAT
                if($code == 'ot_shipping') {
                    $flags += 8; //IS_SHIPMENT
                }
                else if($code == 'ot_'.$this->code.'_fee') {
                    $flags += 16; //IS_HANDLING
                }

/*                if(DISPLAY_PRICE_WITH_TAX == 'true') {
                } else {
                    $price_with_tax = $currencies->get_value($currency) * $value * 100*(($tax/100)+1);
                }*/

				$price_with_tax = $currencies->get_value($currency) * $value * 100;

                if ($value != "" && $value != 0) {
                    $goodsList[] = mk_goods_flags(1, "", BillmateUtils::convertData($name), $price_with_tax, $tax, 0, $flags);
                }

            }
        }

        $secret = (float)MODULE_PAYMENT_BILLMATEBANK_SECRET;
        $eid = (int)MODULE_PAYMENT_BILLMATEBANK_EID;

		$pclass = -1;
		$ship_address = $bill_address = array();

        //$countryData = BillmateCountry::getCountryData($order->billing['country']['iso_code_3']);
		$countryData = BillmateCountry::getSwedenData();
		
	    $ship_address = array(
		    'email'           => $order->customer['email_address'],
		    'telno'           => $order->customer['telephone'],
		    'cellno'          => '',
		    'fname'           => $order->delivery['firstname'],
		    'lname'           => $order->delivery['lastname'],
		    'company'         => $order->delivery['company'],
		    'careof'          => '',
		    'street'          => $order->delivery['street_address'],
		    'zip'             => $order->delivery['postcode'],
		    'city'            => $order->delivery['city'],
		    'country'         => $order->delivery['country']['title'],
	    );
	    $bill_address = array(
		    'email'           => $order->customer['email_address'],
		    'telno'           => $order->customer['telephone'],
		    'cellno'          => '',
		    'fname'           => $order->billing['firstname'],
		    'lname'           => $order->billing['lastname'],
		    'company'         => $order->billing['company'],
		    'careof'          => '',
		    'street'          => $order->billing['street_address'],
		    'house_number'    => '',
		    'house_extension' => '',
		    'zip'             => $order->billing['postcode'],
		    'city'            => $order->billing['city'],
		    'country'         => $order->billing['country']['title'],
	    );

       foreach($ship_address as $key => $col ){
            if(is_numeric($col) ) continue;
            $ship_address[$key] = utf8_decode(Encoding::fixUTF8( $col ));
        }
       foreach($bill_address as $key => $col ){
            if(is_numeric($col) ) continue;
            $bill_address[$key] = utf8_decode(Encoding::fixUTF8( $col ));
        }
   
        //extract($countryData);
		

		$transaction = array(
			"order1"=>(string)time(),
			"comment"=>(string)"",
			"flags"=>0,
			"reference"=>"",
			"reference_code"=>"",
			"currency"=>$countryData['currency'],
			"country"=>209,
			"language"=>$countryData['language'],
			"pclass"=>$pclass,
			"shipInfo"=>array("delay_adjust"=>"1"),
			"travelInfo"=>array(),
			"incomeInfo"=>array(),
			"bankInfo"=>array(),
			"sid"=>array("time"=>microtime(true)),
			"extraInfo"=>array(array("cust_no"=>(string)$customer_id,"creditcard_data"=>$_POST))
		);
		
		
		$transaction["extraInfo"][0]["status"] = 'Paid';
		
		$ssl = true;
		$debug = false;
		
		if( !isset($_SESSION['bank_api_called']) || $_SESSION['bank_api_called']!= true) {
			$k = new BillMate($eid,$secret,$ssl,$debug);
			$result1 = $k->AddInvoice('',$bill_address,$ship_address,$goodsList,$transaction);
		}
		
		if (is_array($result1)) {
			$_SESSION['bank_api_called'] = true;
            // insert address in address book to get correct address in
            // confirmation mail (or fetch correct address from address book
            // if it exists)

            $check_country_query = "select countries_id from " . TABLE_COUNTRIES .
				                    " where countries_iso_code_2 = 'SE'";

            $check_country = $db->Execute($check_country_query);

            $cid = $check_country->fields['countries_id'];

            $check_address_query = "select address_book_id from " . TABLE_ADDRESS_BOOK .
									" where customers_id = '" . (int)$customer_id .
									"' and entry_firstname = '" . $order->delivery['firstname'] .
									"' and entry_lastname = '" . $order->delivery['lastname'] .
									"' and entry_street_address = '" . $order->delivery['street_address'] .
									"' and entry_postcode = '" . $order->delivery['postcode'] .
									"' and entry_city = '" . $order->delivery['city'] .
									"' and entry_company = '" . $order->delivery['company'] . "'";
            $check_address = $db->Execute($check_address_query);
            if(is_array($check_address) && count($check_address) > 0) {
                $sendto = $billto = $check_address['address_book_id'];
            }else {
                $sql_data_array =
                        array('customers_id' => $customer_id,
                        'entry_firstname' => $order->delivery['firstname'],
                        'entry_lastname' => $order->delivery['lastname'],
                        'entry_company' => $order->delivery['company'],
                        'entry_street_address' => $order->delivery['street_address'],
                        'entry_postcode' => $order->delivery['postcode'],
                        'entry_city' => $order->delivery['city'],
                        'entry_country_id' => $cid);

                zen_db_perform(TABLE_ADDRESS_BOOK, $sql_data_array);
                $sendto = $billto = $db->insert_ID();
            }

            $order->billmateref=$result1[1];
            $payment['tan']=$result1[1];
            unset($_SESSION['billmatebank_ot']);
            return false;
        } else {
					$_SESSION['error'] = utf8_encode($result1);
					zen_redirect( zen_href_link(FILENAME_CHECKOUT_PAYMENT).'&payment_error=billmatebank&error=true' );
        }
    }

    function after_process() {
        global $insert_id, $order, $db;

        $find_st_optional_field_query =
                $db->Execute("show columns from " . TABLE_ORDERS);

        $has_billmatebank_ref = false;

        while(!$find_st_optional_field_query->EOF) {
            if ( $find_st_optional_field_query->fields['Field'] == "billmateref" )
                $has_billmatebank_ref = true;
			$find_st_optional_field_query->MoveNext();
        }

        if ($has_billmatebank_ref) {
            $db->Execute("update " . TABLE_ORDERS . " set billmateref='" .
                    $order->billmateref . "' " . " where orders_id = '" .
                    $insert_id . "'");
        }

        // Insert transaction # into history file

        $sql_data_array = array('orders_id' => $insert_id,
                'orders_status_id' =>
                ($order->info['order_status']),
                'date_added' => 'now()',
                'customer_notified' => 0,
                'comments' => ('Accepted by Billmate ' .
                        date("Y-m-d G:i:s") .
                        ' Invoice #: ' .
                        $order->billmateref));

        zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
        $secret = (float)MODULE_PAYMENT_BILLMATEBANK_SECRET;
        $eid = (int)MODULE_PAYMENT_BILLMATEBANK_EID;
        $invno = $order->billmateref;

		$ssl = true;
		$debug = false;

		$k = new BillMate($eid,$secret,$ssl,$debug, $this->billmatebank_testmode);
		$result1 = $k->UpdateOrderNo($invno, $insert_id);

        //Delete Session with user details
        unset($_SESSION['user_billing']);

        return false;
    }


    function get_error() {
		
       if (isset($_GET['message']) && strlen($_GET['message']) > 0) {
            $error = stripslashes(urldecode($_GET['message']));
        } else {
		       $error = $_SESSION['error']; unset($_SESSION['error']);
        }
        return array('title' => html_entity_decode(MODULE_PAYMENT_BILLMATE_ERRORINVOICE),
                     'error' => $error);

    }

    function check() {
		global $db;
        if (!isset($this->_check)) {
            $check_query = $db->Execute("select configuration_value from " .
                    TABLE_CONFIGURATION .
                    " where configuration_key = " .
                    "'MODULE_PAYMENT_BILLMATEBANK_STATUS'");
            $this->_check = $check_query->RecordCount();
        }
        return $this->_check;
    }

    function install() {

				global $db;
        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Billmate Module', 'MODULE_PAYMENT_BILLMATEBANK_STATUS', 'True', 'Do you want to accept Billmate payments?', '6', '0', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Payment Zone', 'MODULE_PAYMENT_BILLMATEBANK_ZONE', '0', 'If a zone is selected, only enable this payment method for that zone.', '6', '2', 'zen_get_zone_class_title', 'zen_cfg_pull_down_zone_classes(', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Merchant ID', 'MODULE_PAYMENT_BILLMATEBANK_EID', '0', 'Merchant ID (estore id) to use for the Billmate service (provided by Billmate)', '6', '0', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Shared secret', 'MODULE_PAYMENT_BILLMATEBANK_SECRET', '', 'Shared secret to use with the Billmate service (provided by Billmate)', '6', '0', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Product artno attribute (id or model)', 'MODULE_PAYMENT_BILLMATEBANK_ARTNO', 'id', 'Use the following product attribute for ArtNo.', '6', '2', 'zen_cfg_select_option(array(\'id\', \'model\'),', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Ignore table', 'MODULE_PAYMENT_BILLMATEBANK_ORDER_TOTAL_IGNORE', 'ot_tax,ot_total,ot_subtotal', 'Ignore these entries from order total list when compiling the invoice data', '6', '2', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Credit limit', 'MODULE_PAYMENT_BILLMATEBANK_ORDER_LIMIT', '50000', 'Only show this payment alternative for orders less than the value below.', '6', '2', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of display.', 'MODULE_PAYMENT_BILLMATEBANK_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Order Status', 'MODULE_PAYMENT_BILLMATEBANK_ORDER_STATUS_ID', '0', 'Set the status of orders made with this payment module to this value', '6', '0', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Testmode', 'MODULE_PAYMENT_BILLMATEBANK_TESTMODE', 'False', 'Do you want to activate the Testmode? We will not pay for the invoices created with the test persons nor companies and we will not collect any fees as well.', '6', '0', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Disabled countries', 'MODULE_PAYMENT_BILLMATEBANK_DISABLED_COUNTRYIES', 'se,fi,dk,no', 'Disable in these countries<br/>Enter country ISO Code of two characters <br/>se = Sweden<br/>fi = Finland<br/>dk = Denmark<br/>no = Norway', '9', '0', now())");

    }

    function remove() {
			global $db;
			$db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
        return array('MODULE_PAYMENT_BILLMATEBANK_STATUS',
                'MODULE_PAYMENT_BILLMATEBANK_ORDER_STATUS_ID',
                'MODULE_PAYMENT_BILLMATEBANK_EID',
                'MODULE_PAYMENT_BILLMATEBANK_SECRET',
                'MODULE_PAYMENT_BILLMATEBANK_ARTNO',
                'MODULE_PAYMENT_BILLMATEBANK_DISABLED_COUNTRYIES',
                'MODULE_PAYMENT_BILLMATEBANK_ORDER_LIMIT',
                'MODULE_PAYMENT_BILLMATEBANK_ORDER_TOTAL_IGNORE',
                'MODULE_PAYMENT_BILLMATEBANK_TESTMODE',
                'MODULE_PAYMENT_BILLMATEBANK_ZONE',
                'MODULE_PAYMENT_BILLMATEBANK_SORT_ORDER');
    }

}
$i = $includeLoopVariable;
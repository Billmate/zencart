<?php
/**
*  Copyright 2010 BILLMATECARDPAY AB. All rights reserved.
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
*  THIS SOFTWARE IS PROVIDED BY BILLMATECARDPAY AB "AS IS" AND ANY EXPRESS OR IMPLIED
*  WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND
*  FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL BILLMATECARDPAY AB OR
*  CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
*  CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
*  SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
*  ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
*  NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF
*  ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*
*  The views and conclusions contained in the software and documentation are those of the
*  authors and should not be interpreted as representing official policies, either expressed
*  or implied, of BILLMATECARDPAY AB.
*
*/

$includeLoopVariable = $i;

@include_once(DIR_FS_CATALOG . DIR_WS_CLASSES . 'billmate/billmate_lang.php');
if(!class_exists('Encoding',false)){
	require_once DIR_FS_CATALOG . DIR_WS_CLASSES.'billmate/utf8.php';
	require_once(DIR_FS_CATALOG . DIR_WS_CLASSES . 'billmate/billmate_api.php');
	require_once(DIR_FS_CATALOG . DIR_WS_CLASSES . 'billmate/billmateutils.php');
}

error_reporting(E_ERROR);
ini_set('display_errors', true);


class billmatecardpay {
	var $code, $title, $description, $enabled, $billmatecardpay_livemode, $billmatecardpay_testmode, $jQuery, $form_action_url;

	// class constructor
	function billmatecardpay() {
		global $order, $currency, $currencies, $customer_id, $customer_country_id, $billmatecardpay_livemode, $billmatecardpay_testmode, $db;
		$this->jQuery = true;
		$this->code = 'billmatecardpay';

		if(strpos($_SERVER['SCRIPT_FILENAME'],'admin')) {
			$this->title = MODULE_PAYMENT_BILLMATECARDPAY_TEXT_TITLE;
		}
		else {
			$this->title = MODULE_PAYMENT_BILLMATECARDPAY_TEXT_TITLE;
		}

		$this->billmatecardpay_testmode = false;
		if ((MODULE_PAYMENT_BILLMATECARDPAY_TESTMODE == 'True')) {
			$this->title .= ' '.MODULE_PAYMENT_BILLMATECARDPAY_LANG_TESTMODE;
			$this->billmatecardpay_testmode = true;
		}

		if (MODULE_PAYMENT_BILLMATECARDPAY_TESTMODE == 'True') {
			$this->form_action_url = 'https://cardpay.billmate.se/pay/test';
		} else {
			$this->form_action_url = 'https://cardpay.billmate.se/pay';
		}

		if( !isset($order->billing) || $order->billing == null ){
			$billing = $_SESSION['billmate_billing'];
		}else{
			$billing = $_SESSION['billmate_billing'] = $order->billing;
		}


		(MODULE_PAYMENT_BILLMATECARDPAY_TESTMODE != 'True') ? $this->billmatecardpay_livemode = true : $this->billmatecardpay_livemode = false;

		$this->description = MODULE_PAYMENT_BILLMATECARDPAY_TEXT_DESCRIPTION . "<br />Version: 1.4";
		$this->enabled = ((MODULE_PAYMENT_BILLMATECARDPAY_STATUS == 'True') ?
		true : false);

		$currency = $_SESSION['currency'];
		$currencyValid = array('SE','SEK','EU', 'EUR','NOK','NO', 'SE','sek','eu', 'eur','nok','no' );
		$countryValid  = array('SE', 'DK', 'FI', 'NO','se', 'dk', 'fi', 'no');
		$disabled_countries = explode(',',
		trim(
		strtolower(MODULE_PAYMENT_BILLMATECARDPAY_DISABLED_COUNTRYIES),
		','
		).','.
		trim(
		strtoupper(MODULE_PAYMENT_BILLMATECARDPAY_DISABLED_COUNTRYIES),
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
			$this->updateCancelStatus();
			
			if ($order->info['total']*$er > MODULE_PAYMENT_BILLMATECARDPAY_ORDER_LIMIT)
			$this->enabled = false;

			$this->order_status = DEFAULT_ORDERS_STATUS_ID;

			if (is_object($order))
			$this->update_status();
		}
		$this->sort_order = MODULE_PAYMENT_BILLMATECARDPAY_SORT_ORDER;
	}
	function updateCancelStatus(){
		global $db;
		if(!is_array($_r = $db->Execute('select orders_status_id from '.TABLE_ORDERS_STATUS.' where orders_status_name like "cancelled"')->fields)){
			$languages = $db->Execute("select languages_id from " . TABLE_LANGUAGES );
			$order_status_id = false;
			$order_status_id = $db->Execute('select Max(orders_status_id) as mxid from '.TABLE_ORDERS_STATUS)->fields['mxid']+1;
			 
			while (!$languages->EOF ) {
			
				$sql = array('orders_status_id'=>$order_status_id, 'language_id'=> $languages->fields['languages_id'],'orders_status_name'=>'Cancelled');
				zen_db_perform(TABLE_ORDERS_STATUS, $sql);
				$languages->MoveNext();
			}
		} else{
			$order_status_id = $_r['orders_status_id'];
		}
		$db->Execute("insert ignore into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Cancelled Order Status', 'MODULE_PAYMENT_BILLMATECARDPAY_CANCEL', '{$order_status_id}', '', '6', '0', '', now())");
	}
	// class methods
	function update_status() {
		global $order, $db;

		if ($this->enabled == true && (int)MODULE_PAYMENT_BILLMATECARDPAY_ZONE > 0) {
			$check_flag = false;
			$check_query = ("select zone_id from " .
			TABLE_ZONES_TO_GEO_ZONES .
			" where geo_zone_id = '" .
			MODULE_PAYMENT_BILLMATECARDPAY_ZONE .
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

		$cart_billmate_card_ID = $_SESSION['cart_billmate_card_ID'];
        if (isset($_SESSION['cart_billmate_card_ID'])) {
			$order_id = $insert_id = substr($cart_billmate_card_ID, strpos($cart_billmate_card_ID, '-')+1);
			$check_query = $db->Execute('select orders_id from ' . TABLE_ORDERS . ' where orders_id = "' . (int)$order_id . '" limit 1')->fields;

			if (is_array($check_query)) {
				$sql = "update " . TABLE_ORDERS . " set orders_status = '".MODULE_PAYMENT_BILLMATECARDPAY_CANCEL."', last_modified = now() where orders_id = '" . (int)$order_id . "'";
				$db->Execute($sql);
				unset($_SESSION['cart_billmate_card_ID']);
			}
		}

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

		empty($user_billing['billmatecardpay_pnum']) ? $billmatecardpay_pnum = $personnummer : $billmatecardpay_pnum = $user_billing['billmatecardpay_pnum'];
		empty($user_billing['billmatecardpay_phone']) ? $billmatecardpay_phone = $order->customer['telephone'] : $billmatecardpay_phone = $user_billing['billmatecardpay_phone'];

		//Fade in/fade out code for the module
		$fields[] = array('title' => BILLMATE_LANG_SE_IMGCARDPAY, 'field' => '');

		return array('id' => $this->code,
		'module' => $this->title,
		'fields' => $fields);
	}

	function pre_confirmation_check() {
		global $billmatecardpay_testmode, $billmatecardpay_livemode, $order, $GA_OLD, $BILL_SE_PNO, $user_billing;
		//Store values into Session
		$_SESSION['user_billing'] = $user_billing;

		$eid = MODULE_PAYMENT_BILLMATECARDPAY_EID;
		$secret = MODULE_PAYMENT_BILLMATECARDPAY_SECRET;
	}

	function confirmation() {
		global $cartID, $cart_billmate_card_ID, $customer_id, $languages_id, $order, $order_total_modules,$db;
		
		$customer_id = $_SESSION['customer_id'];
		$cartID = $_SESSION['cart']->cartID;

		if (isset($_SESSION['cart_billmate_card_ID'])) {
		
		  $cart_billmate_card_ID = $_SESSION['cart_billmate_card_ID'];
          $order_id = substr($cart_billmate_card_ID, strpos($cart_billmate_card_ID, '-')+1);

          $curr_check = $db->Execute("select currency from " . TABLE_ORDERS . " where orders_id = '" . (int)$order_id . "'");
          $curr = $curr_check->fields;

          if ( ($curr['currency'] != $order->info['currency']) || ($cartID != substr($cart_billmate_card_ID, 0, strlen($cartID))) ) {
            $check_query = $db->Execute('select orders_id from ' . TABLE_ORDERS . ' where orders_id = "' . (int)$order_id . '" limit 1');

            if (is_array($check_query->fields)) {
				$sql = "update " . TABLE_ORDERS . " set orders_status = '".MODULE_PAYMENT_BILLMATECARDPAY_CANCEL."', last_modified = now() where orders_id = '" . (int)$order_id . "'";
				unset($_SESSION['cart_billmate_card_ID']);
            }

            $insert_order = true;
          }
        } else {
          $insert_order = true;
        }
		
		if ($insert_order == true) {
          $order_totals = array();
		  $myOrder = new order;
		  $myOrder = clone $order;
		  
          if (is_array($order_total_modules->modules)) {
            reset($order_total_modules->modules);
			
            while (list(, $value) = each($order_total_modules->modules)) {
              $class = substr($value, 0, strrpos($value, '.'));
			  if( $class == 'ot_shipping' ) {
				    $shipping_title = $order->info['shipping_method'] . ':';
				    $shipping_text = $currencies->format($order->info['shipping_cost'], true, $order->info['currency'], $order->info['currency_value']);
				    $shipping_value = $order->info['shipping_cost'];
					$order_totals[] = array('code' => $GLOBALS[$class]->code,
											'title' => $shipping_title,
											'text' => $shipping_text,
											'value' => $shipping_value,
											'sort_order' => $GLOBALS[$class]->sort_order);
			  } else {
				  $GLOBALS[$class]->process();
				  for ($i=0, $n=sizeof($GLOBALS[$class]->output); $i<$n; $i++) {
					
	
					if (zen_not_null($GLOBALS[$class]->output[$i]['title']) && zen_not_null($GLOBALS[$class]->output[$i]['text'])) {
						$order_totals[] = array('code' => $GLOBALS[$class]->code,
												'title' => $GLOBALS[$class]->output[$i]['title'],
												'text' => $GLOBALS[$class]->output[$i]['text'],
												'value' => $GLOBALS[$class]->output[$i]['value'],
												'sort_order' => $GLOBALS[$class]->sort_order);
					}
				  }
				  $GLOBALS[$class]->$class();
			  }
            }
          }

          $sql_data_array = array('customers_id' => $customer_id,
                                  'customers_name' => $order->customer['firstname'] . ' ' . $order->customer['lastname'],
                                  'customers_company' => $order->customer['company'],
                                  'customers_street_address' => $order->customer['street_address'],
                                  'customers_suburb' => $order->customer['suburb'],
                                  'customers_city' => $order->customer['city'],
                                  'customers_postcode' => $order->customer['postcode'],
                                  'customers_state' => $order->customer['state'],
                                  'customers_country' => $order->customer['country']['title'],
                                  'customers_telephone' => $order->customer['telephone'],
                                  'customers_email_address' => $order->customer['email_address'],
                                  'customers_address_format_id' => $order->customer['format_id'],
                                  'delivery_name' => $order->delivery['firstname'] . ' ' . $order->delivery['lastname'],
                                  'delivery_company' => $order->delivery['company'],
                                  'delivery_street_address' => $order->delivery['street_address'],
                                  'delivery_suburb' => $order->delivery['suburb'],
                                  'delivery_city' => $order->delivery['city'],
                                  'delivery_postcode' => $order->delivery['postcode'],
                                  'delivery_state' => $order->delivery['state'],
                                  'delivery_country' => $order->delivery['country']['title'],
                                  'delivery_address_format_id' => $order->delivery['format_id'],
                                  'billing_name' => $order->billing['firstname'] . ' ' . $order->billing['lastname'],
                                  'billing_company' => $order->billing['company'],
                                  'billing_street_address' => $order->billing['street_address'],
                                  'billing_suburb' => $order->billing['suburb'],
                                  'billing_city' => $order->billing['city'],
                                  'billing_postcode' => $order->billing['postcode'],
                                  'billing_state' => $order->billing['state'],
                                  'billing_country' => $order->billing['country']['title'],
                                  'billing_address_format_id' => $order->billing['format_id'],
                                  'payment_method' => $this->code,
                                  'payment_module_code' => $this->code,
								  'shipping_method' => $order->info['shipping_method'],
								  'shipping_module_code' => (strpos($order->info['shipping_module_code'], '_') > 0 ? substr($order->info['shipping_module_code'], 0, strpos($order->info['shipping_module_code'], '_')) : $order->info['shipping_module_code']),
                                  'cc_type' => $order->info['cc_type'],
                                  'cc_owner' => $order->info['cc_owner'],
                                  'cc_number' => $order->info['cc_number'],
                                  'cc_expires' => $order->info['cc_expires'],
                                  'date_purchased' => 'now()',
                                  'orders_status' => $order->info['order_status'],
                                  'currency' => $order->info['currency'],
								  'ip_address' => $_SESSION['customers_ip_address'] . ' - ' . $_SERVER['REMOTE_ADDR'],
                                  'currency_value' => $order->info['currency_value']);

		  
          zen_db_perform(TABLE_ORDERS, $sql_data_array);

          $insert_id = $db->insert_ID();

          for ($i=0, $n=sizeof($order_totals); $i<$n; $i++) {
            $sql_data_array = array('orders_id' => $insert_id,
                                    'title' => $order_totals[$i]['title'],
                                    'text' => $order_totals[$i]['text'],
                                    'value' => $order_totals[$i]['value'],
                                    'class' => $order_totals[$i]['code'],
                                    'sort_order' => $order_totals[$i]['sort_order']);

            zen_db_perform(TABLE_ORDERS_TOTAL, $sql_data_array);
          }

          for ($i=0, $n=sizeof($order->products); $i<$n; $i++) {
            $sql_data_array = array('orders_id' => $insert_id,
                                    'products_id' => zen_get_prid($order->products[$i]['id']),
                                    'products_model' => $order->products[$i]['model'],
                                    'products_name' => $order->products[$i]['name'],
                                    'products_price' => $order->products[$i]['price'],
                                    'final_price' => $order->products[$i]['final_price'],
                                    'products_tax' => $order->products[$i]['tax'],
                                    'products_quantity' => $order->products[$i]['qty']);

            zen_db_perform(TABLE_ORDERS_PRODUCTS, $sql_data_array);

            $order_products_id = $db->insert_ID();

            $attributes_exist = '0';
            if (isset($order->products[$i]['attributes'])) {
              $attributes_exist = '1';
              for ($j=0, $n2=sizeof($order->products[$i]['attributes']); $j<$n2; $j++) {
				  if (DOWNLOAD_ENABLED == 'true') {
					$attributes_query = "select popt.products_options_name, poval.products_options_values_name,
										 pa.options_values_price, pa.price_prefix,
										 pa.product_attribute_is_free, pa.products_attributes_weight, pa.products_attributes_weight_prefix,
										 pa.attributes_discounted, pa.attributes_price_base_included, pa.attributes_price_onetime,
										 pa.attributes_price_factor, pa.attributes_price_factor_offset,
										 pa.attributes_price_factor_onetime, pa.attributes_price_factor_onetime_offset,
										 pa.attributes_qty_prices, pa.attributes_qty_prices_onetime,
										 pa.attributes_price_words, pa.attributes_price_words_free,
										 pa.attributes_price_letters, pa.attributes_price_letters_free,
										 pad.products_attributes_maxdays, pad.products_attributes_maxcount, pad.products_attributes_filename
										 from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " .
					TABLE_PRODUCTS_ATTRIBUTES . " pa
										  left join " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad
										  on pa.products_attributes_id=pad.products_attributes_id
										 where pa.products_id = '" . zen_db_input($order->products[$i]['id']) . "'
										  and pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "'
										  and pa.options_id = popt.products_options_id
										  and pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "'
										  and pa.options_values_id = poval.products_options_values_id
										  and popt.language_id = '" . $_SESSION['languages_id'] . "'
										  and poval.language_id = '" . $_SESSION['languages_id'] . "'";

					$attributes_values = $db->Execute($attributes_query);
				  } else {
					$attributes_values = $db->Execute("select popt.products_options_name, poval.products_options_values_name,
										 pa.options_values_price, pa.price_prefix,
										 pa.product_attribute_is_free, pa.products_attributes_weight, pa.products_attributes_weight_prefix,
										 pa.attributes_discounted, pa.attributes_price_base_included, pa.attributes_price_onetime,
										 pa.attributes_price_factor, pa.attributes_price_factor_offset,
										 pa.attributes_price_factor_onetime, pa.attributes_price_factor_onetime_offset,
										 pa.attributes_qty_prices, pa.attributes_qty_prices_onetime,
										 pa.attributes_price_words, pa.attributes_price_words_free,
										 pa.attributes_price_letters, pa.attributes_price_letters_free
										 from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa
										 where pa.products_id = '" . $order->products[$i]['id'] . "' and pa.options_id = '" . (int)$order->products[$i]['attributes'][$j]['option_id'] . "' and pa.options_id = popt.products_options_id and pa.options_values_id = '" . (int)$order->products[$i]['attributes'][$j]['value_id'] . "' and pa.options_values_id = poval.products_options_values_id and popt.language_id = '" . $_SESSION['languages_id'] . "' and poval.language_id = '" . $_SESSION['languages_id'] . "'");
				  }


				$sql_data_array = array('orders_id' => $insert_id,
                                  'orders_products_id' => $order_products_id,
                                  'products_options' => $attributes_values->fields['products_options_name'],

          //                                 'products_options_values' => $attributes_values->fields['products_options_values_name'],
                                  'products_options_values' => $order->products[$i]['attributes'][$j]['value'],
                                  'options_values_price' => $attributes_values->fields['options_values_price'],
                                  'price_prefix' => $attributes_values->fields['price_prefix'],
                                  'product_attribute_is_free' => $attributes_values->fields['product_attribute_is_free'],
                                  'products_attributes_weight' => $attributes_values->fields['products_attributes_weight'],
                                  'products_attributes_weight_prefix' => $attributes_values->fields['products_attributes_weight_prefix'],
                                  'attributes_discounted' => $attributes_values->fields['attributes_discounted'],
                                  'attributes_price_base_included' => $attributes_values->fields['attributes_price_base_included'],
                                  'attributes_price_onetime' => $attributes_values->fields['attributes_price_onetime'],
                                  'attributes_price_factor' => $attributes_values->fields['attributes_price_factor'],
                                  'attributes_price_factor_offset' => $attributes_values->fields['attributes_price_factor_offset'],
                                  'attributes_price_factor_onetime' => $attributes_values->fields['attributes_price_factor_onetime'],
                                  'attributes_price_factor_onetime_offset' => $attributes_values->fields['attributes_price_factor_onetime_offset'],
                                  'attributes_qty_prices' => $attributes_values->fields['attributes_qty_prices'],
                                  'attributes_qty_prices_onetime' => $attributes_values->fields['attributes_qty_prices_onetime'],
                                  'attributes_price_words' => $attributes_values->fields['attributes_price_words'],
                                  'attributes_price_words_free' => $attributes_values->fields['attributes_price_words_free'],
                                  'attributes_price_letters' => $attributes_values->fields['attributes_price_letters'],
                                  'attributes_price_letters_free' => $attributes_values->fields['attributes_price_letters_free'],
                                  'products_options_id' => (int)$order->products[$i]['attributes'][$j]['option_id'],
                                  'products_options_values_id' => (int)$order->products[$i]['attributes'][$j]['value_id'],
                                  'products_prid' => $order->products[$i]['id']
                                  );

                zen_db_perform(TABLE_ORDERS_PRODUCTS_ATTRIBUTES, $sql_data_array);

                if ((DOWNLOAD_ENABLED == 'true') && isset($attributes_values->fields['products_attributes_filename']) && zen_not_null($attributes_values->fields['products_attributes_filename'])) {
				$sql_data_array = array('orders_id' => $insert_id,
                                    'orders_products_id' => $order_products_id,
                                    'orders_products_filename' => $attributes_values->fields['products_attributes_filename'],
                                    'download_maxdays' => $attributes_values->fields['products_attributes_maxdays'],
                                    'download_count' => $attributes_values->fields['products_attributes_maxcount'],
                                    'products_prid' => $order->products[$i]['id']
                                    );

                  zen_db_perform(TABLE_ORDERS_PRODUCTS_DOWNLOAD, $sql_data_array);
                }
              }
            }
          }

          $cart_billmate_card_ID = $cartID . '-' . $insert_id;
          $_SESSION['cart_billmate_card_ID'] = $cart_billmate_card_ID;
		  $order = clone $myOrder;
		}

		return array('title' => MODULE_PAYMENT_BILLMATECARDPAY_TEXT_CONFIRM_DESCRIPTION);
	}

	function process_button() {
		global $order, $order_total_modules, $billmatecardpay_ot, $shipping, $db, $languages_id,$cart_billmate_card_ID;
		$cart_billmate_card_ID = $_SESSION['cart_billmate_card_ID'];
		$counter = 1;
		$process_button_string= '<script type="text/javascript">document.getElementsByName(\'securityToken\').item(0).remove();</script>';

		$sql = "select code from " . TABLE_LANGUAGES . " where directory = '{$_SESSION['language']}'";
		$check_language = $db->Execute($sql);
		$languageCode = strtoupper( $check_language->fields['code'] );

		$languageCode = $languageCode == 'DA' ? 'DK' : $languageCode;
		$languageCode = $languageCode == 'SV' ? 'SE' : $languageCode;
		$languageCode = $languageCode == 'EN' ? 'GB' : $languageCode;

		$eid = MODULE_PAYMENT_BILLMATECARDPAY_EID;
		$secret = substr( MODULE_PAYMENT_BILLMATECARDPAY_SECRET ,0 ,12 );

		$_ = array();
		$_['merchant_id']   = $eid;
		$_['currency']      = $order->info['currency'];
		$_['order_id']      = substr($cart_billmate_card_ID, strpos($cart_billmate_card_ID, '-')+1);
		$_['callback_url']  = 'http://api.billmate.se/callback.php';
		//$_['callback_url']  = 'http://'.$_SERVER['SERVER_NAME'].'/'.DIR_WS_CATALOG.'extras/billmate/cardpay_ipn.php';
		$_['amount']        = round($order->info['total'], 2)*100;
		$_['accept_url']    = zen_href_link(FILENAME_CHECKOUT_PROCESS);
		$_['cancel_url']    = zen_href_link(FILENAME_CHECKOUT_PAYMENT);
		$_['pay_method']    = 'CARD';
		$_['return_method'] = 'GET';
		$_['do_3d_secure']  = MODULE_PAYMENT_BILLMATECARDPAY_3DSECURE_MODE;
		$_['prompt_name_entry']   = MODULE_PAYMENT_BILLMATECARDPAY_NAME_MODE;
		$_['language']      = $languageCode;
		$_['capture_now']   = MODULE_PAYMENT_BILLMATECARDPAY_AUTHENTICATION_MODE == 'sale' ? 'YES' :'NO';
		unset($_SESSION['card_api_called']);
		$mac_str = $_['accept_url'] . $_['amount'] . $_['callback_url'] . $_['cancel_url'] . $_['capture_now'] . $_['currency'] . $_['do_3d_secure'] . $_['language'] . $_['merchant_id'] . $_['order_id'] . $_['pay_method'] . $_['prompt_name_entry'] . $_['return_method'] . $secret;
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
					$billmatecardpay_ot['code_size_'.$j] = $size;
					for ($i=0; $i<$size; $i++) {
						$billmatecardpay_ot['title_'.$j.'_'.$i] = html_entity_decode($GLOBALS[$class]->output[$i]['title']);

						$billmatecardpay_ot['text_'.$j.'_'.$i] = $GLOBALS[$class]->output[$i]['text'];
						if (isset($GLOBALS[$class]->deduction) && is_numeric($GLOBALS[$class]->deduction) &&
						$GLOBALS[$class]->deduction > 0) {
							$billmatecardpay_ot['value_'.$j.'_'.$i] = -$GLOBALS[$class]->deduction;
						}
						else {
							$billmatecardpay_ot['value_'.$j.'_'.$i] = $GLOBALS[$class]->output[$i]['value'];

							// Add tax rate for shipping address and invoice fee
							if ($class == 'ot_shipping') {
								//Set Shipping VAT
								$shipping_id = @explode('_', $_SESSION['shipping']['id']);
								$tax_class = @$GLOBALS[$shipping_id[0]]->tax_class;
								$tax_rate = 0;
								if($tax_class > 0) {
									$tax_rate = zen_get_tax_rate($tax_class, $order->billing['country']['id'], ($order->billing['zone_id'] > 0) ? $order->billing['zone_id'] : null);
								}
								$billmatecardpay_ot['tax_rate_'.$j.'_'.$i] = $tax_rate;
							} else {
								$billmatecardpay_ot['tax_rate_'.$j.'_'.$i] = $GLOBALS[$class]->output[$i]['tax_rate'];
							}
						}

						$billmatecardpay_ot['code_'.$j.'_'.$i] = $GLOBALS[$class]->code;
					}
					$j += 1;
				}
			}
			$billmatecardpay_ot['code_entries'] = $j;
		}

		$_SESSION['billmatecardpay_ot'] = $billmatecardpay_ot;
		$this->doInvoice();
		return $process_button_string;
	}
	function doInvoice($add_order = false ){
		global $order, $customer_id, $currency, $currencies, $sendto, $billto,
		$billmatecardpay_ot, $billmatecardpay_livemode, $billmatecardpay_testmode,$insert_id, $db;

		$billmatecardpay_ot = $_SESSION['billmatecardpay_ot'];

		//Set the right Host and Port
		$livemode = $this->billmatecardpay_livemode;

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

			if (MODULE_PAYMENT_BILLMATECARDPAY_ARTNO == 'id' ||	MODULE_PAYMENT_BILLMATECARDPAY_ARTNO == '') {
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
				$order->products[$i][MODULE_PAYMENT_BILLMATECARDPAY_ARTNO],
				$order->products[$i]['name'] . $attributes,
				$price_without_tax,
				$order->products[$i]['tax'],
				0,
				0); //incl VAT
			}
		}
		// Then the extra charnges like shipping and invoicefee and
		// discount.

		$extra = $billmatecardpay_ot['code_entries'];
		//end hack

		for ($j=0 ; $j<$extra ; $j++) {
			$size = $billmatecardpay_ot["code_size_".$j];
			for ($i=0 ; $i<$size ; $i++) {
				$value = $billmatecardpay_ot["value_".$j."_".$i];
				$name = $billmatecardpay_ot["title_".$j."_".$i];
				$tax = $billmatecardpay_ot["tax_rate_".$j."_".$i];
				$name = rtrim($name, ":");
				$code = $billmatecardpay_ot["code_".$j."_".$i];
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

		$secret = (float)MODULE_PAYMENT_BILLMATECARDPAY_SECRET;
		$eid = (int)MODULE_PAYMENT_BILLMATECARDPAY_EID;

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
		$cart_billmate_card_ID = $_SESSION['cart_billmate_card_ID'];

		$transaction = array(
		"order1"=>(string)substr($cart_billmate_card_ID, strpos($cart_billmate_card_ID, '-')+1),
		'order2'=> '',
		'gender'=>1,
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


		if(MODULE_PAYMENT_BILLMATECARDPAY_AUTHENTICATION_MODE == 'sale') $transaction["extraInfo"][0]["status"] = 'Paid';

		$ssl = true;
		$debug = false;

		$k = new BillMate($eid,$secret,$ssl,$debug);
		$result1 = $k->AddOrder('',$bill_address,$ship_address,$goodsList,$transaction);
	}
	function before_process() {

		global $order, $customer_id, $currency, $currencies, $sendto, $billto,
			   $billmatecardpay_ot, $billmatecardpay_livemode, $billmatecardpay_testmode,$insert_id, $cart_billmate_card_ID,$payment, $db;
		global $$payment,$cartID, $cart,$order_id;;
		
		$cart_billmate_card_ID = $_SESSION['cart_billmate_card_ID'];
		$order_id = substr($cart_billmate_card_ID, strpos($cart_billmate_card_ID, '-')+1);	
		$billmatecardpay_ot = $_SESSION['billmatecardpay_ot'];
		if( empty( $_POST ) ){
			$_POST = $_GET;
		}
		if(!isset($_POST['status']) || $_POST['status'] != 0){
			$_SESSION['error'] = $_POST['error_message'];
			zen_redirect( zen_href_link(FILENAME_CHECKOUT_PAYMENT).'payment_error=billmatecardpay&error=true' );
			return;
		}

		$status_array = $db->Execute("select orders_status from ".TABLE_ORDERS." where orders_id = {$_POST['order_id']}")->fields;

		$status_history = $db->Execute("select orders_status_history_id from ".TABLE_ORDERS_STATUS_HISTORY.
					" where orders_id = {$_POST['order_id']} and comments='Billmate_IPN'");

		if( is_array($status_history->fields) ){
			$already_completed = true;
			unset($_SESSION['billmatecardpay_ot']);
		}else {
			$already_completed = false;
		}
		$_SESSION['already_completed'] = $already_completed;

		//Set the right Host and Port
		$livemode = $this->billmatecardpay_livemode;

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

			if (MODULE_PAYMENT_BILLMATECARDPAY_ARTNO == 'id' ||
			MODULE_PAYMENT_BILLMATECARDPAY_ARTNO == '') {
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
				$order->products[$i][MODULE_PAYMENT_BILLMATECARDPAY_ARTNO],
				$order->products[$i]['name'] . $attributes,
				$price_without_tax,
				$order->products[$i]['tax'],
				0,
				0); //incl VAT
			}
		}

		// Then the extra charnges like shipping and invoicefee and
		// discount.

		$extra = $billmatecardpay_ot['code_entries'];
		//end hack

		for ($j=0 ; $j<$extra ; $j++) {
			$size = $billmatecardpay_ot["code_size_".$j];
			for ($i=0 ; $i<$size ; $i++) {
				$value = $billmatecardpay_ot["value_".$j."_".$i];
				$name = $billmatecardpay_ot["title_".$j."_".$i];
				$tax = $billmatecardpay_ot["tax_rate_".$j."_".$i];
				$name = rtrim($name, ":");
				$code = $billmatecardpay_ot["code_".$j."_".$i];
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

		$secret = (float)MODULE_PAYMENT_BILLMATECARDPAY_SECRET;
		$eid = (int)MODULE_PAYMENT_BILLMATECARDPAY_EID;

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
		"order1"=>$_POST['order_id'],
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


		if(MODULE_PAYMENT_BILLMATECARDPAY_AUTHENTICATION_MODE == 'sale') $transaction["extraInfo"][0]["status"] = 'Paid';
		unset($_SESSION['cart_billmate_card_ID']);

		$ssl = true;
		$debug = false;

		if(!$already_completed ){
			$k = new Billmate($eid,$secret,$ssl,$debug);
			if( !isset($_SESSION['card_api_called']) || $_SESSION['card_api_called']!= true) {
			
				$k = new BillMate($eid,$secret,$ssl,$debug);
				$result1 = $k->AddInvoice('',$bill_address,$ship_address,$goodsList,$transaction);
			}
		}

		if (is_array($result1) || $already_completed) {
			$_SESSION['card_api_called'] = true;

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

			if(!$already_completed){
				$order->billmateref=$result1[1];
				$payment['tan']=$result1[1];
			}
			unset($_SESSION['billmatecardpay_ot']);
			zen_mail($order->customer['firstname'] . ' ' . $order->customer['lastname'], $order->customer['email_address'], EMAIL_TEXT_SUBJECT, $email_order, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);

			if (SEND_EXTRA_ORDER_EMAILS_TO != '') {
				zen_mail('', SEND_EXTRA_ORDER_EMAILS_TO, EMAIL_TEXT_SUBJECT, $email_order, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
			}

			// load the after_process function from the payment modules
			$this->after_process();

			$_SESSION['cart']->reset(true);

			// unregister session variables used during checkout
			unset( $_SESSION['sendto'], $_SESSION['billto'], $_SESSION['shipping'],
			$_SESSION['payment'], $_SESSION['comments'], $_SESSION['cart_billmate_card_ID']);

			zen_redirect(zen_href_link(FILENAME_CHECKOUT_SUCCESS, '', 'SSL'));
			exit;
			return false;
		} else {
			$_SESSION['error'] = utf8_encode($result1);
			zen_redirect( zen_href_link(FILENAME_CHECKOUT_PAYMENT).'&payment_error=billmatecardpay&error=true');
		}
	}

	function after_process() {
		global $order, $db;
		$insert_id = $_POST['order_id'];

		$find_st_optional_field_query =
		$db->Execute("show columns from " . TABLE_ORDERS);

		$has_billmatecardpay_ref = false;

		while(!$find_st_optional_field_query->EOF) {
			if ( $find_st_optional_field_query->fields['Field'] == "billmateref" )
			$has_billmatecardpay_ref = true;
			$find_st_optional_field_query->MoveNext();
		}

		if ($has_billmatecardpay_ref) {
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
		$db->Execute("update " . TABLE_ORDERS . " set orders_status = '".$order_status."', last_modified = now() where orders_id = '" . (int)$insert_id . "'");
		
		$secret = (float)MODULE_PAYMENT_BILLMATECARDPAY_SECRET;
		$eid = (int)MODULE_PAYMENT_BILLMATECARDPAY_EID;
		$invno = $order->billmateref;

		$ssl = true;
		$debug = false;
		$k = new BillMate($eid,$secret,$ssl,$debug, $this->billmatecardpay_testmode);
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
			"'MODULE_PAYMENT_BILLMATECARDPAY_STATUS'");
			$this->_check = $check_query->RecordCount();
		}
		return $this->_check;
	}

	function install() {

		global $db;
		$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Billmate Module', 'MODULE_PAYMENT_BILLMATECARDPAY_STATUS', 'True', 'Do you want to accept Billmate payments?', '6', '0', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");

		$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Payment Zone', 'MODULE_PAYMENT_BILLMATECARDPAY_ZONE', '0', 'If a zone is selected, only enable this payment method for that zone.', '6', '2', 'zen_get_zone_class_title', 'zen_cfg_pull_down_zone_classes(', now())");

		$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Merchant ID', 'MODULE_PAYMENT_BILLMATECARDPAY_EID', '0', 'Merchant ID (estore id) to use for the Billmate service (provided by Billmate)', '6', '0', now())");

		$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Shared secret', 'MODULE_PAYMENT_BILLMATECARDPAY_SECRET', '', 'Shared secret to use with the Billmate service (provided by Billmate)', '6', '0', now())");

		$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Product artno attribute (id or model)', 'MODULE_PAYMENT_BILLMATECARDPAY_ARTNO', 'id', 'Use the following product attribute for ArtNo.', '6', '2', 'zen_cfg_select_option(array(\'id\', \'model\'),', now())");

		$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Ignore table', 'MODULE_PAYMENT_BILLMATECARDPAY_ORDER_TOTAL_IGNORE', 'ot_tax,ot_total,ot_subtotal', 'Ignore these entries from order total list when compiling the invoice data', '6', '2', now())");

		$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Credit limit', 'MODULE_PAYMENT_BILLMATECARDPAY_ORDER_LIMIT', '50000', 'Only show this payment alternative for orders less than the value below.', '6', '2', now())");

		$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of display.', 'MODULE_PAYMENT_BILLMATECARDPAY_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");

		$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Order Status', 'MODULE_PAYMENT_BILLMATECARDPAY_ORDER_STATUS_ID', '0', 'Set the status of orders made with this payment module to this value', '6', '0', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");

		$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Testmode', 'MODULE_PAYMENT_BILLMATECARDPAY_TESTMODE', 'False', 'Do you want to activate the Testmode? We will not pay for the invoices created with the test persons nor companies and we will not collect any fees as well.', '6', '0', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");

		$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Authentication Mode', 'MODULE_PAYMENT_BILLMATECARDPAY_AUTHENTICATION_MODE', 'sale', '', '7', '0', 'zen_cfg_select_option(array(\'sale\', \'authentication\'), ', now())");

		$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable 3DSecure', 'MODULE_PAYMENT_BILLMATECARDPAY_3DSECURE_MODE', 'YES', 'This will enable prompt 3D Secure', '7', '0', 'zen_cfg_select_option(array(\'YES\', \'NO\'), ', now())");

		$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Display Name', 'MODULE_PAYMENT_BILLMATECARDPAY_NAME_MODE', 'NO', 'This will enable prompt name in cardpay', '7', '0', 'zen_cfg_select_option(array(\'YES\', \'NO\'), ', now())");

		$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Disabled countries', 'MODULE_PAYMENT_BILLMATECARDPAY_DISABLED_COUNTRYIES', 'se,fi,dk,no', 'Disable in these countries<br/>Enter country ISO Code of two characters <br/>se = Sweden<br/>fi = Finland<br/>dk = Denmark<br/>no = Norway', '9', '0', now())");

		$this->updateCancelStatus();
	}

	function remove() {
		global $db;
		$db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
	}

	function keys() {
		return array('MODULE_PAYMENT_BILLMATECARDPAY_STATUS',
		'MODULE_PAYMENT_BILLMATECARDPAY_ORDER_STATUS_ID',
		'MODULE_PAYMENT_BILLMATECARDPAY_EID',
		'MODULE_PAYMENT_BILLMATECARDPAY_CANCEL',
		'MODULE_PAYMENT_BILLMATECARDPAY_SECRET',
		'MODULE_PAYMENT_BILLMATECARDPAY_ARTNO',
		'MODULE_PAYMENT_BILLMATECARDPAY_AUTHENTICATION_MODE',
		'MODULE_PAYMENT_BILLMATECARDPAY_DISABLED_COUNTRYIES',
		'MODULE_PAYMENT_BILLMATECARDPAY_ORDER_LIMIT',
		'MODULE_PAYMENT_BILLMATECARDPAY_ORDER_TOTAL_IGNORE',
		'MODULE_PAYMENT_BILLMATECARDPAY_TESTMODE',
		'MODULE_PAYMENT_BILLMATECARDPAY_ZONE',
		'MODULE_PAYMENT_BILLMATECARDPAY_NAME_MODE',
		'MODULE_PAYMENT_BILLMATECARDPAY_3DSECURE_MODE',
		'MODULE_PAYMENT_BILLMATECARDPAY_SORT_ORDER');
	}

}
$i = $includeLoopVariable;
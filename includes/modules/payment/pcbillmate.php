<?php
/**
 *  Copyright 2010 BILLMATE AB. All rights reserved.
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
 *  THIS SOFTWARE IS PROVIDED BY BILLMATE AB "AS IS" AND ANY EXPRESS OR IMPLIED
 *  WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND
 *  FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL BILLMATE AB OR
 *  CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 *  CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 *  SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
 *  ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 *  NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF
 *  ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *  The views and conclusions contained in the software and documentation are those of the
 *  authors and should not be interpreted as representing official policies, either expressed
 *  or implied, of BILLMATE AB.
 *
 */

$includeLoopVariable = $i;
@include_once(DIR_FS_CATALOG . DIR_WS_CLASSES . 'billmate/billmate_lang.php');
require_once DIR_FS_CATALOG . DIR_WS_CLASSES.'billmate/commonfunctions.php';
require_once(DIR_FS_CATALOG . DIR_WS_CLASSES . 'billmate/utf8.php');
require_once(DIR_FS_CATALOG . DIR_WS_CLASSES . 'billmate/Billmate.php');;


class pcbillmate {
    var $code, $title, $description, $enabled, $pcbillmate_testmode, $jQuery;
    var $display_pc_threshold = 1000;
    var $ma_minimum = 50;

    // class constructor
    function pcbillmate() {
        global $order, $currency, $cart, $currencies, $customer_id, $customer_country_id, $pcbillmate_testmode;
        $this->jQuery = true;
        $this->code = 'pcbillmate';

        if(strpos($_SERVER['SCRIPT_FILENAME'],'admin')) {
            $this->title = MODULE_PAYMENT_PCBILLMATE_TEXT_TITLE;
        }
        else {
            //$tmp = explode('Billmate', MODULE_PAYMENT_PCBILLMATE_TEXT_TITLE);
            //$this->title = $tmp[0] . 'Billmate';
			$this->title = MODULE_PAYMENT_PCBILLMATE_FRONTEND_TEXT_TITLE;
        }

        $this->sort_order = MODULE_PAYMENT_PCBILLMATE_SORT_ORDER;

        $this->pcbillmate_testmode = false;
        if ((MODULE_PAYMENT_PCBILLMATE_TESTMODE == 'True')) {
            $this->pcbillmate_testmode = true;
            $this->title .= ' '.MODULE_PAYMENT_PCBILLMATE_TESTMODE_TITLE;
        }

        $this->description = MODULE_PAYMENT_PCBILLMATE_TEXT_DESCRIPTION . "<br />Version: ".BILLPLUGIN_VERSION;
        $this->enabled = ((MODULE_PAYMENT_PCBILLMATE_STATUS == 'True') ? true : false);

        if($this->enabled) {
            $this->description .= '<br /><b>Click <a href="modules.php?set=payment&module=pcbillmate&get_pclasses=true">here</a> to update your pclasses</b><br />';
        }

        $currencyValid = array('SEK' );
        $countryValid  = array('SE');
        $enabled_countries = explode(',',
                                trim( 
                                    strtolower(MODULE_PAYMENT_PCBILLMATE_ENABLED_COUNTRYIES),
                                    ','
                                ).','.
                                trim( 
                                    strtoupper(MODULE_PAYMENT_PCBILLMATE_ENABLED_COUNTRYIES),
                                    ','
                                 )
                              );
		if( $order->billing == null ){
			$billing = $_SESSION['billmate_billing'];
		}else{
			$billing = $_SESSION['billmate_billing'] = $order->billing;
		}
		$availablecountries = array_intersect($countryValid,$enabled_countries);
        if (!in_array(strtoupper($currency),$currencyValid)) {
            $this->enabled = false;
        }
        else {
            if(!empty($billing) && is_array($billing)) {
                if(!in_array(strtoupper($billing['country']['iso_code_2']),$availablecountries)) {
                    $this->enabled = false;
                }
            }
            else {
                $query = tep_db_query("SELECT countries_iso_code_2 FROM countries WHERE countries_id = " . (int)$_SESSION['customer_country_id']);
                $result = tep_db_fetch_array($query);
        
                if(is_array($result)) {
                    if(!in_array(strtoupper($result['countries_iso_code_2']),$countryValid)) {
                        $this->enabled = false;
                    }
                    $this->enabled = $this->enabled && in_array(strtoupper($result['countries_iso_code_2']),$enabled_countries);
                }
                else {
                    $this->enabled = false;
                }
            }
        }    
		
        if(is_object($currencies)) {
            $er = $currencies->get_value($currency);
        }else {
            $er = 1;
        }
        if (!empty($order) && $order->info['total']* $er > MODULE_PAYMENT_PCBILLMATE_ORDER_LIMIT) {
            $this->enabled = false;
        }

	    if($order->info['total'] * $er < MODULE_PAYMENT_PCBILLMATE_MIN_ORDER_LIMIT)
		    $this->enabled = false;

        if ((int)MODULE_PAYMENT_PCBILLMATE_ORDER_STATUS_ID > 0) {
            $this->order_status = MODULE_PAYMENT_PCBILLMATE_ORDER_STATUS_ID;
        }
        if (is_object($order)) {
            $this->update_status();
        }

        $this->form_action_url = tep_href_link(FILENAME_CHECKOUT_PROCESS,
                '', 'SSL', false);
    }

    // class methods
    function update_status() {
        global $order;
        if ($this->enabled == true && (int)MODULE_PAYMENT_PCBILLMATE_ZONE > 0) {
            $check_flag = false;
            $check_query = tep_db_query("select zone_id from " .
                    TABLE_ZONES_TO_GEO_ZONES .
                    " where geo_zone_id = '" .
                    MODULE_PAYMENT_PCBILLMATE_ZONE .
                    "' and zone_country_id = '" .
                    $order->billing['country']['id'] .
                    "' order by zone_id");

            while ($check = tep_db_fetch_array($check_query)) {
                if ($check['zone_id'] < 1) {
                    $check_flag = true;
                    break;
                } elseif ($check['zone_id'] == $order->billing['zone_id']) {
                    $check_flag = true;
                    break;
                }
            }

            if ($check_flag == false) {
                $this->enabled = false;
            }
        }
    }

    function javascript_validation() {
	    $script = '
			if(payment_value == "'.$this->code.'"){
				var formdata = $(\'form[name="checkout_payment"]\').serializeArray();

				if($(\'input[name="success"]\') && $(\'input[name="success"]\').val() == "true"){
					return true;
				}
				$.ajax({
					url: "'.tep_href_link('ext/modules/payment/billmate/getaddress.php?method='.$this->code, '', 'SSL').'",
					data: formdata,
					method: "POST",
					success:function(response){
						var result = JSON.parse(response);
						if(result.success){
							jQuery(":input[name=pcbillmate_pnum]").after("<input type=\'hidden\' name=\'success\' value=\'true\'/>");
	                        jQuery(":input[name=pcbillmate_pnum]").parents("form").submit()
						}
						else if(result.popup) {
							ShowMessage(result.content,"'.MODULE_PAYMENT_BILLMATE_ADDRESS_WRONG.'");

						}
						else {
							alert(result.content);
						}
					}
				})
				return false;
			}


		';
	    return $script;
    }

    function selection() {
        global $pcbillmate_testmode, $order, $customer_id, $currencies, $KRED_ISO3166_SE, $currency, $user_billing, $shipping,$languages_id,$cart_billmate_card_ID;

        //Set the right Host and Port
        $livemode = $this->pcbillmate_testmode == false;

        require_once(DIR_FS_CATALOG . DIR_WS_CLASSES . 'billmate/billmateutils.php');

        $eid = MODULE_PAYMENT_PCBILLMATE_EID;
        $secret = MODULE_PAYMENT_PCBILLMATE_SECRET;

        if (tep_session_is_registered('cart_billmate_card_ID')) {
            $order_id = $insert_id = $cart_billmate_card_ID;

            $check_query = tep_db_query('select orders_id from ' . TABLE_ORDERS_STATUS_HISTORY . ' where orders_id = "' . (int)$order_id . '" limit 1');

            if (tep_db_num_rows($check_query) < 1) {
                tep_db_query('delete from ' . TABLE_ORDERS . ' where orders_id = "' . (int)$order_id . '"');
                tep_db_query('delete from ' . TABLE_ORDERS_TOTAL . ' where orders_id = "' . (int)$order_id . '"');
                tep_db_query('delete from ' . TABLE_ORDERS_STATUS_HISTORY . ' where orders_id = "' . (int)$order_id . '"');
                tep_db_query('delete from ' . TABLE_ORDERS_PRODUCTS . ' where orders_id = "' . (int)$order_id . '"');
                tep_db_query('delete from ' . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . ' where orders_id = "' . (int)$order_id . '"');
                tep_db_query('delete from ' . TABLE_ORDERS_PRODUCTS_DOWNLOAD . ' where orders_id = "' . (int)$order_id . '"');

                tep_session_unregister('cart_billmate_card_ID');
            }
        }

        $find_personnummer_field_query =
                tep_db_query("show columns from " . TABLE_CUSTOMERS);

        $has_personnummer = false;
        $has_dob = false;

        while($fields = tep_db_fetch_array($find_personnummer_field_query)) {
            if ($fields['Field'] == "customers_personnummer")
                $has_personnummer = true;
            if ($fields['Field'] == "customers_dob")
                $has_dob = true;
        }

        if ($has_personnummer) {
            $customer_query = tep_db_query("select customers_personnummer from " .
                    TABLE_CUSTOMERS . " where customers_id = '" . (int)$customer_id."'");
            $customer = tep_db_fetch_array($customer_query);

            $personnummer = $customer['customers_personnummer'];
        }
        else if ($has_dob) {
            $customer_query = tep_db_query("select DATE_FORMAT(customers_dob, '%Y%m%d') AS customers_dob from " .
                    TABLE_CUSTOMERS . " where customers_id = '" . (int)$customer_id."'");
            $customer = tep_db_fetch_array($customer_query);
            $personnummer = ""; //$personnummer = $customer['customers_dob'];
        }
        else
            $personnummer = "";

        if (MODULE_PAYMENT_PCBILLMATE_PRE_POPULATE == "False")
            $personnummer = "";

        $er = $currencies->get_value($currency);
        $total = $order->info['total']*$er;
        $default = ( isset($_SESSION['pcbillmate_pclass']) ) ? $_SESSION['pcbillmate_pclass'] : '';

        //Show price excl. tax. if display with tax isn't true.
        if(DISPLAY_PRICE_WITH_TAX != 'true') {
            $total -= ($order->info['tax'])*$er;
        }
		// if not free ship
	    if($shipping['id'] != 'free_free')
	    {
		    list($module, $method) = explode('_', $shipping['id']);

		    require(DIR_WS_CLASSES.'shipping.php');
		    $shipping_modules = new shipping;
			// Get shipping quote for set module
		    $shipp = $shipping_modules->quote($method, $module);
		    if(isset($shipp[0]['tax']))
			$total += $order->info['shipping_cost'] * ($shipp[0]['tax']/100);
	    }
        //Get and calculate monthly costs for all pclasses
        $languageCode = tep_db_fetch_array(tep_db_query("select code from languages where languages_id = " . $languages_id));
        $languageCode = tep_db_fetch_array(tep_db_query("select code from languages where languages_id = " . $languages_id));
        if(!in_array($languageCode['code'],array('sv','en','se')))
            $languageCode['code'] = 'en';
        $languageCode['code'] = $languageCode['code'] == 'se' ? 'sv' : $languageCode['code'];

        $pclasses = BillmateUtils::calc_monthly_cost($total, MODULE_PAYMENT_PCBILLMATE_PCLASS_TABLE, $order->billing['country']['iso_code_2'], 0,$languageCode['code'],MODULE_PAYMENT_PCBILLMATE_MONTH);
        
        $lowest = BillmateUtils::get_cheapest_pclass($pclasses,$total);

        //Disable payment option if no pclasses are available
        if(count($pclasses) == 0) {
            $this->enabled = false;
            return false;
        }

        if(is_array($lowest)) {
            $this->title = str_replace('xx', $currencies->format($lowest['minpay'], false), MODULE_PAYMENT_PCBILLMATE_TITLE);
            if($this->pcbillmate_testmode) {
                $this->title .= ' '.MODULE_PAYMENT_PCBILLMATE_TESTMODE_TITLE;
            }
        }

        $user_billing = isset($_SESSION['user_billing'])? $_SESSION['user_billing'] : array();
		
        empty($user_billing['pcbillmate_pnum']) ? $pcbillmate_pnum = $personnummer : $pcbillmate_pnum = $user_billing['pcbillmate_pnum'];
        empty($user_billing['pcbillmate_phone']) ? $pcbillmate_phone = $order->customer['telephone'] : $pcbillmate_phone = $user_billing['pcbillmate_phone'];
        empty($user_billing['pcbillmate_email']) ? $pcbillmate_email = '' : $pcbillmate_email = $user_billing['pcbillmate_email'];

        $error = isset($_SESSION['WrongAddress'])?$_SESSION['WrongAddress']:'';
        unset($_SESSION['WrongAddress']);
        //Fade in/fade out code for the module
        $js = ($this->jQuery) ? BillmateUtils::get_display_jQuery($this->code) : "";


        $fields=array(
                array('title' => '<img src="'.HTTP_SERVER.DIR_WS_HTTP_CATALOG.'/images/billmate/'.$languageCode['code'].'/partpayment.png" />',
                        'field' => '<script type="text/javascript">
                          if(!window.jQuery){
	                          var jq = document.createElement("script");
	                          jq.type = "text/javascript";
	                          jq.src = "'.HTTP_SERVER.DIR_WS_HTTP_CATALOG.'jquery.js";
	                          document.getElementsByTagName("head")[0].appendChild(jq);
                          }
</script>'.$js.$error),
                array('title' => sprintf(MODULE_PAYMENT_PCBILLMATE_CONDITIONS, $eid),
	                'field' => "<a href=\"#\" id=\"pcbillmate\" onclick=\"ShowBillmateInvoicePopup(event);return false;\"></a>"),
                array('title' => MODULE_PAYMENT_PCBILLMATE_CHOOSECONSUMERCREDIT,
                        'field' => tep_draw_pull_down_menu('pcbillmate_pclass', $pclasses, $default)),
                array('title' => MODULE_PAYMENT_PCBILLMATE_PERSON_NUMBER,
                        'field' => tep_draw_input_field('pcbillmate_pnum',
                        $pcbillmate_pnum)),
                array('title' => "<link rel='stylesheet' href='".HTTP_SERVER.DIR_WS_HTTP_CATALOG."/billmatestyle.css'/>",
                        'field' => tep_draw_hidden_field('pcbillmate_phone',
                        $pcbillmate_phone)),
                array('title' => tep_draw_checkbox_field('pcbillmate_email',
                    $order->customer['email_address'], !empty($pcbillmate_email) || $user_billing['pcbillmate_pnum'] == NULL).sprintf(MODULE_PAYMENT_PCBILLMATE_EMAIL, $user_billing['billmate_email']),
                        'field' =>  ''));

        //Show yearly salary if order total is above set "limit".
        

        //Shipping/billing address notice
        $fields[] = array('title' => MODULE_PAYMENT_PCBILLMATE_ADDR_TITLE.' '.MODULE_PAYMENT_PCBILLMATE_ADDR_NOTICE, 'field' => '');

        if(DISPLAY_PRICE_WITH_TAX != 'true') {
            $fields[] = array('title' => '', 'field' => '<font color="red">'.MODULE_PAYMENT_PCBILLMATE_WITHOUT_TAX.'</font>');
        }

        return array('id' => $this->code,
                'module' => $this->title,
                'fields' => $fields);
    }

    function pre_confirmation_check() {
        global $pcbillmate_testmode, $order, $GA_OLD, $KRED_SE_PNO, $user_billing,$languages_id,$billmate_pno;

        //Set the right Host and Port
        $livemode = $this->pcbillmate_testmode == false;

        require(DIR_FS_CATALOG . DIR_WS_CLASSES . 'billmate/billmateutils.php');

        //INCLUDE THE VALIDATION FILE
        @include_once(DIR_FS_CATALOG . DIR_WS_CLASSES . 'billmate/validation.php');

        // Set error reasons
        $errors = array();

        //Fill user array with billing details
        $user_billing['pcbillmate_pnum'] = $_POST['pcbillmate_pnum'];
        $user_billing['pcbillmate_phone'] = $_POST['pcbillmate_phone'];
        $user_billing['pcbillmate_email'] = $_POST['pcbillmate_email'];

        //Store values into Session
        tep_session_register('user_billing');

        if (!validate_pno_se($_POST['pcbillmate_pnum'])) {
            $errors[] = MODULE_PAYMENT_PCBILLMATE_PERSON_NUMBER;
        }

        if (!validate_email($_POST['pcbillmate_email'])) {
            $errors[] = sprintf( MODULE_PAYMENT_PCBILLMATE_EMAIL, $order->customer['email_address']);
        }

        if (!empty($errors)) {
            $error_message = implode(', ', $errors);
            tep_redirect(BillmateUtils::error_link(FILENAME_CHECKOUT_PAYMENT, 'payment_error=pcbillmate&error='.$error_message, 'SSL'));
        }

        $pno = $this->pcbillmate_pnum = $_POST['pcbillmate_pnum'];
        $_SESSION['pcbillmate_pclass'] = $this->pcbillmate_pclass = $_POST['pcbillmate_pclass'];
        $eid = MODULE_PAYMENT_PCBILLMATE_EID;
        $secret = MODULE_PAYMENT_PCBILLMATE_SECRET;

        $pnoencoding = $KRED_SE_PNO;

        $billmate_pno = $pno;
        tep_session_register('billmate_pno');
        $type = $GA_OLD;
	    $languageCode = tep_db_fetch_array(tep_db_query("select code from languages where languages_id = " . $languages_id));
        $languageCode['code']  = (strtolower($languageCode['code']) == 'se') ? 'sv' : $languageCode['code'];
	    if(!defined('BILLMATE_LANGUAGE')) define('BILLMATE_LANGUAGE',$languageCode['code']);
        if(!defined('BILLMATE_SERVER')) define('BILLMATE_SERVER','2.1.7');
		$k = new BillMate($eid,$secret,true, $this->pcbillmate_testmode, false);
		$result = (object)$k->GetAddress(array('pno'=>$pno));

		if (isset($result->message) || empty($result) || !is_object($result)) {
            tep_redirect(BillmateUtils::error_link(FILENAME_CHECKOUT_PAYMENT,
                    'payment_error=pcbillmate&error='.strip_tags( utf8_encode($result->message) ),
                    'SSL', true, false));
        }
		$result->firstname = convertToUTF8($result->firstname);
		$result->lastname = convertToUTF8($result->lastname);
		$result->street = convertToUTF8($result->street);
		$result->zip = convertToUTF8($result->zip);
		$result->city = convertToUTF8($result->city);
		$result->country = 209;

        $fullname = $order->billing['firstname'].' '.$order->billing['lastname'] .' '.$order->billing['company'];
		if( empty ( $result->firstname ) ){
			$apiName = $fullname;
		} else {
			$apiName  = $result->firstname.' '.$result->lastname;
		}
        $this->addrs = $result;

        $firstArr = explode(' ', $order->billing['firstname']);
        $lastArr  = explode(' ', $order->billing['lastname']);
        
        if( empty( $result->firstname ) ){
            $apifirst = $firstArr;
            $apilast  = $lastArr ;
        }else {
            $apifirst = explode(' ', $result->firstname );
            $apilast  = explode(' ', $result->lastname );
        }
        $matchedFirst = array_intersect($apifirst, $firstArr );
        $matchedLast  = array_intersect($apilast, $lastArr );
        $apiMatchedName   = !empty($matchedFirst) && !empty($matchedLast);

		$addressNotMatched = !isEqual($result->street, $order->billing['street_address'] ) ||
		    !isEqual($result->zip, $order->billing['postcode']) || 
		    !isEqual($result->city, $order->billing['city']) || 
		    !isEqual($result->country, $order->billing['country']['iso_code_2']);

        $shippingAndBilling =  !$apiMatchedName ||
		    !isEqual($order->billing['street_address'],  $order->delivery['street_address'] ) ||
		    !isEqual($order->billing['postcode'], $order->delivery['postcode']) || 
		    !isEqual($order->billing['city'], $order->delivery['city']) || 
		    !isEqual($order->billing['country']['iso_code_3'], $order->delivery['country']['iso_code_3']) ;
		

        if( $addressNotMatched || $shippingAndBilling ){
            if( empty($_POST['geturl'])){
	            $html = '<b>'.MODULE_PAYMENT_PCBILLMATE_CORRECT_ADDRESS.' </b><br/><span style="line-height: 1.4em;">'.($result->firstname).' '.$result->lastname.'<br>'.$result->street.'<br>'.$result->zip.' '.$result->city.'</span><div style="margin-top:1em;"><input type="button" value="'.MODULE_PAYMENT_PCBILLMATE_YES.'" onclick="updateAddress();" class="billmate_button"/> <a onclick="closefunc(this)" class="linktag"/>'.MODULE_PAYMENT_PCBILLMATE_NO.'</a></div> ';

	            $code = '<style type="text/css">
.checkout-heading {
    background: none repeat scroll 0 0 #F8F8F8;
    border: 1px solid #DBDEE1;
    color: #555555;
    font-size: 13px;
    font-weight: bold;
    margin-bottom: 15px;
    padding: 8px;
}
#cboxClose{
 display:none!important;
 visibility:hidden!important;
}
.button:hover{
    background:#0B6187!important;
}

.button {
    background-color: #1DA9E7;
    border: 0 none;
    border-radius: 8px 8px 8px 8px;
    box-shadow: 2px 2px 2px 1px #EAEAEA;
    color: #FFFFFF;
    cursor: pointer;
    font-family: arial;
    font-size: 14px!important;
    font-weight: bold;
    padding: 3px 17px;
}
#cboxContent{
    margin:0px!important;
}
	            </style>
	            <script type="text/javascript" src="'.HTTP_SERVER.DIR_WS_HTTP_CATALOG.'billmatepopup.js"></script>
	            <script type="text/javascript">
	            function updateAddresspart(){
    	            jQuery(":input[name=pcbillmate_pnum]").after("<input type=\'hidden\' name=\'geturl\' value=\'true\'/>");
    	            jQuery(":input[name=pcbillmate_pnum]").parents("form").submit()
	            }
	            function closefunc(obj){
    	            modalWin.HideModalPopUp();
	            };ShowMessage(\''.$html.'\',\''.MODULE_PAYMENT_PCBILLMATE_ADDRESS_WRONG.'\');</script>';
	            unset($_SESSION['WrongAddress']);
                $WrongAddress = $code;
                global $messageStack;
                $_SESSION['WrongAddress'] = $WrongAddress;
                tep_redirect(BillmateUtils::error_link(FILENAME_CHECKOUT_PAYMENT, 'payment_error=pcbillmate&error=invalidaddress', 'SSL'));
	        }else{
	            if(!match_usernamevp( $fullname , $apiName)){
                    if($result->firstname == "") {
                        $this->pcbillmate_fname = $order->billing['firstname'];
                        $this->pcbillmate_lname = $order->billing['lastname'];
						$this->pccompany_name   = $result->lastname;
                    }else {
                        $this->pcbillmate_fname = $result->firstname;
                        $this->pcbillmate_lname = $result->lastname;
						$this->pccompany_name   = '';
                    }
                }
                $this->pcbillmate_street = $result->street;
                $this->pcbillmate_postno = $result->zip;
                $this->pcbillmate_city = $result->city;
                $country_query = tep_db_query("select countries_id from " . TABLE_COUNTRIES . " where countries_iso_code_2 = '" .$result->country . "'");
                $country = tep_db_fetch_array($country_query);
                global $customer_id;
                $data = array(
                    'entry_firstname'       =>$this->pcbillmate_fname,
                    'entry_lastname'        =>$this->pcbillmate_lname,
                    'entry_street_address'  =>$this->pcbillmate_street,
                    'entry_postcode'        =>$this->pcbillmate_postno,
                    'entry_city'            =>$this->pcbillmate_city,
                    'entry_country_id'      =>$country['countries_id'],
                    'customers_id'          => $customer_id
                );

                
                $order->delivery['firstname'] = $this->pcbillmate_fname;
                $order->billing['firstname'] = $this->pcbillmate_fname;
                $order->delivery['lastname'] = $this->pcbillmate_lname;
                $order->billing['lastname'] = $this->pcbillmate_lname;
                $order->delivery['company'] = $this->pccompany_name;
                $order->billing['company'] = $this->pccompany_name;
                $order->delivery['street_address'] = $this->pcbillmate_street;
                $order->billing['street_address'] = $this->pcbillmate_street;
                $order->delivery['postcode'] = $this->pcbillmate_postno;
                $order->billing['postcode'] = $this->pcbillmate_postno;
                $order->delivery['city'] = $this->pcbillmate_city;
                $order->billing['city'] = $this->pcbillmate_city;
				$order->billing['suburb'] = $order->delivery['suburb'] = '';

                $order->delivery['telephone'] = $_POST['billmate_phone'];
                $order->billing['telephone'] = $_POST['billmate_phone'];

                $this->billmate_invoice_type = $_POST['billmate_invoice_type'];

                //Set same country information to delivery
                $order->delivery['state'] = $order->billing['state'];
                $order->delivery['zone_id'] = $order->billing['zone_id'];
                $order->delivery['country_id'] = $order->billing['country_id'];
                $order->delivery['country']['id'] = $order->billing['country']['id'];
                $order->delivery['country']['title'] = $order->billing['country']['title'];
                $order->delivery['country']['iso_code_2'] = $order->billing['country']['iso_code_2'];
                $order->delivery['country']['iso_code_3'] = $order->billing['country']['iso_code_3'];

	        }
        }
    }

    function confirmation() {
        global $cartID, $cart_billmate_card_ID, $customer_id, $languages_id, $order, $order_total_modules, $currencies;

        if (tep_session_is_registered('cart_billmate_card_ID')) {
            $order_id = $cart_billmate_card_ID;

            $curr_check = tep_db_query("select currency from " . TABLE_ORDERS . " where orders_id = '" . (int)$order_id . "'");
            $curr = tep_db_fetch_array($curr_check);

            if ( ($curr['currency'] != $order->info['currency']) ) {
                $check_query = tep_db_query('select orders_id from ' . TABLE_ORDERS_STATUS_HISTORY . ' where orders_id = "' . (int)$order_id . '" limit 1');

                if (tep_db_num_rows($check_query) < 1) {
                    tep_db_query('delete from ' . TABLE_ORDERS . ' where orders_id = "' . (int)$order_id . '"');
                    tep_db_query('delete from ' . TABLE_ORDERS_TOTAL . ' where orders_id = "' . (int)$order_id . '"');
                    tep_db_query('delete from ' . TABLE_ORDERS_STATUS_HISTORY . ' where orders_id = "' . (int)$order_id . '"');
                    tep_db_query('delete from ' . TABLE_ORDERS_PRODUCTS . ' where orders_id = "' . (int)$order_id . '"');
                    tep_db_query('delete from ' . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . ' where orders_id = "' . (int)$order_id . '"');
                    tep_db_query('delete from ' . TABLE_ORDERS_PRODUCTS_DOWNLOAD . ' where orders_id = "' . (int)$order_id . '"');
                }

                $insert_order = true;
            }
        } else {
            $insert_order = true;
        }

        if ($insert_order == true) {
            $order_totals = array();
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
                        if ($GLOBALS[$class]->enabled) {
                            for ($i=0, $n=sizeof($GLOBALS[$class]->output); $i<$n; $i++) {
                                if (tep_not_null($GLOBALS[$class]->output[$i]['title']) && tep_not_null($GLOBALS[$class]->output[$i]['text'])) {
                                    $order_totals[] = array('code' => $GLOBALS[$class]->code,
                                        'title' => $GLOBALS[$class]->output[$i]['title'],
                                        'text' => $GLOBALS[$class]->output[$i]['text'],
                                        'value' => $GLOBALS[$class]->output[$i]['value'],
                                        'sort_order' => $GLOBALS[$class]->sort_order);
                                }
                            }
                        }
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
                'payment_method' => $order->info['payment_method'],
                'cc_type' => $order->info['cc_type'],
                'cc_owner' => $order->info['cc_owner'],
                'cc_number' => $order->info['cc_number'],
                'cc_expires' => $order->info['cc_expires'],
                'date_purchased' => 'now()',
                'orders_status' => 0,
                'currency' => $order->info['currency'],
                'currency_value' => $order->info['currency_value']);

            tep_db_perform(TABLE_ORDERS, $sql_data_array);

            $insert_id = tep_db_insert_id();

            for ($i=0, $n=sizeof($order_totals); $i<$n; $i++) {
                $sql_data_array = array('orders_id' => $insert_id,
                    'title' => $order_totals[$i]['title'],
                    'text' => $order_totals[$i]['text'],
                    'value' => $order_totals[$i]['value'],
                    'class' => $order_totals[$i]['code'],
                    'sort_order' => $order_totals[$i]['sort_order']);

                tep_db_perform(TABLE_ORDERS_TOTAL, $sql_data_array);
            }

            $customer_notification = (SEND_EMAILS == 'true') ? '1' : '0';
            $sql_data_array = array('orders_id' => $insert_id,
                'orders_status_id' => 0,
                'date_added' => 'now()',
                'customer_notified' => 0,
                'comments' => $order->info['comments']);
            tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);

            for ($i=0, $n=sizeof($order->products); $i<$n; $i++) {
                $sql_data_array = array('orders_id' => $insert_id,
                    'products_id' => tep_get_prid($order->products[$i]['id']),
                    'products_model' => $order->products[$i]['model'],
                    'products_name' => $order->products[$i]['name'],
                    'products_price' => $order->products[$i]['price'],
                    'final_price' => $order->products[$i]['final_price'],
                    'products_tax' => $order->products[$i]['tax'],
                    'products_quantity' => $order->products[$i]['qty']);

                tep_db_perform(TABLE_ORDERS_PRODUCTS, $sql_data_array);

                $order_products_id = tep_db_insert_id();

                $attributes_exist = '0';
                if (isset($order->products[$i]['attributes'])) {
                    $attributes_exist = '1';
                    for ($j=0, $n2=sizeof($order->products[$i]['attributes']); $j<$n2; $j++) {
                        if (DOWNLOAD_ENABLED == 'true') {
                            $attributes_query = "select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix, pad.products_attributes_maxdays, pad.products_attributes_maxcount , pad.products_attributes_filename
                                       from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                                       left join " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad
                                       on pa.products_attributes_id=pad.products_attributes_id
                                       where pa.products_id = '" . $order->products[$i]['id'] . "'
                                       and pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "'
                                       and pa.options_id = popt.products_options_id
                                       and pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "'
                                       and pa.options_values_id = poval.products_options_values_id
                                       and popt.language_id = '" . $languages_id . "'
                                       and poval.language_id = '" . $languages_id . "'";
                            $attributes = tep_db_query($attributes_query);
                        } else {
                            $attributes = tep_db_query("select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa where pa.products_id = '" . $order->products[$i]['id'] . "' and pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "' and pa.options_id = popt.products_options_id and pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "' and pa.options_values_id = poval.products_options_values_id and popt.language_id = '" . $languages_id . "' and poval.language_id = '" . $languages_id . "'");
                        }
                        $attributes_values = tep_db_fetch_array($attributes);

                        $sql_data_array = array('orders_id' => $insert_id,
                            'orders_products_id' => $order_products_id,
                            'products_options' => $attributes_values['products_options_name'],
                            'products_options_values' => $attributes_values['products_options_values_name'],
                            'options_values_price' => $attributes_values['options_values_price'],
                            'price_prefix' => $attributes_values['price_prefix']);

                        tep_db_perform(TABLE_ORDERS_PRODUCTS_ATTRIBUTES, $sql_data_array);

                        if ((DOWNLOAD_ENABLED == 'true') && isset($attributes_values['products_attributes_filename']) && tep_not_null($attributes_values['products_attributes_filename'])) {
                            $sql_data_array = array('orders_id' => $insert_id,
                                'orders_products_id' => $order_products_id,
                                'orders_products_filename' => $attributes_values['products_attributes_filename'],
                                'download_maxdays' => $attributes_values['products_attributes_maxdays'],
                                'download_count' => $attributes_values['products_attributes_maxcount']);

                            tep_db_perform(TABLE_ORDERS_PRODUCTS_DOWNLOAD, $sql_data_array);
                        }
                    }
                }
            }

            $cart_billmate_card_ID =  $insert_id;
            tep_session_register('cart_billmate_card_ID');
        }
        return array('title' => MODULE_PAYMENT_PCBILLMATE_TEXT_CONFIRM_DESCRIPTION);
    }

    function process_button() {
        global $order, $order_total_modules, $pcbillmate_ot, $shipping,$pclass,$cart_billmate_card_ID;
        $counter = 1;
        $process_button_string = '';
        $checked = true;
		$process_button_string .=
		tep_draw_hidden_field('addr_num', $counter, $checked, '').
		tep_draw_hidden_field('pcbillmate_fname'.$counter, convertToUTF8($order->billing['firstname'])).
		tep_draw_hidden_field('pcbillmate_lname'.$counter, convertToUTF8($order->billing['lastname'])).
		tep_draw_hidden_field('pcbillmate_street'.$counter, convertToUTF8($order->billing['street_address'])).
		tep_draw_hidden_field('pcbillmate_postno'.$counter, $order->billing['postcode']).
		tep_draw_hidden_field('pcbillmate_company'.$counter, convertToUTF8($order->billing['company'])).
		tep_draw_hidden_field('pcbillmate_city'.$counter, convertToUTF8($order->billing['city'])).
		tep_draw_hidden_field('pcbillmate_country'.$counter,  convertToUTF8($this->addrs->country)).
		tep_draw_hidden_field('pcbillmate_pclass'.$counter,$this->pcbillmate_pclass).
		tep_draw_hidden_field('pcbillmate_pnum'.$counter,$this->pcbillmate_pnum);


        $pclass = $this->pcbillmate_pclass;
        tep_session_register('pclass');
        $order_totals = $order_total_modules->modules;

        if (is_array($order_totals)) {
            reset($order_totals);
            $j = 0;
            $table = preg_split("/[,]/", MODULE_PAYMENT_PCBILLMATE_ORDER_TOTAL_IGNORE);

            while (list(, $value) = each($order_totals)) {
                $class = substr($value, 0, strrpos($value, '.'));

                if (!$GLOBALS[$class]->enabled) {
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
                    $pcbillmate_ot['code_size_'.$j] = $size;
                    for ($i=0; $i<$size; $i++) {
                        $pcbillmate_ot['title_'.$j.'_'.$i] = html_entity_decode($GLOBALS[$class]->output[$i]['title']);
                        $pcbillmate_ot['text_'.$j.'_'.$i] = $GLOBALS[$class]->output[$i]['text'];
                        if (is_numeric($GLOBALS[$class]->deduction) &&
                                $GLOBALS[$class]->deduction > 0) {
                            $pcbillmate_ot['value_'.$j.'_'.$i] = -$GLOBALS[$class]->deduction;
                        }
                        else {
                            $pcbillmate_ot['value_'.$j.'_'.$i] = $GLOBALS[$class]->output[$i]['value'];

                            // Add tax rate for shipping address and invoice fee
                            if ($class == 'ot_shipping') {
                                //Set Shipping VAT
                                $shipping_id = @explode('_', $shipping['id']);
                                $tax_class = @$GLOBALS[$shipping_id[0]]->tax_class;
                                $tax_rate = 0;
                                if($tax_class > 0) {
                                    $tax_rate = tep_get_tax_rate($tax_class, $order->billing['country']['id'], ($order->billing['zone_id'] > 0) ? $order->billing['zone_id'] : null);
                                }
                                $pcbillmate_ot['tax_rate_'.$j.'_'.$i] = $tax_rate;
                            } else {
                                $pcbillmate_ot['tax_rate_'.$j.'_'.$i] = $GLOBALS[$class]->output[$i]['tax_rate'];
                            }
                        }

                        $pcbillmate_ot['code_'.$j.'_'.$i] = $GLOBALS[$class]->code;
                    }
                    $j += 1;
                }
            }
            $pcbillmate_ot['code_entries'] = $j;
        }

        tep_session_register('pcbillmate_ot');

        $process_button_string .= tep_draw_hidden_field(tep_session_name(),
            tep_session_id());
        //$return = $this->doInvoice();
        $redirect = false;
        $redirect = tep_href_link('ext/modules/payment/billmate/payment.php', 'method=partpay%26order_id='.$cart_billmate_card_ID, 'SSL');

        if($redirect) {
            $process_button_string .= '<script type="text/javascript">
                            String.prototype.replaceAll = function(search, replacement) {
                                var target = this;
                                return target.replace(new RegExp(search, \'g\'), replacement);
                            };
                          if(!window.jQuery){
                          	var jq = document.createElement("script");
                          	jq.type = "text/javascript";
                          	jq.src = "' . HTTP_SERVER . DIR_WS_HTTP_CATALOG . 'jquery.js";
                            jq.onload = redirectLink;
                          	document.getElementsByTagName("head")[0].appendChild(jq);
                          } else {
                            redirectLink();
                          }
                          function redirectLink(){
                                                      jQuery(document).ready(function(){ $("input[name=\'comments\']").remove(); }); $(\'form[name="checkout_confirmation"]\').submit(function(e){e.preventDefault(); window.location = decodeURI("' . $redirect . '").replaceAll("%26","&").replaceAll("%3D","=").replaceAll("%3A",":").replaceAll("%2C",",");});

                          }
                          </script>';
        }
        return $process_button_string;
    }

    function doInvoice($add_order = false ){
        global $order, $customer_id, $currency, $currencies, $sendto, $billto,
               $pcbillmate,$insert_id, $languages_id, $language_id, $language, $currency, $cart_billmate_card_ID,$billmate_pno,$pclass;

        $pcbillmate = $_SESSION['pcbillmate_ot'];

        require_once(DIR_FS_CATALOG . DIR_WS_CLASSES . 'billmate/billmateutils.php');
        if( empty($_POST ) ) $_POST = $_GET;
        //Set the right Host and Port

        $goodsList = array();
        $n = sizeof($order->products);
        $totalValue = 0;
        $taxValue = 0;
        $codes = array();
        $prepareDiscounts = array();
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
                $temp =
                    mk_goods_flags($order->products[$i]['qty'],
                        $order->products[$i]['id'],
                        $order->products[$i]['name'] . $attributes,
                        $price_without_tax,
                        $order->products[$i]['tax'],
                        0,
                        0); //incl VAT
                $totalValue += $temp['withouttax'];
                $taxValue += $temp['tax'];
                $tax1 = (int)$order->products[$i]['tax'];
                if(isset($prepareDiscounts[$tax1])){

                    $prepareDiscounts[$tax1] += $temp['withouttax'];
                } else {
                    $prepareDiscounts[$tax1] = $temp['withouttax'];
                }

                $goodsList[] = $temp;
            } else {
                $temp =
                    mk_goods_flags($order->products[$i]['qty'],
                        $order->products[$i][MODULE_PAYMENT_PCBILLMATE_ARTNO],
                        $order->products[$i]['name'] . $attributes,
                        $price_without_tax,
                        $order->products[$i]['tax'],
                        0,
                        0); //incl VAT
                $totalValue += $temp['withouttax'];
                $taxValue += $temp['tax'];
                $tax1 = (int)$order->products[$i]['tax'];
                if(isset($prepareDiscounts[$tax1])){

                    $prepareDiscounts[$tax1] += $temp['withouttax'];
                } else {
                    $prepareDiscounts[$tax1] = $temp['withouttax'];
                }
                $goodsList[] = $temp;
            }
        }

        // Then the extra charnges like shipping and invoicefee and
        // discount.

        $extra = $pcbillmate['code_entries'];

        //end hack
        for ($j=0 ; $j<$extra ; $j++) {
            $size = $pcbillmate["code_size_".$j];
            for ($i=0 ; $i<$size ; $i++) {
                $value = $pcbillmate["value_".$j."_".$i];
                $name = $pcbillmate["title_".$j."_".$i];
                $tax = $pcbillmate["tax_rate_".$j."_".$i];
                $name = rtrim($name, ":");
                $code = $pcbillmate["code_".$j."_".$i];

                $price_without_tax = $currencies->get_value($currency) * $value * 100;
                if(DISPLAY_PRICE_WITH_TAX == 'true') {
                    $price_without_tax = $price_without_tax/(($tax+100)/100);
                }

                $codes[] = $code;
                if( $code == 'ot_discount' ) { $price_without_tax = 0 - $price_without_tax; }
                if( $code == 'ot_shipping' ){ $shippingPrice = $price_without_tax; $shippingTaxRate = $tax; continue; }

                if ($value != "" && $value != 0) {
                    $totals = $totalValue;
                    foreach($prepareDiscounts as $tax => $value)
                    {
                        $percent = $value / $totals;
                        $price_without_tax_out = $price_without_tax * $percent;
                        $temp = mk_goods_flags(1, "", ($name).' '.(int)$tax.'% '.MODULE_PAYMENT_PCBILLMATE_VAT, $price_without_tax_out, $tax, 0, 0);
                        $totalValue += $temp['withouttax'];
                        $taxValue += $temp['tax'];
                        $goodsList[] = $temp;
                    }
                }

            }
        }

        $secret = MODULE_PAYMENT_PCBILLMATE_SECRET;
        $eid = MODULE_PAYMENT_PCBILLMATE_EID;

        $ship_address = $bill_address = array();
        $countryData = BillmateCountry::getSwedenData();

        $ship_address = array(
            "firstname" => $order->delivery['firstname'],
            "lastname" 	=> $order->delivery['lastname'],
            "company" 	=> $order->delivery['company'],
            "street" 	=> $order->delivery['street_address'],
            "street2" 	=> "",
            "zip" 		=> $order->delivery['postcode'],
            "city" 		=> $order->delivery['city'],
            "country" 	=> $order->delivery['country']['iso_code_2'],
            "phone" 	=> $order->customer['telephone'],
        );

        $bill_address = array(
            "firstname" => $order->billing['firstname'],
            "lastname" 	=> $order->billing['lastname'],
            "company" 	=> $order->billing['company'],
            "street" 	=> $order->billing['street_address'],
            "street2" 	=> "",
            "zip" 		=> $order->billing['postcode'],
            "city" 		=> $order->billing['city'],
            "country" 	=> $order->billing['country']['iso_code_2'],
            "phone" 	=> $order->customer['telephone'],
            "email" 	=> $order->customer['email_address'],
        );

        /*foreach($ship_address as $key => $col ){
             if(is_numeric($col) ) continue;
             $ship_address[$key] = utf8_decode(Encoding::fixUTF8( $col ));
         }
        foreach($bill_address as $key => $col ){
             if(is_numeric($col) ) continue;
             $bill_address[$key] = utf8_decode(Encoding::fixUTF8( $col ));
         }*/

        $ssl = true;
        $debug = false;
        $languageCode = tep_db_fetch_array(tep_db_query("select code from languages where languages_id = " . $languages_id));
        $languageCode['code']  = (strtolower($languageCode['code']) == 'se') ? 'sv' : $languageCode['code'];

        if(!defined('BILLMATE_LANGUAGE')) define('BILLMATE_LANGUAGE',$languageCode['code']);
        if(!defined('BILLMATE_SERVER')) define('BILLMATE_SERVER','2.1.7');
        if(tep_session_is_registered('billmate_pno')){
            $pno = $billmate_pno;
        }

        $k = new BillMate($eid,$secret,$ssl,$this->pcbillmate_testmode,$debug,$codes);
        $invoiceValues = array();
        $lang = $languageCode['code'] == 'se' ? 'sv' : $languageCode['code'];
        $invoiceValues['PaymentData'] = array(	"method" => "4",		//1=Factoring, 2=Service, 4=PartPayment, 8=Card, 16=Bank, 24=Card/bank and 32=Cash.
            "currency" => $currency, //"SEK",
            "paymentplanid" => $pclass,
            "language" => $lang,
            "country" => "SE",
            "orderid" => (string)$cart_billmate_card_ID,
            "bankid" => true,
            "returnmethod" => "GET",
            "accepturl" => tep_href_link(FILENAME_CHECKOUT_PROCESS,'', 'SSL'),
            "cancelurl" => tep_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'),
            "callbackurl" => tep_href_link('ext/modules/payment/billmate/common_ipn.php', '', 'SSL')
        );
        $invoiceValues['PaymentInfo'] = array( 	"paymentdate" => date('Y-m-d'),
            "yourreference" => "",
            "ourreference" => "",
            "projectname" => "",
            "delivery" => "Post",
            "deliveryterms" => "FOB",
        );
        
        $invoiceValues['Customer'] = array(
            'customernr'=> (string)$customer_id,
            'pno'=>$pno,
            'Billing'=> $bill_address,
            'Shipping'=> $ship_address
        );
        $invoiceValues['Articles'] = $goodsList;
        $totalValue += $shippingPrice;
        $taxValue += $shippingPrice * ($shippingTaxRate/100);
        $totaltax = round($taxValue,0);
        $totalwithtax = round($order->info['total']*100,0);
        //$totalwithtax += $shippingPrice * ($shippingTaxRate/100);
        $totalwithouttax = $totalValue;
        $rounding = $totalwithtax - ($totalwithouttax+$totaltax);

        $invoiceValues['Cart'] = array(
            "Handling" => array(
                "withouttax" => 0,
                "taxrate" => 0
            ),
            "Shipping" => array(
                "withouttax" => ($shippingPrice)?round($shippingPrice,0):0,
                "taxrate" => ($shippingTaxRate)?$shippingTaxRate:0
            ),
            "Total" => array(
                "withouttax" => $totalwithouttax,
                "tax" => $totaltax,
                "rounding" => $rounding,
                "withtax" => $totalwithtax,
            )
        );
        $result1 = (object)$k->AddPayment($invoiceValues);
        $result1->raw_response = $k->raw_response;
        if(!isset($result1->code)){
            return $result1;
        }
        else {
            billmate_remove_order($cart_billmate_card_ID,true);
            tep_session_unregister('cart_Billmate_card_ID');
            return $result1;

        }

    }

    function before_process() {
        global $order, $customer_id, $currency, $currencies, $sendto, $billto, $pcbillmate_ot, $pcbillmate_testmode,$languages_id, $cart_billmate_card_ID, $cart, $order_id,$payment;

		//Assigning billing session
		$pcbillmate = $_SESSION['pcbillmate_ot'];

        //Set the right Host and Port
        $livemode = $this->pcbillmate_testmode == false;

        require(DIR_FS_CATALOG . DIR_WS_CLASSES . 'billmate/billmateutils.php');
        require_once DIR_FS_CATALOG . DIR_WS_CLASSES.'/billmate/Billmate.php';

        $secret = MODULE_PAYMENT_PCBILLMATE_SECRET;
        $eid = MODULE_PAYMENT_PCBILLMATE_EID;
        $debug = false;
        $ssl = true;
        $k = new BillMate($eid, $secret,$ssl,$this->pcbillmate_testmode,$debug);

        foreach($_REQUEST as $key => $value){
            $_REQUEST[$key] = stripslashes($value);
        }
        $_DATA = $k->verify_hash($_REQUEST);

        if(!isset($_DATA['status']) || ($_DATA['status'] == 'Cancelled' || $_DATA['status'] == 'Failed')) {

            billmate_remove_order($_DATA['orderid'],true);
            tep_session_unregister('cart_Billmate_card_ID');
            tep_redirect(BillmateUtils::error_link(FILENAME_CHECKOUT_PAYMENT,
                'payment_error=billmate_invoice&error=Please try again.',
                'SSL', true, false));
            return;
        }
        $status = tep_db_query("select orders_status from ".TABLE_ORDERS." where orders_id = {$_DATA['orderid']}");
        $status_array = tep_db_fetch_array( $status );

        $status_history = tep_db_query("select orders_status_history_id from ".TABLE_ORDERS_STATUS_HISTORY.
            " where orders_id = {$_DATA['orderid']} and comments='Billmate_IPN'");

        $status_history_a = tep_db_fetch_array($status_history);

        if( $status_history_a ){
            $already_completed = true;
            tep_session_register('already_completed');
            tep_session_unregister('billmatecardpay_ot');
        }else {
            $already_completed = false;
            tep_session_register('already_completed');
        }
        $products_ordered = '';
        $subtotal = 0;
        $total_tax = 0;
        for ($i=0, $n=sizeof($order->products); $i<$n; $i++) {
// Stock Update - Joao Correia
            if (STOCK_LIMITED == 'true') {
                if (DOWNLOAD_ENABLED == 'true') {
                    $stock_query_raw = "SELECT products_quantity, pad.products_attributes_filename
                                FROM " . TABLE_PRODUCTS . " p
                                LEFT JOIN " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                                ON p.products_id=pa.products_id
                                LEFT JOIN " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad
                                ON pa.products_attributes_id=pad.products_attributes_id
                                WHERE p.products_id = '" . tep_get_prid($order->products[$i]['id']) . "'";
// Will work with only one option for downloadable products
// otherwise, we have to build the query dynamically with a loop
                    $products_attributes = $order->products[$i]['attributes'];
                    if (is_array($products_attributes)) {
                        $stock_query_raw .= " AND pa.options_id = '" . $products_attributes[0]['option_id'] . "' AND pa.options_values_id = '" . $products_attributes[0]['value_id'] . "'";
                    }
                    $stock_query = tep_db_query($stock_query_raw);
                } else {
                    $stock_query = tep_db_query("select products_quantity from " . TABLE_PRODUCTS . " where products_id = '" . tep_get_prid($order->products[$i]['id']) . "'");
                }
                if (tep_db_num_rows($stock_query) > 0) {
                    $stock_values = tep_db_fetch_array($stock_query);
// do not decrement quantities if products_attributes_filename exists
                    if ((DOWNLOAD_ENABLED != 'true') || (!$stock_values['products_attributes_filename'])) {
                        $stock_left = $stock_values['products_quantity'] - $order->products[$i]['qty'];
                    } else {
                        $stock_left = $stock_values['products_quantity'];
                    }
                    tep_db_query("update " . TABLE_PRODUCTS . " set products_quantity = '" . $stock_left . "' where products_id = '" . tep_get_prid($order->products[$i]['id']) . "'");
                    if ( ($stock_left < 1) && (STOCK_ALLOW_CHECKOUT == 'false') ) {
                        tep_db_query("update " . TABLE_PRODUCTS . " set products_status = '0' where products_id = '" . tep_get_prid($order->products[$i]['id']) . "'");
                    }
                }
            }

// Update products_ordered (for bestsellers list)
            tep_db_query("update " . TABLE_PRODUCTS . " set products_ordered = products_ordered + " . sprintf('%d', $order->products[$i]['qty']) . " where products_id = '" . tep_get_prid($order->products[$i]['id']) . "'");

//------insert customer choosen option to order--------
            $attributes_exist = '0';
            $products_ordered_attributes = '';
            if (isset($order->products[$i]['attributes'])) {
                $attributes_exist = '1';
                for ($j=0, $n2=sizeof($order->products[$i]['attributes']); $j<$n2; $j++) {
                    if (DOWNLOAD_ENABLED == 'true') {
                        $attributes_query = "select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix, pad.products_attributes_maxdays, pad.products_attributes_maxcount , pad.products_attributes_filename
                                   from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                                   left join " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad
                                   on pa.products_attributes_id=pad.products_attributes_id
                                   where pa.products_id = '" . $order->products[$i]['id'] . "'
                                   and pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "'
                                   and pa.options_id = popt.products_options_id
                                   and pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "'
                                   and pa.options_values_id = poval.products_options_values_id
                                   and popt.language_id = '" . $languages_id . "'
                                   and poval.language_id = '" . $languages_id . "'";
                        $attributes = tep_db_query($attributes_query);
                    } else {
                        $attributes = tep_db_query("select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa where pa.products_id = '" . $order->products[$i]['id'] . "' and pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "' and pa.options_id = popt.products_options_id and pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "' and pa.options_values_id = poval.products_options_values_id and popt.language_id = '" . $languages_id . "' and poval.language_id = '" . $languages_id . "'");
                    }
                    $attributes_values = tep_db_fetch_array($attributes);

                    $products_ordered_attributes .= "\n\t" . $attributes_values['products_options_name'] . ' ' . $attributes_values['products_options_values_name'];
                }
            }


            $products_ordered .= $order->products[$i]['qty'] . ' x ' . $order->products[$i]['name'] . ' (' . $order->products[$i]['model'] . ') = ' . $currencies->display_price($order->products[$i]['final_price'], $order->products[$i]['tax'], $order->products[$i]['qty']) . $products_ordered_attributes . "\n";
        }

        $email_order = STORE_NAME . "\n" .
            EMAIL_SEPARATOR . "\n" .
            EMAIL_TEXT_ORDER_NUMBER . ' ' . $order_id . "\n" .
            EMAIL_TEXT_DATE_ORDERED . ' ' . strftime(DATE_FORMAT_LONG) . "\n\n";

        if ($order->info['comments']) {
            $email_order .= tep_db_output($order->info['comments']) . "\n\n";
        }

        $email_order .= EMAIL_TEXT_PRODUCTS . "\n" .
            EMAIL_SEPARATOR . "\n" .
            $products_ordered .
            EMAIL_SEPARATOR . "\n";

        if ($order->content_type != 'virtual') {
            $email_order .= "\n" . EMAIL_TEXT_DELIVERY_ADDRESS . "\n" .
                EMAIL_SEPARATOR . "\n" .
                tep_address_label($customer_id, $sendto, 0, '', "\n") . "\n";
        }

        $email_order .= "\n" . EMAIL_TEXT_BILLING_ADDRESS . "\n" .
            EMAIL_SEPARATOR . "\n" .
            tep_address_label($customer_id, $billto, 0, '', "\n") . "\n\n";


        if (is_object($payment)) {
            $email_order .= EMAIL_TEXT_PAYMENT_METHOD . "\n" .
                EMAIL_SEPARATOR . "\n";
            $payment_class = $$payment;
            $email_order .= $payment_class->title . "\n\n";
            if ($payment_class->email_footer) {
                $email_order .= $payment_class->email_footer . "\n\n";
            }
        }
        if(!$already_completed ){
            $languageCode = tep_db_fetch_array(tep_db_query("select code from languages where languages_id = " . $languages_id));
            $languageCode['code']  = (strtolower($languageCode['code']) == 'se') ? 'sv' : $languageCode['code'];

            if(!defined('BILLMATE_LANGUAGE')) define('BILLMATE_LANGUAGE',$languageCode['code']);
            if(!defined('BILLMATE_SERVER')) define('BILLMATE_SERVER','2.1.7');

            $k = new BillMate($eid,$secret,$ssl, $this->pcbillmate_testmode,$debug);
            $result1 = (object)$k->UpdatePayment( array('PaymentData'=> array("number"=>$_DATA['number'], "orderid"=>(string)$_DATA['orderid'])));
        }

        if(is_string($result1) || (isset($result1->message) && is_object($result1))){
            tep_redirect(BillmateUtils::error_link(FILENAME_CHECKOUT_PAYMENT,
                'payment_error=billmatecardpay&error='.$result1->message,
                'SSL', true, false));
        } elseif($already_completed || is_object($result1)) {
            $billmatecard_called_api = true;
            tep_session_register('billmatecard_called_api');
            tep_session_register('billmatecard_api_result');

            // insert address in address book to get correct address in
            // confirmation mail (or fetch correct address from address book
            // if it exists)

            $q = "select countries_id from " . TABLE_COUNTRIES .
                " where countries_iso_code_2 = 'SE'";

            $check_country_query = tep_db_query($q);
            $check_country = tep_db_fetch_array($check_country_query);

            $cid = $check_country['countries_id'];

            $q = "select address_book_id from " . TABLE_ADDRESS_BOOK .
                " where customers_id = '" . (int)$customer_id .
                "' and entry_firstname = '" . $order->delivery['firstname'] .
                "' and entry_lastname = '" . $order->delivery['lastname'] .
                "' and entry_street_address = '" . $order->delivery['street_address'] .
                "' and entry_postcode = '" . $order->delivery['postcode'] .
                "' and entry_city = '" . $order->delivery['city'] .
                "' and entry_company = '" . $order->delivery['company'] . "'";
            $check_address_query = tep_db_query($q);
            $check_address = tep_db_fetch_array($check_address_query);
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

                tep_db_perform(TABLE_ADDRESS_BOOK, $sql_data_array);
                $sendto = $billto = tep_db_insert_id();
            }

            if(!$already_completed){
                $order->billmateref=$result1->number;
                $payment['tan']=$result1->number;
            }
            tep_session_unregister('pcbillmate_ot');
            tep_mail($order->customer['firstname'] . ' ' . $order->customer['lastname'], $order->customer['email_address'], EMAIL_TEXT_SUBJECT, $email_order, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
            tep_session_unregister('pcbillmate_ot');
            // send emails to other people
            if (SEND_EXTRA_ORDER_EMAILS_TO != '') {
                tep_mail('', SEND_EXTRA_ORDER_EMAILS_TO, EMAIL_TEXT_SUBJECT, $email_order, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
            }
            // Insert transaction # into history file

            $sql_data_array = array('orders_id' => $_DATA['orderid'],
                'orders_status_id' => MODULE_PAYMENT_PCBILLMATE_ORDER_STATUS_ID,
                'date_added' => 'now()',
                'customer_notified' => 0,
                'comments' => ('Billmate_IPN')
            );
            tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);

            $sql_data_array = array('orders_id' => $_DATA['orderid'],
                'orders_status_id' => MODULE_PAYMENT_PCBILLMATE_ORDER_STATUS_ID,
                'date_added' => 'now()',
                'customer_notified' => 0,
                'comments' => ('Accepted by Billmate ' . date("Y-m-d G:i:s") .' Invoice #: ' . $_DATA['number'])
            );
            tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
            tep_db_query("update " . TABLE_ORDERS . " set orders_status = '" . (MODULE_PAYMENT_PCBILLMATE_ORDER_STATUS_ID ) . "', last_modified = now() where orders_id = '" . (int)$_DATA['orderid'] . "'");


            // load the after_process function from the payment modules
            $this->after_process();

            $cart->reset(true);

            // unregister session variables used during checkout
            tep_session_unregister('sendto');
            tep_session_unregister('billto');
            tep_session_unregister('shipping');
            tep_session_unregister('payment');
            tep_session_unregister('comments');

            tep_session_unregister('cart_billmate_card_ID');

            tep_redirect(tep_href_link(FILENAME_CHECKOUT_SUCCESS, '', 'SSL'));

            return false;
        }
    }

    function after_process() {
        global $insert_id, $order, $languages_id;


        //get response data
        $_DATA = json_decode($_REQUEST['data'], true);
        $_DATA['order_id'] = substr($_DATA['orderid'], strpos($_DATA['orderid'], '-')+1);

        $find_st_optional_field_query =
            tep_db_query("show columns from " . TABLE_ORDERS);

        $has_billmatecardpay_ref = false;

        while($fields = tep_db_fetch_array($find_st_optional_field_query)) {
            if ( $fields['Field'] == "billmateref" )
                $has_billmatecardpay_ref = true;
        }

        if ($has_billmatecardpay_ref) {
            tep_db_query("update " . TABLE_ORDERS . " set billmateref='" .
                $order->billmateref . "' " . " where orders_id = '" .
                $_DATA['order_id'] . "'");
        }

        // Insert transaction # into history file
        if( !empty($order->billmateref) ) {
            $sql_data_array = array('orders_id' => $_DATA['order_id'],
                'orders_status_id' => MODULE_PAYMENT_PCBILLMATE_ORDER_STATUS_ID,
                'date_added' => 'now()',
                'customer_notified' => 0,
                'comments' => ('Accepted by Billmate ' .
                    date("Y-m-d G:i:s") .
                    ' Invoice #: ' .
                    $order->billmateref));
            tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);

            tep_db_query("update " . TABLE_ORDERS . " set orders_status = '" . (MODULE_PAYMENT_PCBILLMATE_ORDER_STATUS_ID ) . "', last_modified = now() where orders_id = '" . (int)$_DATA['order_id'] . "'");
        }


        //Delete Session with user details
        tep_session_unregister('user_billing');
        return false;
    }


    function get_error() {

        if (isset($_GET['message']) && strlen($_GET['message']) > 0) {
            $error = stripslashes(urldecode($_GET['message']));
        } else {
            $error = $_GET['error'];
        }

        return array('title' => html_entity_decode(MODULE_PAYMENT_PCBILLMATE_ERRORDIVIDE),
                     'error' => $error);
    }

    function check() {
        if (!isset($this->_check)) {
            $check_query = tep_db_query("select configuration_value from " .
                    TABLE_CONFIGURATION .
                    " where configuration_key = " .
                    "'MODULE_PAYMENT_PCBILLMATE_STATUS'");
            $this->_check = tep_db_num_rows($check_query);
        }
        return $this->_check;
    }

    function install() {
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Billmate Module', 'MODULE_PAYMENT_PCBILLMATE_STATUS', 'True', 'Do you want to accept Billmate payments?', '6', '0', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");

        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Merchant ID', 'MODULE_PAYMENT_PCBILLMATE_EID', '0', 'Merchant ID (estore id) to use for the Billmate service (provided by Billmate)', '6', '0', now())");

        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Shared secret', 'MODULE_PAYMENT_PCBILLMATE_SECRET', '', 'Shared secret to use with the Billmate service (provided by Billmate)', '6', '0', now())");

        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Product artno attribute (id or model)', 'MODULE_PAYMENT_PCBILLMATE_ARTNO', 'id', 'Use the following product attribute for ArtNo.', '6', '2', 'tep_cfg_select_option(array(\'id\', \'model\'),', now())");

        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Ignore table', 'MODULE_PAYMENT_PCBILLMATE_ORDER_TOTAL_IGNORE', 'ot_tax,ot_total,ot_subtotal', 'Ignore these entries from order total list when compiling the invoice data', '6', '2', now())");

        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Order value maximum limit', 'MODULE_PAYMENT_PCBILLMATE_ORDER_LIMIT', '50000', 'Only show this payment alternative for orders less than the value below.', '6', '2', now())");
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Order value minimum limit', 'MODULE_PAYMENT_PCBILLMATE_MIN_ORDER_LIMIT', '0', 'Only show this payment alternative for orders greater than the value below.', '6', '2', now())");

        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of display.', 'MODULE_PAYMENT_PCBILLMATE_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");

        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Order Status', 'MODULE_PAYMENT_PCBILLMATE_ORDER_STATUS_ID', '0', 'Set the status of orders made with this payment module to this value', '6', '0', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");

        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Set database table for campaigns', 'MODULE_PAYMENT_PCBILLMATE_PCLASS_TABLE', 'osc_billmate_se_pclasses', 'A unused table to store pclasses in, e.g. \"osc_billmate_se_pclasses\"', '6', '7', now())");

        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Testmode', 'MODULE_PAYMENT_PCBILLMATE_TESTMODE', 'False', 'Do you want to activate the Testmode? We will not pay for the invoices created with the test persons nor companies and we will not collect any fees as well.', '6', '0', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");

        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Countries', 'MODULE_PAYMENT_PCBILLMATE_ENABLED_COUNTRYIES', 'se,fi,dk,no', 'Available in selected countries<br/>se = Sweden<br/>fi = Finland<br/>dk = Denmark<br/>no = Norway', '6', '0', now())");
        
    }

    function remove() {
        require_once(DIR_FS_CATALOG . DIR_WS_CLASSES . 'billmate/billmateutils.php');
        tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
        BillmateUtils::remove_db(MODULE_PAYMENT_PCBILLMATE_PCLASS_TABLE);
    }

    function create_pclass_db() {
        require_once(DIR_FS_CATALOG . DIR_WS_CLASSES . 'billmate/billmateutils.php');
        BillmateUtils::create_db(MODULE_PAYMENT_PCBILLMATE_PCLASS_TABLE);
    }

    function keys() {
        global $pcbillmate_testmode;

        //Set the right Host and Port
        $livemode = $this->pcbillmate_testmode == false;

        require_once(DIR_FS_CATALOG . DIR_WS_CLASSES . 'billmate/billmateutils.php');

        $filename = explode('?', basename($_SERVER['REQUEST_URI'], 0));//[0];

        if ($filename[0] == "modules.php") {
                $eid = MODULE_PAYMENT_PCBILLMATE_EID;
                $secret = MODULE_PAYMENT_PCBILLMATE_SECRET;

                $result = false;
            $langs = tep_db_query("select code from languages");
            while($lang = tep_db_fetch_array($langs)) {
                $lang['code'] = ($lang['code'] == 'se') ? 'sv' : $lang['code'];
                if (in_array($lang['code'], array('sv', 'en'))) {
                    $additionalinfo['PaymentData'] = array(
                        "currency" => 'SEK',//SEK
                        "country" => 'se',//Sweden
                        "language" => $lang['code'],//Swedish
                    );
                    $k = new BillMate($eid, $secret, true, $this->pcbillmate_testmode, false);
                    $result = $k->GetPaymentPlans($additionalinfo);

                    BillmateUtils::update_pclasses(MODULE_PAYMENT_PCBILLMATE_PCLASS_TABLE, $result,$lang['code']);
                }
            }
                
                BillmateUtils::display_pclasses(MODULE_PAYMENT_PCBILLMATE_PCLASS_TABLE, $KRED_ISO3166_SE);

        }

        return array('MODULE_PAYMENT_PCBILLMATE_STATUS',
                'MODULE_PAYMENT_PCBILLMATE_ORDER_STATUS_ID',
                'MODULE_PAYMENT_PCBILLMATE_EID',
                'MODULE_PAYMENT_PCBILLMATE_SECRET',
                'MODULE_PAYMENT_PCBILLMATE_ARTNO',
                'MODULE_PAYMENT_PCBILLMATE_ENABLED_COUNTRYIES',
                'MODULE_PAYMENT_PCBILLMATE_ORDER_LIMIT',
                'MODULE_PAYMENT_PCBILLMATE_MIN_ORDER_LIMIT',
                'MODULE_PAYMENT_PCBILLMATE_ORDER_TOTAL_IGNORE',
                'MODULE_PAYMENT_PCBILLMATE_SORT_ORDER',
                'MODULE_PAYMENT_PCBILLMATE_PCLASS_TABLE',
                'MODULE_PAYMENT_PCBILLMATE_TESTMODE'
        );
    }
}
$i = $includeLoopVariable;
?>

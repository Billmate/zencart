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
//error_reporting(E_ALL);
//ini_set('display_errors', true);
error_reporting(E_ERROR);
ini_set('display_errors', true);
@session_start();
$includeLoopVariable = $i;
@include_once(DIR_FS_CATALOG . DIR_WS_CLASSES . 'billmate/billmate_lang.php');

if(!class_exists('Encoding',false)){
    require_once DIR_FS_CATALOG . DIR_WS_CLASSES.'billmate/utf8.php';
	require_once(DIR_FS_CATALOG . DIR_WS_CLASSES . 'billmate/billmate_api.php');
	require_once(DIR_FS_CATALOG . DIR_WS_CLASSES . 'billmate/billmateutils.php');
}

class pcbillmate extends base{
    var $code, $title, $description, $enabled, $pcbillmate_testmode, $jQuery;
    var $display_pc_threshold = 1000;
    var $ma_minimum = 50;

    // class constructor
    function pcbillmate() {
        global $order, $currency, $cart, $currencies, $customer_id, $customer_country_id, $pcbillmate_testmode, $db;
        $this->jQuery = true;
        $this->code = 'pcbillmate';

        if(strpos($_SERVER['SCRIPT_FILENAME'],'admin')) {
            $this->title = MODULE_PAYMENT_PCBILLMATE_TEXT_TITLE;
        }
        else {
            //$tmp = explode('Billmate', MODULE_PAYMENT_PCBILLMATE_TEXT_TITLE);
            //$this->title = $tmp[0] . 'Billmate';
			$this->title = MODULE_PAYMENT_PCBILLMATE_TEXT_TITLE;
        }

        $this->pcbillmate_testmode = false;
        if ((MODULE_PAYMENT_PCBILLMATE_TESTMODE == 'True')) {
            $this->pcbillmate_testmode = true;
            $this->title .= ' '.MODULE_PAYMENT_PCBILLMATE_TESTMODE_TITLE;
        }

        $this->description = MODULE_PAYMENT_PCBILLMATE_TEXT_DESCRIPTION . "<br />Version: 1.6";
        $this->enabled = ((MODULE_PAYMENT_PCBILLMATE_STATUS == 'True') ? true : false);

        if($this->enabled) {
            $this->description .= '<br /><b>Click <a href="modules.php?set=payment&module=pcbillmate&get_pclasses=true">here</a> to update your pclasses</b><br />';
        }
		

		$currency = $_SESSION['currency'];
        $currencyValid = array('SE','SEK','EU', 'EUR','NOK','NO', 'SE','sek','eu', 'eur','nok','no' );
        $countryValid  = array('SE', 'DK', 'FI', 'NO','se', 'dk', 'fi', 'no');
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
							  
		if( IS_ADMIN_FLAG == false ) {
			if( $order->billing == null ){
				$billing = $_SESSION['billmate_billing'];
			}else{
				$billing = $_SESSION['billmate_billing'] = $order->billing;
			}
		
			$availablecountries = array_intersect($countryValid,$enabled_countries);

			if (!in_array($currency,$currencyValid)) {
				$this->enabled = false;
			}
			else {
				if(!empty($billing) && is_array($billing)) {
					if(!in_array($billing['country']['iso_code_2'],$availablecountries)) {
						$this->enabled = false;
					}
				}
				else {					
					$query = "SELECT countries_iso_code_2 FROM countries WHERE countries_id = " . (int)$_SESSION['customer_country_id'];
					$result = $db->Execute($query);
					if(is_array($result->fields)) {
						if(!in_array($result->fields['countries_iso_code_2'],$countryValid)) {
							$this->enabled = false;
						}
						$this->enabled = $this->enabled && in_array($result->fields['countries_iso_code_2'],$enabled_countries);
					}
					else {
						$this->enabled = false;
					}
					$this->enabled = false;
				}
			}
			
			if(is_object($currencies)) {
				$er = $currencies->get_value($currency);
			}
			else {
				$er = 1;
			}

			if (!empty($order) && $order->info['total']* $er > MODULE_PAYMENT_PCBILLMATE_ORDER_LIMIT) {
				$this->enabled = false;
			}
	
			if ((int)MODULE_PAYMENT_PCBILLMATE_ORDER_STATUS_ID > 0) {
				$this->order_status = MODULE_PAYMENT_PCBILLMATE_ORDER_STATUS_ID;
			}
				
			$this->form_action_url = zen_href_link(FILENAME_CHECKOUT_PROCESS,
					'', 'SSL', false);
		}
		$this->sort_order = MODULE_PAYMENT_PCBILLMATE_SORT_ORDER;
    }

    // class methods
    function javascript_validation() {
        return false;
    }

    function selection() {
        global $pcbillmate_testmode, $order, $customer_id, $currencies, $BILL_ISO3166_SE, $currency, $user_billing, $db;

        //Set the right Host and Port
        $livemode = $this->pcbillmate_testmode == false;

        $eid = MODULE_PAYMENT_PCBILLMATE_EID;
        $secret = MODULE_PAYMENT_PCBILLMATE_SECRET;

        $find_personnummer_field_query =
                $db->Execute("show columns from " . TABLE_CUSTOMERS);

        $has_personnummer = false;
        $has_dob = false;

        while(!$find_personnummer_field_query->EOF) {
            if ($find_personnummer_field_query->fields['Field'] == "customers_personnummer"){
	                $has_personnummer = true;
			}
            if ($find_personnummer_field_query->fields['Field'] == "customers_dob") {
                $has_dob = true;
			}
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

        //Get and calculate monthly costs for all pclasses
        $pclasses = BillmateUtils::calc_monthly_cost($total, MODULE_PAYMENT_PCBILLMATE_PCLASS_TABLE, $BILL_ISO3166_SE, 0);
        
        $lowest = BillmateUtils::get_cheapest_pclass($pclasses);

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
		//unset($_SESSION['WrongAddress']);
        //Fade in/fade out code for the module
        $js = "";

        $fields=array(
                array('title' => BILLMATE_LANG_SE_IMGCONSUMERCREDIT.sprintf(MODULE_PAYMENT_PCBILLMATE_CONDITIONS, $eid),
                        'field' => $js.$error),
                array('title' => MODULE_PAYMENT_PCBILLMATE_CHOOSECONSUMERCREDIT,
                        'field' => zen_draw_pull_down_menu('pcbillmate_pclass', $pclasses, $default)),
                array('title' => MODULE_PAYMENT_PCBILLMATE_PERSON_NUMBER,
                        'field' => zen_draw_input_field('pcbillmate_pnum',
                        $pcbillmate_pnum)),
                array('title' => '',
                        'field' => zen_draw_hidden_field('pcbillmate_phone',
                        $pcbillmate_phone)),
                array('title' => sprintf(MODULE_PAYMENT_PCBILLMATE_EMAIL, $user_billing['billmate_email']),
                        'field' => zen_draw_checkbox_field('pcbillmate_email',
                        $order->customer['email_address'], !empty($pcbillmate_email))));

        //Show yearly salary if order total is above set "limit".
        

        //Shipping/billing address notice
        $fields[] = array('title' => MODULE_PAYMENT_PCBILLMATE_ADDR_TITLE, 'field' => MODULE_PAYMENT_PCBILLMATE_ADDR_NOTICE);

        if(DISPLAY_PRICE_WITH_TAX != 'true') {
            $fields[] = array('title' => '', 'field' => '<font color="red">'.MODULE_PAYMENT_PCBILLMATE_WITHOUT_TAX.'</font>');
        }

        return array('id' => $this->code,
                'module' => $this->title,
                'fields' => $fields);
    }

    function pre_confirmation_check() {
        global $pcbillmate_testmode, $order, $GA_OLD, $BILL_SE_PNO, $user_billing, $db;

        //Set the right Host and Port
        $livemode = $this->pcbillmate_testmode == false;

        //INCLUDE THE VALIDATION FILE
        @include_once(DIR_FS_CATALOG . DIR_WS_CLASSES . 'billmate/validation.php');

        // Set error reasons
        $errors = array();
		unset($_SESSION['error']);
		unset($_SESSION['WrongAddress']);

        //Fill user array with billing details
        $user_billing['pcbillmate_pnum'] = $_POST['pcbillmate_pnum'];
        $user_billing['pcbillmate_phone'] = $_POST['pcbillmate_phone'];
        $user_billing['pcbillmate_email'] = $_POST['pcbillmate_email'];

        //Store values into Session
        $_SESSION['user_billing'] = $user_billing;

        if (!validate_pno_se($_POST['pcbillmate_pnum'])) {
            $errors[] = MODULE_PAYMENT_PCBILLMATE_PERSON_NUMBER;
        }

        if (!validate_email($_POST['pcbillmate_email'])) {
            $errors[] = sprintf( MODULE_PAYMENT_PCBILLMATE_EMAIL, $order->customer['email_address']);
        }

        if (!empty($errors)) {
            $error_message = implode(', ', $errors);
			$_SESSION['error'] = $error_message;
			zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, 'payment_error=pcbillmate&error=true', 'SSL', true, false));
			return;
        }

        $pno = $this->pcbillmate_pnum = $_POST['pcbillmate_pnum'];
        $_SESSION['pcbillmate_pclass'] = $this->pcbillmate_pclass = $_POST['pcbillmate_pclass'];
        $eid = MODULE_PAYMENT_PCBILLMATE_EID;
        $secret = MODULE_PAYMENT_PCBILLMATE_SECRET;

        $pnoencoding = $BILL_SE_PNO;

        $type = $GA_OLD;

		$ssl = true;
		$debug = false;

		$k = new BillMate((int)$eid,(float)$secret,$ssl,$debug, $this->pcbillmate_testmode );
		$result = $k->getAddress( $pno );
       // $status = get_addresses($eid, BillmateUtils::convertData($pno), $secret, $pnoencoding, $type, $result);
        if (!is_array($result)) {
			$_SESSION['error'] = strip_tags($result)." ($status)";
            zen_redirect( zen_href_link(FILENAME_CHECKOUT_PAYMENT).'&payment_error=pcbillmate&error=true');
        }
        //If UTF-8 convert to UTF-8 so everything is displayed correctly.
        if(strtolower(CHARSET) == 'utf-8') {
            foreach($result as &$addrs) {
                foreach($addrs as &$addr) {
                    $addr = utf8_encode($addr);
                }
            }
        }
        $this->addrs = $result;
        
        foreach($result as &$addrs) {
            foreach($addrs as &$addr) {
                $addr = Encoding::fixUTF8($addr);
            }
        }

        $fullname = $order->billing['firstname'].' '.$order->billing['lastname'] .' '.$order->billing['company'];
        $apiName  = $result[0][0].' '.$result[0][1];

        $firstArr = explode(' ', $order->billing['firstname']);
        $lastArr  = explode(' ', $order->billing['lastname']);
        
        if( empty( $result[0][0] ) ){
            $apifirst = $firstArr;
            $apilast  = $lastArr ;
        }else {
            $apifirst = explode(' ', $result[0][0] );
            $apilast  = explode(' ', $result[0][1] );
        }
        $matchedFirst = array_intersect($apifirst, $firstArr );
        $matchedLast  = array_intersect($apilast, $lastArr );
        $apiMatchedName   = !empty($matchedFirst) && !empty($matchedLast);

		$addressNotMatched = !isEqual($result[0][2], $order->billing['street_address'] ) ||
		    !isEqual($result[0][3], $order->billing['postcode']) || 
		    !isEqual($result[0][4], $order->billing['city']) || 
		    !isEqual($result[0][5], BillmateCountry::fromCode($order->billing['country']['iso_code_3']));

        $shippingAndBilling =  !$apiMatchedName ||
		    !isEqual($order->billing['street_address'],  $order->delivery['street_address'] ) ||
		    !isEqual($order->billing['postcode'], $order->delivery['postcode']) || 
		    !isEqual($order->billing['city'], $order->delivery['city']) || 
		    !isEqual($order->billing['country']['iso_code_3'], $order->delivery['country']['iso_code_3']) ;
		

        if( $addressNotMatched || $shippingAndBilling ){
            if( empty($_POST['geturl'])){
	            $html = '<p><b>'.MODULE_PAYMENT_PCBILLMATE_CORRECT_ADDRESS.' </b></p>'.($result[0][0]).' '.$result[0][1].'<br>'.$result[0][2].'<br>'.$result[0][3].' '.$result[0][4].'<div style="padding: 17px 0px;"> <i>'.MODULE_PAYMENT_PCBILLMATE_CORRECT_ADDRESS_OPTION.'</i></div> <input type="button" value="'.MODULE_PAYMENT_PCBILLMATE_YES.'" onclick="updateAddresspart();" class="button"/> <input type="button" value="'.MODULE_PAYMENT_PCBILLMATE_NO.'" onclick="closefunc(this)" class="button" style="float:right" />';

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
	            <script type="text/javascript" src="'.HTTP_SERVER.DIR_WS_CATALOG.'billmatepopup.js"></script>
	            <script type="text/javascript">
	            function updateAddresspart(){
						var e = document.createElement(\'input\');
						e.name="geturl"; e.value ="true"; e.type ="hidden";
						document.getElementsByName(\'pcbillmate_pnum\').item(0).parentNode.insertBefore(e, document.getElementsByName(\'pcbillmate_pnum\').item(0));
						document.forms[\'checkout_payment\'].submit();
	            }
	            function closefunc(obj){
    	            modalWin.HideModalPopUp();
	            };modalWin.ShowMessage(\''.$html.'\',250,500,\''.MODULE_PAYMENT_PCBILLMATE_ADDRESS_WRONG.'\');</script>';
	            unset($_SESSION['WrongAddress']);
                $WrongAddress = $code;
                global $messageStack;
                $_SESSION['WrongAddress'] = $WrongAddress;
				$_SESSION['error']= "invalidaddress";
	            zen_redirect( zen_href_link(FILENAME_CHECKOUT_PAYMENT).'&payment_error=billmate&error=true' );
	        }else{
	            if(!match_usernamevp( $fullname , $apiName)){
                    if($result[0][0] == "") {
                        $this->pcbillmate_fname = $result[0][1];
                        $this->pcbillmate_lname = $result[0][0];
                    }else {
                        $this->pcbillmate_fname = $result[0][0];
                        $this->pcbillmate_lname = $result[0][1];
                    }
                }
                $this->pcbillmate_street = $result[0][2];
                $this->pcbillmate_postno = $result[0][3];
                $this->pcbillmate_city = $result[0][4];
                $countryCode = BillmateCountry::getCode($result[0][5]);
                $country_query = ("select countries_id from " . TABLE_COUNTRIES . " where countries_iso_code_2 = '" .$countryCode . "'");
                $country = $db->Execute($country_query);
                global $customer_id;
                $data = array(
                    'entry_firstname'       =>$this->pcbillmate_fname,
                    'entry_lastname'        =>$this->pcbillmate_lname,
                    'entry_street_address'  =>$this->pcbillmate_street,
                    'entry_postcode'        =>$this->pcbillmate_postno,
                    'entry_city'            =>$this->pcbillmate_city,
                    'entry_country_id'      =>$country->fields['countries_id'],
                    'customers_id'          => $customer_id
                );

                
                $order->delivery['firstname'] = $this->pcbillmate_fname;
                $order->billing['firstname'] = $this->pcbillmate_fname;
                $order->delivery['lastname'] = $this->pcbillmate_lname;
                $order->billing['lastname'] = $this->pcbillmate_lname;
                $order->delivery['street_address'] = $this->pcbillmate_street;
                $order->billing['street_address'] = $this->pcbillmate_street;
                $order->delivery['postcode'] = $this->pcbillmate_postno;
                $order->billing['postcode'] = $this->pcbillmate_postno;
                $order->delivery['city'] = $this->pcbillmate_city;
                $order->billing['city'] = $this->pcbillmate_city;

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
                global $sendto;
                $sendto = $this->delivery;
	        }
        }
    }

    function confirmation() {
        return array('title' => MODULE_PAYMENT_PCBILLMATE_TEXT_CONFIRM_DESCRIPTION);
    }

    function process_button() {
        global $order, $order_total_modules, $shipping;
		
		//Address session destroy
		unset($_SESSION['WrongAddress']);
		
        $counter = 1;
        $a=count($this->addrs);

        $process_button_string = "";
        if($a>1) {
            $process_button_string .= "<div align='left'>";
            $process_button_string .=  "<table border='0' width='100%' cellspacing='1' cellpadding='2' class='infoBox'>";
            $process_button_string .=  "<tr><td class='main'><b>".MODULE_PAYMENT_PCBILLMATE_CHOOSEALTERNATIVES."</b></td></tr>";
            $process_button_string .=  "<tr class='infoBoxContents'><td>";

            foreach ($this->addrs as $addr) {
                $this->pcbillmate_fname = $addr[0];
                $this->pcbillmate_lname = $addr[1];
                $this->pcbillmate_street = $addr[2];
                $this->pcbillmate_postno = $addr[3];
                $this->pcbillmate_city = $addr[4];

                if($counter == 1) {
                    $checked = true;
                }else {
                    $checked = false;
                }

                $process_button_string .=
                        zen_draw_radio_field('addr_num', $counter, $checked, '').
                        zen_draw_hidden_field('pcbillmate_pclass'.$counter,
                        $this->pcbillmate_pclass).
                        zen_draw_hidden_field('pcbillmate_pnum'.$counter,
                        $this->pcbillmate_pnum).
                        zen_draw_hidden_field('pcbillmate_ysalary'.$counter,
                        $this->pcbillmate_ysalary).
                        zen_draw_hidden_field('pcbillmate_fname'.$counter,
                        $this->pcbillmate_fname).
                        zen_draw_hidden_field('pcbillmate_lname'.$counter,
                        $this->pcbillmate_lname).
                        zen_draw_hidden_field('pcbillmate_street'.$counter,
                        $this->pcbillmate_street).
                        $this->pcbillmate_street." ".
                        zen_draw_hidden_field('pcbillmate_postno'.$counter,
                        $this->pcbillmate_postno).
                        $this->pcbillmate_postno." ".
                        zen_draw_hidden_field('pcbillmate_city'.$counter,
                        $this->pcbillmate_city).
                        $this->pcbillmate_city." ".
                        zen_draw_hidden_field('pcbillmate_phone'.$counter,
                        $this->pcbillmate_phone).
                        zen_draw_hidden_field('pcbillmate_email'.$counter,
                        $this->pcbillmate_email)."<br/>";
                $counter++;
            }
            $process_button_string .= "</td></tr></table><br/></div>";
        }else {
            $process_button_string .=
                    zen_draw_hidden_field('addr_num', $counter, $checked, '').
                    zen_draw_hidden_field('pcbillmate_pclass'.$counter,$this->pcbillmate_pclass).
                    zen_draw_hidden_field('pcbillmate_pnum'.$counter,$this->pcbillmate_pnum).
                    zen_draw_hidden_field('pcbillmate_ysalary'.$counter,$this->pcbillmate_ysalary).
                    zen_draw_hidden_field('pcbillmate_fname'.$counter,$this->pcbillmate_fname).
                    zen_draw_hidden_field('pcbillmate_lname'.$counter,$this->pcbillmate_lname).
                    zen_draw_hidden_field('pcbillmate_street'.$counter,
                    $this->pcbillmate_street).
                    zen_draw_hidden_field('pcbillmate_postno'.$counter,
                    $this->pcbillmate_postno).
                    zen_draw_hidden_field('pcbillmate_city'.$counter,$this->pcbillmate_city).
                    zen_draw_hidden_field('pcbillmate_phone'.$counter,$this->pcbillmate_phone).
                    zen_draw_hidden_field('pcbillmate_email'.$counter,$this->pcbillmate_email);
        }

        // This is a bit of a hack. The problem is that we need access to
        // all additional charges, ie the order_totals list, later in
        // before_process(), but at that point order_totals->process hasn't
        // been run in that process. We cannot run it ourselves since
        // checkout_process.php will run it after running before_process.
        // Running it twice causes an error message since the classes
        // will be redefined.
        //
        // An alternative to this ugly code is to modify checkout_process.php
        // or order_total.php but we want to avoid that.
        //

        $order_totals = $order_total_modules->modules;
        if (is_array($order_totals)) {
            reset($order_totals);
            $j = 0;
            $table = preg_split("/[,]/", MODULE_PAYMENT_PCBILLMATE_ORDER_TOTAL_IGNORE);

            while (list(, $value) = each($order_totals)) {
                $class = substr($value, 0, strrpos($value, '.'));

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
                                $shipping_id = @explode('_', $_SESSION['shipping']['id']);
                                $tax_class = @$GLOBALS[$shipping_id[0]]->tax_class;
                                $tax_rate = 0;
                                if($tax_class > 0) {
                                    $tax_rate = zen_get_tax_rate($tax_class, $order->billing['country']['id'], ($order->billing['zone_id'] > 0) ? $order->billing['zone_id'] : null);
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
        $_SESSION['pcbillmate_ot'] = $pcbillmate_ot;

        $process_button_string .= zen_draw_hidden_field(zen_session_name(),
                zen_session_id());
        return $process_button_string;
    }

    function before_process() {
        global $order, $customer_id, $currency, $currencies, $sendto, $billto, $pcbillmate_testmode, $db;

		//Assigning billing session
		$pcbillmate_ot = $_SESSION['pcbillmate_ot'];
	
        //Set the right Host and Port
        $livemode = $this->pcbillmate_testmode == false;

        $eid = (int)MODULE_PAYMENT_PCBILLMATE_EID;
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

            if (MODULE_PAYMENT_PCBILLMATE_ARTNO == 'id' || MODULE_PAYMENT_PCBILLMATE_ARTNO == '') {
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
                        $order->products[$i][MODULE_PAYMENT_PCBILLMATE_ARTNO],
                        $order->products[$i]['name'] . $attributes,
                        $price_without_tax,
                        $order->products[$i]['tax'],
                        0,
                        0); //incl VAT
            }
        }

        // Then the extra charnges like shipping and invoicefee and
        // discount.

        $extra = $pcbillmate_ot['code_entries'];
        //end hack

        //This could apply tax on shipping etc, twice?
        for ($j=0 ; $j<$extra ; $j++) {
            $size = $pcbillmate_ot["code_size_".$j];
            for ($i=0 ; $i<$size ; $i++) {
                $value = $pcbillmate_ot["value_".$j."_".$i];
                $name = $pcbillmate_ot["title_".$j."_".$i];
                $tax = $pcbillmate_ot["tax_rate_".$j."_".$i];
                $code = $pcbillmate_ot["code_".$j."_".$i];
                $name = rtrim($name, ":");
                $flags = 0; //INC VAT
                if($code == 'ot_shipping') {
                    $flags += 8; //IS_SHIPMENT
                }

                if(DISPLAY_PRICE_WITH_TAX == 'true') {
                    $flags += 32;
                }


				$price_with_tax = $currencies->get_value($currency) * $value * 100;
                
				if ($value != "" && $value != 0) {
                    $goodsList[] = mk_goods_flags(1, "", BillmateUtils::convertData($name), $price_with_tax, $tax, 0, $flags);
                }
            }
        }

        $secret = (float)MODULE_PAYMENT_PCBILLMATE_SECRET;
        $estoreOrderNo = "";
        $shipmentfee = 0;
        $shipmenttype = 1;
        $handlingfee = 0;
        $ready_date = "";

        // Fixes potential security problem
 
		$pclass = $_POST['pcbillmate_pclass1'];
		$pno    = $_POST['pcbillmate_pnum1'];
		$ship_address = $bill_address = array();

        $countryData = BillmateCountry::getCountryData($order->billing['country']['iso_code_3']);

        $addr_num = $_POST['addr_num'];
     /*   $order->delivery['firstname'] = $_POST['pcbillmate_fname'.$addr_num];
        $order->billing['firstname'] =  $_POST['pcbillmate_fname'.$addr_num];
        $order->delivery['lastname'] =  $_POST['pcbillmate_lname'.$addr_num];
        $order->billing['lastname'] =   $_POST['pcbillmate_lname'.$addr_num];
        $order->delivery['street_address'] = $_POST['pcbillmate_street'.$addr_num];
        $order->billing['street_address'] = $_POST['pcbillmate_street'.$addr_num];
        $order->delivery['postcode'] = $_POST['pcbillmate_postno'.$addr_num];
        $order->billing['postcode'] = $_POST['pcbillmate_postno'.$addr_num];
        $order->delivery['city'] = $_POST['pcbillmate_city'.$addr_num];
        $order->billing['city'] = $_POST['pcbillmate_city'.$addr_num];

        //Set same country information to delivery
        $order->delivery['state'] = $order->billing['state'];
        $order->delivery['zone_id'] = $order->billing['zone_id'];
        $order->delivery['country_id'] = $order->billing['country_id'];
        $order->delivery['country']['id'] = $order->billing['country']['id'];
        $order->delivery['country']['title'] = $order->billing['country']['title'];
        $order->delivery['country']['iso_code_2'] = $order->billing['country']['iso_code_2'];
        $order->delivery['country']['iso_code_3'] = $order->billing['country']['iso_code_3'];*/

	    $ship_address = array(
		    'email'           => $order->customer['email_address'],
		    'telno'           => $order->customer['telephone'],
		    'cellno'          => '',
		    'fname'           => $order->delivery['firstname'],
		    'lname'           => $order->delivery['lastname'],
		    'company'         => $order->delivery['company'],
		    'street'          => $order->delivery['street_address'],
		    'zip'             => $order->delivery['postcode'],
		    'city'            => $order->delivery['city'],
		    'country'         => (int)$countryData['country'],
	    );
	    $bill_address = array(
		    'email'           => $order->customer['email_address'],
		    'telno'           => $order->customer['telephone'],
		    'cellno'          => '',
		    'fname'           => $order->billing['firstname'],
		    'lname'           => $order->billing['lastname'],
		    'company'         => $order->billing['company'],
		    'street'          => $order->billing['street_address'],
		    'zip'             => $order->billing['postcode'],
		    'city'            => $order->billing['city'],
		    'country'         => (int)$countryData['country'],
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
			"country"=>$countryData['country'],
			"language"=>$countryData['language'],
			"pclass"=>(int)$pclass,
			"shipInfo"=>array("delay_adjust"=>"1"),
			"travelInfo"=>array(),
			"incomeInfo"=>array(),
			"bankInfo"=>array(),
			"sid"=>array("time"=>microtime(true)),
			"extraInfo"=>array(array("cust_no"=>(int)$customer_id))
		);

		$ssl = true;
		$debug = false;

		$k = new BillMate($eid,$secret,$ssl,$debug, $this->pcbillmate_testmode);
		$result1 = $k->AddInvoice($pno,$ship_address,$bill_address,$goodsList,$transaction);
        if (is_array($result1)) {

            // insert address in address book to get correct address in
            // confirmation mail (or fetch correct address from address book
            // if it exists)

            $check_country_query = "select countries_id from " . TABLE_COUNTRIES .
                    " where countries_iso_code_2 = '".$order->billing['country']['iso_code_2']."'";

            $check_country = $db->Execute($check_country_query);

            $cid = $check_country->fields['countries_id'];

            $check_address_query = "select address_book_id from " . TABLE_ADDRESS_BOOK .
									" where customers_id = '" . (int)$customer_id .
									"' and entry_firstname = '" . $order->delivery['firstname'] .
									"' and entry_lastname = '" . $order->delivery['lastname'] .
									"' and entry_street_address = '" . $order->delivery['street_address'] .
									"' and entry_postcode = '" . $order->delivery['postcode'] .
									"' and entry_city = '" . $order->delivery['city'] . "'";
            $check_address = $db->Execute($check_address_query);
            if(is_array($check_address->fields) && count($check_address->fields) > 0) {
                $sendto = $billto = $check_address->fields['address_book_id'];
            }else {
                $sql_data_array =
                        array('customers_id' => $customer_id,
                        'entry_firstname' => $order->delivery['firstname'],
                        'entry_lastname' => $order->delivery['lastname'],
                        'entry_street_address' => $order->delivery['street_address'],
                        'entry_postcode' => $order->delivery['postcode'],
                        'entry_city' => $order->delivery['city'],
                        'entry_country_id' => $cid);

                zen_db_perform(TABLE_ADDRESS_BOOK, $sql_data_array);
                $sendto = $billto = $db->insert_ID();
            }

            $order->billmateref=$result1[1];
            $payment['tan']=$result1;

            unset($_SESSION['pcbillmate_ot']);

        } else {
			$_SESSION['error'] = utf8_encode($result1);
			zen_redirect( zen_href_link(FILENAME_CHECKOUT_PAYMENT).'&payment_error=pcbillmate&error=true' );				
        }
    }

    function after_process() {
		global $insert_id, $db, $order;

        $find_st_optional_field_query =
                $db->Execute("show columns from " . TABLE_ORDERS);

        $has_billmate_ref = false;

        while(!$find_st_optional_field_query->EOF) {
            if ( $find_st_optional_field_query->fields['Field'] == "billmateref" )
                $has_billmate_ref = true;
        	$find_st_optional_field_query->MoveNext();
		}

        if ($has_billmate_ref) {
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
        $secret = (float)MODULE_PAYMENT_PCBILLMATE_SECRET;
        $eid = (int)MODULE_PAYMENT_PCBILLMATE_EID;
        $invno = $order->billmateref;

		$ssl = true;
		$debug = false;
		$k = new BillMate($eid,$secret,$ssl,$debug, $this->pcbillmate_testmode);
		$result1 = $k->UpdateOrderNo($invno, $insert_id);
        return false;
    }


    function get_error() {
		
		unset($_SESSION['WrongAddress']);
        if (isset($_GET['message']) && strlen($_GET['message']) > 0) {
            $error = stripslashes(urldecode($_GET['message']));
        } else {
            $error = $_SESSION['error']; unset($_SESSION['error']);
        }

        return array('title' => html_entity_decode(MODULE_PAYMENT_PCBILLMATE_ERRORDIVIDE),
                     'error' => $error);
    }

    function check() {
		global $db;
        if (!isset($this->_check)) {
            $check_query = $db->Execute("select configuration_value from " .
                    TABLE_CONFIGURATION .
                    " where configuration_key = " .
                    "'MODULE_PAYMENT_PCBILLMATE_STATUS'");
            $this->_check = $check_query->RecordCount();
        }
        return $this->_check;
    }

    function install() {
		 global $db, $messageStack;
		if (defined('MODULE_PAYMENT_PAYPAL_STATUS')) {
		  $messageStack->add_session('PayPal Payments Standard module already installed.', 'error');
		  zen_redirect(zen_href_link(FILENAME_MODULES, 'set=payment&module=paypal', 'NONSSL'));
		  return 'failed';
		}
		if (defined('MODULE_PAYMENT_PAYPALWPP_STATUS')) {
		  $messageStack->add_session('NOTE: PayPal Express Checkout module already installed. You don\'t need Standard if you have Express installed.', 'error');
		  zen_redirect(zen_href_link(FILENAME_MODULES, 'set=payment&module=paypalwpp', 'NONSSL'));
		  return 'failed';
		}
		 
        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Billmate Module', 'MODULE_PAYMENT_PCBILLMATE_STATUS', 'True', 'Do you want to accept Billmate payments?', '6', '0', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Merchant ID', 'MODULE_PAYMENT_PCBILLMATE_EID', '0', 'Merchant ID (estore id) to use for the Billmate service (provided by Billmate)', '6', '0', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Shared secret', 'MODULE_PAYMENT_PCBILLMATE_SECRET', '', 'Shared secret to use with the Billmate service (provided by Billmate)', '6', '0', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Product artno attribute (id or model)', 'MODULE_PAYMENT_PCBILLMATE_ARTNO', 'id', 'Use the following product attribute for ArtNo.', '6', '2', 'zen_cfg_select_option(array(\'id\', \'model\'),', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Ignore table', 'MODULE_PAYMENT_PCBILLMATE_ORDER_TOTAL_IGNORE', 'ot_tax,ot_total,ot_subtotal', 'Ignore these entries from order total list when compiling the invoice data', '6', '2', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Credit limit', 'MODULE_PAYMENT_PCBILLMATE_ORDER_LIMIT', '50000', 'Only show this payment alternative for orders less than the value below.', '6', '2', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of display.', 'MODULE_PAYMENT_PCBILLMATE_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Order Status', 'MODULE_PAYMENT_PCBILLMATE_ORDER_STATUS_ID', '0', 'Set the status of orders made with this payment module to this value', '6', '0', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Set database table for campaigns', 'MODULE_PAYMENT_PCBILLMATE_PCLASS_TABLE', 'zen_billmate_se_pclasses', 'A unused table to store pclasses in, e.g. \"osc_billmate_se_pclasses\"', '6', '7', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Testmode', 'MODULE_PAYMENT_PCBILLMATE_TESTMODE', 'False', 'Do you want to activate the Testmode? We will not pay for the invoices created with the test persons nor companies and we will not collect any fees as well.', '6', '0', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Countries', 'MODULE_PAYMENT_PCBILLMATE_ENABLED_COUNTRYIES', 'se,fi,dk,no', 'Available in selected countries<br/>se = Sweden<br/>fi = Finland<br/>dk = Denmark<br/>no = Norway', '6', '0', now())");
		$this->notify('NOTIFY_PAYMENT_PCBILLMATE_INSTALLED');
        
    }

    function remove() {
		global $db;
        require_once(DIR_FS_CATALOG . DIR_WS_CLASSES . 'billmate/billmateutils.php');
        $db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
        BillmateUtils::remove_db(MODULE_PAYMENT_PCBILLMATE_PCLASS_TABLE);
		$this->notify('NOTIFY_PAYMENT_PCBILLMATE_UNINSTALLED');
    }

    function create_pclass_db() {
        require_once(DIR_FS_CATALOG . DIR_WS_CLASSES . 'billmate/billmateutils.php');
        BillmateUtils::create_db(MODULE_PAYMENT_PCBILLMATE_PCLASS_TABLE);
    }

    function keys() {
        global $pcbillmate_testmode, $BILL_ISO3166_SE,$BILL_SEK,$BILL_ISO639_SE;

        //Set the right Host and Port
        $livemode = $this->pcbillmate_testmode == false;

        $filename = explode('?', basename($_SERVER['REQUEST_URI'], 0));//[0];

        if ($filename[0] == "modules.php") {
            //if ($_GET['get_pclasses'] == TRUE) {
                $eid = MODULE_PAYMENT_PCBILLMATE_EID;
                $secret = MODULE_PAYMENT_PCBILLMATE_SECRET;

                $result = false;
                fetch_pclasses($eid, $BILL_SEK, $secret, $BILL_ISO3166_SE, $BILL_ISO639_SE, $result);
                error_log(print_r($result,true));
                BillmateUtils::update_pclasses(MODULE_PAYMENT_PCBILLMATE_PCLASS_TABLE, $result);
           // }
           // if (( isset($_GET['view_pclasses']) && $_GET['view_pclasses'] == TRUE) || ( isset($_GET['get_pclasses']) && $_GET['get_pclasses'] == TRUE) ) {
                //echo "<pre>";
                BillmateUtils::display_pclasses(MODULE_PAYMENT_PCBILLMATE_PCLASS_TABLE, $BILL_ISO3166_SE);
                //echo "</pre>";
           // }
        }

        return array('MODULE_PAYMENT_PCBILLMATE_STATUS',
                'MODULE_PAYMENT_PCBILLMATE_ORDER_STATUS_ID',
                'MODULE_PAYMENT_PCBILLMATE_EID',
                'MODULE_PAYMENT_PCBILLMATE_SECRET',
                'MODULE_PAYMENT_PCBILLMATE_ARTNO',
                'MODULE_PAYMENT_PCBILLMATE_ENABLED_COUNTRYIES',
                'MODULE_PAYMENT_PCBILLMATE_ORDER_LIMIT',
                'MODULE_PAYMENT_PCBILLMATE_ORDER_TOTAL_IGNORE',
                'MODULE_PAYMENT_PCBILLMATE_SORT_ORDER',
                'MODULE_PAYMENT_PCBILLMATE_PCLASS_TABLE',
                'MODULE_PAYMENT_PCBILLMATE_TESTMODE'
        );
    }
}
$i = $includeLoopVariable;
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
error_reporting(E_ERROR);
ini_set('display_errors', true);

$includebackup = $i;

@include_once(DIR_FS_CATALOG . DIR_WS_CLASSES . 'billmate/billmate_lang.php');
if(!class_exists('Encoding',false)){
	require_once DIR_FS_CATALOG . DIR_WS_CLASSES.'billmate/utf8.php';
	require (DIR_FS_CATALOG . DIR_WS_CLASSES . 'billmate/billmate_api.php');
	require(DIR_FS_CATALOG . DIR_WS_CLASSES . 'billmate/billmateutils.php');
}



error_reporting(E_ERROR);
ini_set('display_errors', true);

class billmate {
    var $code, $title, $description, $enabled, $billmate_livemode, $billmate_testmode, $jQuery, $db;

    // class constructor
    function billmate() {
        global $order, $currency, $currencies, $customer_id, $customer_country_id, $billmate_livemode, $billmate_testmode;
        $this->jQuery = true;
        $this->code = 'billmate';

        if(strpos($_SERVER['SCRIPT_FILENAME'],'admin')) {
            $this->title = MODULE_PAYMENT_BILLMATE_TEXT_TITLE;
        }
        else {
            $this->title = MODULE_PAYMENT_BILLMATE_TEXT_TITLE;
        }

		if( $order->billing == null ){
			$billing = $_SESSION['billmate_billing'];
		}else{
			$billing = $_SESSION['billmate_billing'] = $order->billing;
		}

        $this->billmate_testmode = false;
		$this->billmate_livemode = true;
        if ((MODULE_PAYMENT_BILLMATE_TESTMODE == 'True')) {
            $this->title .= ' '.MODULE_PAYMENT_BILLMATE_TESTMODE;
            $this->billmate_testmode = true;
			$this->billmate_livemode = false;
        }

        $this->description = MODULE_PAYMENT_BILLMATE_TEXT_DESCRIPTION . "<br />Version: 1.1";
        $this->enabled = ((MODULE_PAYMENT_BILLMATE_STATUS == 'True') ?
                true : false);

		$currency = $_SESSION['currency'];
        $currencyValid = array('SE','SEK','EU', 'EUR','NOK','NO', 'SE','sek','eu', 'eur','nok','no' );
        $countryValid  = array('SE', 'DK', 'FI', 'NO','se', 'dk', 'fi', 'no');
        $enabled_countries = explode(',',
                                trim( 
                                    strtolower(MODULE_PAYMENT_BILLMATE_ENABLED_COUNTRYIES),
                                    ','
                                ).','.
                                trim( 
                                    strtoupper(MODULE_PAYMENT_BILLMATE_ENABLED_COUNTRYIES),
                                    ','
                                 )
                              );
		if( IS_ADMIN_FLAG == false ) {							  
			$availablecountries = array_intersect($countryValid,$enabled_countries);
			if (!in_array($currency,$currencyValid)) {
				$this->enabled = false;
			}
			else {
				if(is_array($billing)) {
					if(!in_array($billing['country']['iso_code_2'],$availablecountries)) {
						$this->enabled = false;
					}
				}
				else {
					$query = ("SELECT countries_iso_code_2 FROM countries WHERE countries_id = " . (int)$_SESSION['customer_country_id']);
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
				}
			}
			
			
			if(is_object($currencies)) {
				$er = $currencies->get_value($currency);
			}
			else {
				$er = 1;
			}
	
			if ($order->info['total']*$er > MODULE_PAYMENT_BILLMATE_ORDER_LIMIT)
				$this->enabled = false;
	
			if ((int)MODULE_PAYMENT_BILLMATE_ORDER_STATUS_ID > 0)
				$this->order_status = MODULE_PAYMENT_BILLMATE_ORDER_STATUS_ID;
	
			if (is_object($order))
				$this->update_status();
	
			$this->form_action_url = zen_href_link(FILENAME_CHECKOUT_PROCESS,
					'', 'SSL', false);
		}
		$this->sort_order = MODULE_PAYMENT_BILLMATE_SORT_ORDER;		
    }

    // class methods
    function update_status() {
        global $order,$db;

        if ($this->enabled == true && (int)MODULE_PAYMENT_BILLMATE_ZONE > 0) {
            $check_flag = false;
            $check_query = ("select zone_id from " .
                    TABLE_ZONES_TO_GEO_ZONES .
                    " where geo_zone_id = '" .
                    MODULE_PAYMENT_BILLMATE_ZONE .
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

        // FIX THE LINK TO THE CONDITION
        if(is_numeric(MODULE_BILLMATE_FEE_FIXED) && MODULE_BILLMATE_FEE_FIXED >= 0)
            $billmate_fee = round($currencies->get_value($currency)*MODULE_BILLMATE_FEE_FIXED, 2);
        else
            $billmate_fee = 0;


        $paymeny_charge_link = "<a href=\"javascript:void();\" onclick=\"window." .
                "open('https://online.billmate.com/villkor.yaws?eid=" .
                MODULE_PAYMENT_BILLMATE_EID."&charge=" .
                $billmate_fee."'," .
                "'villkor','width=300,height=300,scrollbars=no');\">" .
                MODULE_PAYMENT_BILLMATE_PAYMENT."</a>";

        $user_billing = $_SESSION['user_billing'];

        empty($user_billing['billmate_pnum']) ? $billmate_pnum = $personnummer : $billmate_pnum = $user_billing['billmate_pnum'];
        empty($user_billing['billmate_email']) ? $billmate_email = '' : $billmate_email = $user_billing['billmate_email'];

        //Fade in/fade out code for the module
        $js = ($this->jQuery) ? BillmateUtils::get_display_jQuery($this->code) : "";
        $popup = '';
        if(!empty($_GET['error']) && $_GET['error'] == 'invalidaddress' && !empty( $_SESSION['WrongAddress'] ) ){
            $popup = $_SESSION['WrongAddress'];
        }
        $fields=array(
                array('title' => BILLMATE_LANG_SE_IMGINVOICE,
                        'field' => $js),
                array('title' => MODULE_PAYMENT_BILLMATE_CONDITIONS,
                        'field' => "
				<a href=\"#\" id=\"billmate_invoice\" onclick=\"ShowBillmateInvoicePopup(event);return false;\"></a>"),
                array('title' => "",
                        'field' => $popup),				
                array('title' => MODULE_PAYMENT_BILLMATE_PERSON_NUMBER,
                        'field' => zen_draw_input_field('billmate_pnum',
                        $billmate_pnum)),
                array('title' => sprintf(MODULE_PAYMENT_BILLMATE_EMAIL , $order->customer['email_address']),
                        'field' => zen_draw_checkbox_field('billmate_email',
                        $order->customer['email_address'],!empty($billmate_email))));

        //Shipping/billing address notice
        $fields[] = array('title' => MODULE_PAYMENT_BILLMATE_ADDR_TITLE, 'field' => MODULE_PAYMENT_BILLMATE_ADDR_NOTICE);

        return array('id' => $this->code,
                'module' => $this->title,
                'fields' => $fields);
    }

    function pre_confirmation_check() {
        global $billmate_testmode, $billmate_livemode, $order, $GA_OLD, $KRED_SE_PNO, $user_billing, $db;

        //Livemode to set the right Host and Port
        $livemode = $this->billmate_livemode;


        //INCLUDE THE VALIDATION FILE
        @include_once DIR_FS_CATALOG . DIR_WS_CLASSES . 'billmate/validation.php';

        // Set error reasons
        $errors = array();

        //Fill user array with billing details
        $user_billing['billmate_pnum'] = $_POST['billmate_pnum'];
        $user_billing['billmate_email'] = $_POST['billmate_email'];
        $user_billing['billmate_invoice_type'] = $_POST['billmate_invoice_type'];


        //Store values into Session
		$_SESSION['user_billing'] = $user_billing;

        //Validation for Company fields else Private fields
        if (!validate_pno_se($_POST['billmate_pnum'])) {
            $errors[] = MODULE_PAYMENT_BILLMATE_PERSON_NUMBER;
        }
		if(empty($_POST['billmate_email'])){
			$errors[] = sprintf( MODULE_PAYMENT_BILLMATE_EMAIL, $order->customer['email_address']);;
		}
        if (!empty($errors)) {
			$_SESSION['error'] = implode(', ', $errors);
            zen_redirect( zen_href_link(FILENAME_CHECKOUT_PAYMENT).'&payment_error=billmate&error=true' );
        }

        $pno = $this->billmate_pnum = $_POST['billmate_pnum'];
        $eid = MODULE_PAYMENT_BILLMATE_EID;
        $secret = MODULE_PAYMENT_BILLMATE_SECRET;

        $pnoencoding = $KRED_SE_PNO;
        $type = $GA_OLD;

		$ssl = true;
		$debug = false;

		$k = new BillMateAPI((int)$eid,(float)$secret,$ssl,$debug);
		$result = $k->getAddress( $pno );

        if (!is_array($result)) {
			$_SESSION['error'] = strip_tags($result)." ($status)";
			zen_redirect( zen_href_link(FILENAME_CHECKOUT_PAYMENT).'&payment_error=billmate&error=true');
        }
       
        $this->addrs = $result;

				$result[0][0] = utf8_encode($result[0][0]);
				$result[0][1] = utf8_encode($result[0][1]);
				$result[0][2] = utf8_encode($result[0][2]);
				$result[0][3] = utf8_encode($result[0][3]);
				$result[0][4] = utf8_encode($result[0][4]);

 
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

        if( $resultessNotMatched || $shippingAndBilling ){
            if( empty($_POST['geturl'])){
	            $html = '<p><b>'.MODULE_PAYMENT_BILLMATE_CORRECT_ADDRESS.' </b></p>'.($result[0][0]).' '.$result[0][1].'<br>'.$result[0][2].'<br>'.$result[0][3].' '.$result[0][4].'<div style="padding: 17px 0px;"> <i>'.MODULE_PAYMENT_BILLMATE_CORRECT_ADDRESS_OPTION.'</i></div> <input type="button" value="'.MODULE_PAYMENT_BILLMATE_YES.'" onclick="updateAddress();" class="button"/> <input type="button" value="'.MODULE_PAYMENT_BILLMATE_NO.'" onclick="closefunc(this)" class="button" style="float:right" />';
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
	            function updateAddress(){
						var e = document.createElement(\'input\');
						e.name="geturl"; e.value ="true"; e.type ="hidden";
						document.getElementsByName(\'billmate_pnum\').item(0).parentNode.insertBefore(e, document.getElementsByName(\'billmate_pnum\').item(0));
						document.forms[\'checkout_payment\'].submit();
	            }
	            function closefunc(obj){
    	           modalWin.HideModalPopUp();
	            };
				 modalWin.ShowMessage(\''.$html.'\',250,500,\''.MODULE_PAYMENT_BILLMATE_ADDRESS_WRONG.'\');</script>';
	            unset($_SESSION['WrongAddress']);
                $WrongAddress = $code;
                global $messageStack;
                $_SESSION['WrongAddress'] = $WrongAddress;
				$_SESSION['error']= "invalidaddress";
                zen_redirect( zen_href_link(FILENAME_CHECKOUT_PAYMENT).'&payment_error=billmate&error=true' );
	        }else{
	            if(!match_usernamevp( $fullname , $apiName)){
                    if($result[0][0] == "") {
                        $this->billmate_fname = $result[0][1];
                        $this->billmate_lname = $result[0][0];
                    }else {
                        $this->billmate_fname = $result[0][0];
                        $this->billmate_lname = $result[0][1];
                    }
                }
                $this->billmate_street = $result[0][2];
                $this->billmate_postno = $result[0][3];
                $this->billmate_city = $result[0][4];
                $order->delivery['firstname'] = $this->billmate_fname;
                $order->billing['firstname'] = $this->billmate_fname;
                $order->delivery['lastname'] = $this->billmate_lname;
                $order->billing['lastname'] = $this->billmate_lname;
                $order->delivery['street_address'] = $this->billmate_street;
                $order->billing['street_address'] = $this->billmate_street;
                $order->delivery['postcode'] = $this->billmate_postno;
                $order->billing['postcode'] = $this->billmate_postno;
                $order->delivery['city'] = $this->billmate_city;
                $order->billing['city'] = $this->billmate_city;

                $order->delivery['telephone'] = $_POST['billmate_email'];
                $order->billing['telephone'] = $_POST['billmate_email'];

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
        return array('title' => MODULE_PAYMENT_BILLMATE_TEXT_CONFIRM_DESCRIPTION);
    }

    function process_button() {
        global $order, $order_total_modules, $billmate_ot, $shipping, $db;

		unset($_SESSION['WrongAddress']);
        $counter = 1;
        $a=count($this->addrs);
        $process_button_string = "";
        if($a>1) {
            $process_button_string .= "<div align='left'>";
            $process_button_string .=  "<table border='0' width='100%' cellspacing='1' cellpadding='2' class='infoBox'>";
            $process_button_string .=  "<tr><td class='main'><b>".MODULE_PAYMENT_BILLMATE_CHOOSEALTERNATIVES."</b></td></tr>";
            $process_button_string .=  "<tr class='infoBoxContents'><td>";

            foreach ($this->addrs as $result) {
                $this->billmate_fname = $result[0];
                $this->billmate_lname = $result[1];
                $this->billmate_street = $result[2];
                $this->billmate_postno = $result[3];
                $this->billmate_city = $result[4];

                if($counter == 1) {
                    $checked = true;
                }else {
                    $checked = false;
                }

                $process_button_string .=
                        zen_draw_radio_field('addr_num', $counter, $checked, '').
                        zen_draw_hidden_field('billmate_pnum'.$counter,
                        $this->billmate_pnum).
                        zen_draw_hidden_field('billmate_fname'.$counter,
                        $this->billmate_fname).
                        zen_draw_hidden_field('billmate_lname'.$counter,
                        $this->billmate_lname).
                        zen_draw_hidden_field('billmate_street'.$counter,
                        $this->billmate_street).
                        $this->billmate_street." ".
                        zen_draw_hidden_field('billmate_postno'.$counter,
                        $this->billmate_postno).
                        $this->billmate_postno." ".
                        zen_draw_hidden_field('billmate_city'.$counter,
                        $this->billmate_city).
                        $this->billmate_city." ".
                        zen_draw_hidden_field('billmate_phone2'.$counter,
                        $this->billmate_phone2).
                        zen_draw_hidden_field('billmate_phone'.$counter,
                        $this->billmate_phone).
                        zen_draw_hidden_field('billmate_email'.$counter,
                        $this->billmate_email).
                        zen_draw_hidden_field('billmate_invoice_type'.
                        $counter,  $this->billmate_invoice_type).
                        zen_draw_hidden_field('billmate_reference'.
                        $counter,  $this->billmate_reference);
                "<br/>";


                $counter++;
            }
            $process_button_string .= "</td></tr></table><br/></div>";
        }else {
            $process_button_string .=
                    zen_draw_hidden_field('addr_num', $counter, $checked, '').
                    zen_draw_hidden_field('billmate_pnum'.$counter,$this->billmate_pnum).
                    zen_draw_hidden_field('billmate_ysalary'.$counter,$this->billmate_ysalary).
                    zen_draw_hidden_field('billmate_fname'.$counter,$this->billmate_fname).
                    zen_draw_hidden_field('billmate_lname'.$counter,$this->billmate_lname).
                    zen_draw_hidden_field('billmate_street'.$counter,
                    $this->billmate_street).
                    zen_draw_hidden_field('billmate_postno'.$counter,
                    $this->billmate_postno).
                    zen_draw_hidden_field('billmate_city'.$counter,$this->billmate_city).
                    zen_draw_hidden_field('billmate_phone2'.$counter,$this->billmate_phone2).
                    zen_draw_hidden_field('billmate_phone'.$counter,$this->billmate_phone).
                    zen_draw_hidden_field('billmate_email'.$counter,$this->billmate_email).
                    zen_draw_hidden_field('billmate_invoice_type'.$counter,  $this->billmate_invoice_type).
                    zen_draw_hidden_field('billmate_reference'.$counter,  $this->billmate_reference);

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
		$billmate_ot = array();
		
        if (is_array($order_totals)) {
            reset($order_totals);
            $j = 0;
            $table = preg_split("/[,]/", MODULE_PAYMENT_BILLMATE_ORDER_TOTAL_IGNORE);

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
                    $billmate_ot['code_size_'.$j] = $size;

                    for ($i=0; $i<$size; $i++) {
                        $billmate_ot['title_'.$j.'_'.$i] = html_entity_decode($GLOBALS[$class]->output[$i]['title']);

                        $billmate_ot['text_'.$j.'_'.$i] = $GLOBALS[$class]->output[$i]['text'];
                        if (is_numeric($GLOBALS[$class]->deduction) &&
                                $GLOBALS[$class]->deduction > 0) {
                            $billmate_ot['value_'.$j.'_'.$i] = -$GLOBALS[$class]->deduction;
                        }
                        else {
                            $billmate_ot['value_'.$j.'_'.$i] = $GLOBALS[$class]->output[$i]['value'];

                            // Add tax rate for shipping address and invoice fee
                            if ($class == 'ot_shipping') {
                                //Set Shipping VAT
                                $shipping_id = @explode('_', $shipping['id']);
                                $tax_class = @$GLOBALS[$shipping_id[0]]->tax_class;
                                $tax_rate = 0;
                                if($tax_class > 0) {
                                    $tax_rate = zen_get_tax_rate($tax_class, $order->billing['country']['id'], ($order->billing['zone_id'] > 0) ? $order->billing['zone_id'] : null);
                                }
                                $billmate_ot['tax_rate_'.$j.'_'.$i] = $tax_rate;
                            } else {
                                $billmate_ot['tax_rate_'.$j.'_'.$i] = $GLOBALS[$class]->output[$i]['tax_rate'];
                            }
                        }

                        $billmate_ot['code_'.$j.'_'.$i] = $GLOBALS[$class]->code;
                    }
                    $j += 1;
                }
            }
            $billmate_ot['code_entries'] = $j;
        }
		$_SESSION['billmate_ot'] = $billmate_ot;
		
        $process_button_string .= zen_draw_hidden_field(zen_session_name(),
                zen_session_id());
        return $process_button_string;
    }

    function before_process() {
		
     global $order, $customer_id, $currency, $currencies, $sendto, $billto,
               $billmate_ot, $billmate_livemode, $billmate_testmode,$insert_id, $db;

		//Assigning billing session
		$billmate_ot = $_SESSION['billmate_ot'];

        //Set the right Host and Port
        $livemode = $this->billmate_livemode;
				
        /*$addr_num = $_POST['addr_num'];
        $order->delivery['firstname'] = $_POST['billmate_fname'.$addr_num];
        $order->billing['firstname'] =  $_POST['billmate_fname'.$addr_num];
        $order->delivery['lastname'] =  $_POST['billmate_lname'.$addr_num];
        $order->billing['lastname'] =   $_POST['billmate_lname'.$addr_num];
        $order->delivery['street_address'] =
                $_POST['billmate_street'.$addr_num];
        $order->billing['street_address'] =
                $_POST['billmate_street'.$addr_num];
        $order->delivery['postcode'] = $_POST['billmate_postno'.$addr_num];
        $order->billing['postcode'] = $_POST['billmate_postno'.$addr_num];
        $order->delivery['city'] = $_POST['billmate_city'.$addr_num];
        $order->billing['city'] = $_POST['billmate_city'.$addr_num];
*/
        $eid = (int)MODULE_PAYMENT_BILLMATE_EID;
        $secret = (int)MODULE_PAYMENT_BILLMATE_SECRET;

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

            if (MODULE_PAYMENT_BILLMATE_ARTNO == 'id' ||
                    MODULE_PAYMENT_BILLMATE_ARTNO == '') {
                $goodsList[] =
                        mk_goods_flags($order->products[$i]['qty'],
                        (string)zen_get_prid($order->products[$i]['id']),
                        $order->products[$i]['name'] . $attributes,
                        $price_without_tax,
                        $order->products[$i]['tax'],
                        0,
                        0); //incl VAT
            } else {
                $goodsList[] =
                        mk_goods_flags($order->products[$i]['qty'],
                        $order->products[$i][MODULE_PAYMENT_BILLMATE_ARTNO],
                        $order->products[$i]['name'] . $attributes,
                        $price_without_tax,
                        $order->products[$i]['tax'],
                        0,
                        0); //incl VAT
            }
        }

        // Then the extra charnges like shipping and invoicefee and
        // discount.

        $extra = $billmate_ot['code_entries'];
        //end hack

        for ($j=0 ; $j<$extra ; $j++) {
            $size = $billmate_ot["code_size_".$j];
            for ($i=0 ; $i<$size ; $i++) {
                $value = $billmate_ot["value_".$j."_".$i];
                $name = $billmate_ot["title_".$j."_".$i];
                $tax = $billmate_ot["tax_rate_".$j."_".$i];
                $name = rtrim($name, ":");
                $code = $billmate_ot["code_".$j."_".$i];
                $flags = 0; //INC VAT
                if($code == 'ot_shipping') {
                    $flags += 8; //IS_SHIPMENT
                }
                else if($code == 'ot_'.$this->code.'_fee') {
                    $flags += 16; //IS_HANDLING
                }
				if( $code == 'ot_billmate_fee' ){
					$flags = 16;
				}
/*
                if(DISPLAY_PRICE_WITH_TAX == 'true') {
                    $price_with_tax = $currencies->get_value($currency) * $value * 100;
                } else {
                    $price_with_tax = $currencies->get_value($currency) * $value * 100*(($tax/100)+1);
                }*/
				
				$price_with_tax = $currencies->get_value($currency) * $value * 100;
                
				if ($value != "" && $value != 0) {
                    $goodsList[] = mk_goods_flags(1, "", BillmateUtils::convertData($name), $price_with_tax, $tax, 0, $flags);
                }

            }
        }


        $secret = MODULE_PAYMENT_BILLMATE_SECRET;
        $estoreOrderNo = "";
        $shipmentfee = 0;
        $shipmenttype = 1;

        $handlingfee = 0;
        $ready_date = "";

        $pno   = BillmateUtils::convertData($_POST['billmate_pnum1']);

		$pclass = -1;
		$ship_address = $bill_address = array();

        $countryData = BillmateCountry::getCountryData($order->billing['country']['iso_code_3']);
	
	    $ship_address = array(
		    'email'           => $order->customer['email_address'],
		    'telno'           => $order->customer['telephone'],
		    'cellno'          => '',
		    'fname'           => $order->delivery['firstname'],
		    'lname'           => $order->delivery['lastname'],
		    'company'         => $order->delivery['company'],
		    'careof'          => '',
		    'street'          => $order->delivery['street_address'],
		    'house_number'    => isset($house_no)? $house_no: '',
		    'house_extension' => isset($house_ext)?$house_ext:'',
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
		    'careof'          => '',
		    'street'          => $order->billing['street_address'],
		    'house_number'    => '',
		    'house_extension' => '',
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
			"pclass"=>$pclass,
			"shipInfo"=>array("delay_adjust"=>"1"),
			"travelInfo"=>array(),
			"incomeInfo"=>array(),
			"bankInfo"=>array(),
			"sid"=>array("time"=>microtime(true)),
			"extraInfo"=>array(array("cust_no"=>(string)$customer_id))
		);
		
		
		$ssl = true;
		$debug = false;

		$k = new BillMateAPI($eid,$secret,$ssl,$debug, $this->billmate_testmode);
		$result1 = $k->AddInvoice($pno,$ship_address,$bill_address,$goodsList,$transaction);

        if (is_array($result1)) {

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
            if(is_array($check_address->fields) && count($check_address->fields) > 0) {
                $sendto = $billto = $check_address->fields['address_book_id'];
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
            unset($_SESSION['billmate_ot']);
            return false;
        } else {
			$_SESSION['error'] = utf8_encode($result1);
			zen_redirect( zen_href_link(FILENAME_CHECKOUT_PAYMENT).'&payment_error=billmate&error=true' );				
        }
    }

    function after_process() {
        global $insert_id, $order, $db;

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
        $secret = (float)MODULE_PAYMENT_BILLMATE_SECRET;
        $eid = (int)MODULE_PAYMENT_BILLMATE_EID;
        $invno = (int)$order->billmateref;

		$ssl = true;
		$debug = false;
		$k = new BillMateAPI($eid,$secret,$ssl,$debug, $this->billmate_testmode);
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
                    "'MODULE_PAYMENT_BILLMATE_STATUS'");
            $this->_check = $check_query->RecordCount();
        }
        return $this->_check;
    }

    function install() {
		global $db;
        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Billmate Module', 'MODULE_PAYMENT_BILLMATE_STATUS', 'True', 'Do you want to accept Billmate payments?', '6', '0', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Payment Zone', 'MODULE_PAYMENT_BILLMATE_ZONE', '0', 'If a zone is selected, only enable this payment method for that zone.', '6', '2', 'zen_get_zone_class_title', 'zen_cfg_pull_down_zone_classes(', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Merchant ID', 'MODULE_PAYMENT_BILLMATE_EID', '0', 'Merchant ID (estore id) to use for the Billmate service (provided by Billmate)', '6', '0', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Shared secret', 'MODULE_PAYMENT_BILLMATE_SECRET', '', 'Shared secret to use with the Billmate service (provided by Billmate)', '6', '0', now())");


        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Product artno attribute (id or model)', 'MODULE_PAYMENT_BILLMATE_ARTNO', 'id', 'Use the following product attribute for ArtNo.', '6', '2', 'zen_cfg_select_option(array(\'id\', \'model\'),', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Ignore table', 'MODULE_PAYMENT_BILLMATE_ORDER_TOTAL_IGNORE', 'ot_tax,ot_total,ot_subtotal', 'Ignore these entries from order total list when compiling the invoice data', '6', '2', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Credit limit', 'MODULE_PAYMENT_BILLMATE_ORDER_LIMIT', '50000', 'Only show this payment alternative for orders less than the value below.', '6', '2', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of display.', 'MODULE_PAYMENT_BILLMATE_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Order Status', 'MODULE_PAYMENT_BILLMATE_ORDER_STATUS_ID', '0', 'Set the status of orders made with this payment module to this value', '6', '0', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Testmode', 'MODULE_PAYMENT_BILLMATE_TESTMODE', 'False', 'Do you want to activate the Testmode? We will not pay for the invoices created with the test persons nor companies and we will not collect any fees as well.', '6', '0', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Countries', 'MODULE_PAYMENT_BILLMATE_ENABLED_COUNTRYIES', 'se,fi,dk,no', 'Available in selected countries<br/>se = Sweden<br/>fi = Finland<br/>dk = Denmark<br/>no = Norway', '9', '0', now())");

    }

    function remove() {
		global $db;
        $db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
        return array('MODULE_PAYMENT_BILLMATE_STATUS',
                'MODULE_PAYMENT_BILLMATE_ORDER_STATUS_ID',
                'MODULE_PAYMENT_BILLMATE_EID',
                'MODULE_PAYMENT_BILLMATE_SECRET',
                'MODULE_PAYMENT_BILLMATE_ARTNO',
                'MODULE_PAYMENT_BILLMATE_ENABLED_COUNTRYIES',
                'MODULE_PAYMENT_BILLMATE_ORDER_LIMIT',
                'MODULE_PAYMENT_BILLMATE_ORDER_TOTAL_IGNORE',
                'MODULE_PAYMENT_BILLMATE_TESTMODE',
                'MODULE_PAYMENT_BILLMATE_ZONE',
                'MODULE_PAYMENT_BILLMATE_SORT_ORDER');
    }

}

$i = $includebackup;
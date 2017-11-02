<?php
/**
 * Created by PhpStorm.
 * User: jesper
 * Date: 15-04-01
 * Time: 13:28
 */
ini_set('display_errors',1);
error_reporting(E_ALL);
global $user_billing, $language, $languages_id,$db;
	chdir('../');
	require('includes/application_top.php');
	require(DIR_FS_CATALOG . DIR_WS_CLASSES . 'billmate/Billmate.php');
	require(DIR_FS_CATALOG . DIR_WS_CLASSES . 'billmate/utf8.php');
	require_once(DIR_FS_CATALOG . DIR_WS_CLASSES . 'billmate/billmateutils.php');


require(DIR_WS_CLASSES . 'order.php');
	// load the language file according to set language.
	include(DIR_WS_LANGUAGES . $_SESSION['language'] . '/modules/payment/billmate_invoice.php');
	$order = new order;
	$method = $_GET['method'];
	if($method == 'billmate_invoice')
	{
		$method = 'billmate';
		$secret   = MODULE_PAYMENT_BILLMATE_SECRET;
		$eid      = MODULE_PAYMENT_BILLMATE_EID;
		$testmode = ((MODULE_PAYMENT_BILLMATE_TESTMODE == 'True')) ? true : false;
	}
	if($method == 'pcbillmate'){
		$secret   = MODULE_PAYMENT_PCBILLMATE_SECRET;
		$eid      = MODULE_PAYMENT_PCBILLMATE_EID;
		$testmode = ((MODULE_PAYMENT_PCBILLMATE_TESTMODE == 'True')) ? true : false;
	}
	$ssl = true;
	$debug = false;



	$languageCode = $db->Execute("select code from languages where languages_id = " . $_SESSION['languages_id']);
	if(!defined('BILLMATE_LANGUAGE')) define('BILLMATE_LANGUAGE',$languageCode->fields['code']);

	$billmate = new BillMate($eid,$secret,true,$testmode,false);
	$address = $billmate->getAddress(array('pno' => $_POST[$method.'_pnum']));



	$user_billing[$method.'_pnum'] = $_POST[$method.'_pnum'];
	$user_billing[$method.'_email'] = $_POST[$method.'_email'];
	$user_billing[$method.'_invoice_type'] = $_POST[$method.'_invoice_type'];

	$_SESSION['user_billing'] = $user_billing;

	if (isset($address['code']) || empty($address) || !is_array($address))
		die(json_encode(array('success' => false, 'content' => utf8_encode($address['message']),'popup' => false)));

	foreach($address as $key => $value)
		$address[$key] = convertToUTF8($value);



	$billing = $order->billing;
	$delivery = $order->delivery;

	$fullname = $billing['name'] .' '.$billing['company'];
	if( empty ( $address['name'] ) ){
		$apiName = $fullname;
	} else {
		$apiName  = $address['firstname'].' '.$address['lastname'];
	}


	$firstArr = explode(' ', $order->billing['firstname']);
	$lastArr  = explode(' ', $order->billing['lastname']);

	if( empty( $address['firstname'] ) ){
		$apifirst = $firstArr;
		$apilast  = $lastArr ;
	}else {
		$apifirst = explode(' ', $address['firstname'] );
		$apilast  = explode(' ', $address['lastname'] );
	}


	$apiMatchedName   = !empty($matchedFirst) && !empty($matchedLast);

	$addressNotMatched = !isEqual($address['street'], $billing['street_address'] ) ||
	                     !isEqual($address['zip'], $billing['postcode']) ||
	                     !isEqual($address['city'], $billing['city']) ||
	                     !isEqual($address['country'], $billing['country']['iso_code_2'] ||
	                     !isEqual($apiName,$fullname) );

	$shippingAndBilling =  !isEqual($billing['name'],$delivery['name']) ||
	                       !isEqual($billing['street_address'],  $delivery['street_address'] ) ||
	                       !isEqual($billing['postcode'], $delivery['postcode']) ||
	                       !isEqual($billing['city'], $delivery['city']) ||
	                       !isEqual($billing['country']['iso_code_3'], $delivery['country']['iso_code_3']);

	if( $addressNotMatched || $shippingAndBilling ){


		if(empty($_POST['geturl'])){
			$html = '<span style="line-height: 1.4em;">'.($address['firstname']).' '.$address['lastname'].'<br>'.$address['street'].'<br>'.$address['zip'].' '.$address['city'].'</span><div style="margin-top:1em;"><input type="button" value="'.MODULE_PAYMENT_BILLMATE_YES.'" onclick="window.updateAddress();" class="billmate_button"/> <a onclick="window.closefunc(this)" class="linktag"/>'.MODULE_PAYMENT_BILLMATE_NO.'</a></div> ';
			die(json_encode(array('success' => false, 'content' => convertToUTF8($html),'popup' => true)));
		} else {
			if($address['firstname'] == "") {
				$billmate_name = $order->billing['name'];

				$company_name   = $address['company'];
			}else {
				$billmate_name = $address['firstname'].' '.$address['lastname'];
				$company_name   = '';
			}

			$billmate_street = $address['street'];
			$billmate_postno = $address['zip'];
			$billmate_city = $address['city'];

			/*
			 * 'entry_firstname' => $sendto['firstname'],
                                  'entry_lastname' => $sendto['lastname'],
                                  'entry_company' => $sendto['company'],
                                  'entry_street_address' => $sendto['street_address'],
                                  'entry_suburb' => $sendto['suburb'],
                                  'entry_postcode' => $sendto['postcode'],
                                  'entry_city' => $sendto['city'],
                                  'entry_zone_id' => $sendto['zone_id'],
                                  'zone_name' => $sendto['zone_name'],
                                  'entry_country_id' => $sendto['country_id'],
                                  'countries_id' => $sendto['country_id'],
                                  'countries_name' => $sendto['country_name'],
                                  'countries_iso_code_2' => $sendto['country_iso_code_2'],
                                  'countries_iso_code_3' => $sendto['country_iso_code_3'],
                                  'address_format_id' => $sendto['address_format_id'],
                                  'entry_state' => $sendto['zone_name']
			 */

			$order->delivery['name'] = $billmate_name;
			$order->billing['name'] = $billmate_name;
			$order->delivery['company'] = $company_name;
			$order->billing['suburb'] = $order->delivery['suburb'] = '';
			$order->billing['company'] = $company_name;
			$order->delivery['street_address'] = $billmate_street;
			$order->billing['street_address'] = $billmate_street;
			$order->delivery['postcode'] = $billmate_postno;
			$order->billing['postcode'] = $billmate_postno;
			$order->delivery['city'] = $billmate_city;
			$order->billing['city'] = $billmate_city;


			//Set same country information to delivery
			$order->delivery['state'] = $order->billing['state'];
			$order->delivery['zone_id'] = $order->billing['zone_id'];
			$order->delivery['country_id'] = $order->billing['country_id'];
			$order->delivery['country']['id'] = $order->billing['country']['id'];
			$order->delivery['country']['title'] = $order->billing['country']['title'];
			$order->delivery['country']['iso_code_2'] = $order->billing['country']['iso_code_2'];
			$order->delivery['country']['iso_code_3'] = $order->billing['country']['iso_code_3'];
			die(json_encode(array('success' => true,'debug' => print_r($order,true))));
		}

	} else {
		die(json_encode(array('success' => true)));
	}


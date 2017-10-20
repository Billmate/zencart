<?php
/**
 * Created by PhpStorm.
 * User: Boxedsolutions
 * Date: 2016-11-24
 * Time: 09:11
 */
ini_set('display_errors',1);

chdir('../../../../');
require_once('includes/application_top.php');
require_once(DIR_FS_CATALOG . DIR_WS_CLASSES . 'billmate/Billmate.php');
require_once(DIR_FS_CATALOG . DIR_WS_CLASSES . 'billmate/utf8.php');

function partpay($order_id){
    global $customer_id, $currency, $currencies, $sendto, $billto,
           $pcbillmate,$insert_id, $languages_id, $language_id, $language, $currency, $cart_billmate_card_ID,$billmate_pno,$pclass,$db;

    $pcbillmate = $_SESSION['pcbillmate_ot'];

    require_once(DIR_FS_CATALOG . DIR_WS_CLASSES . 'billmate/billmateutils.php');
    include_once(DIR_WS_LANGUAGES . $language . '/modules/payment/pcbillmate.php');

    require(DIR_WS_CLASSES . 'order.php');




    $order = new Order($order_id);

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

        if (MODULE_PAYMENT_PCBILLMATE_ARTNO == 'id' ||
            MODULE_PAYMENT_PCBILLMATE_ARTNO == '') {
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
    $names = explode(' ',$order->delivery['name']);
    $firstname = array_shift($names);
    $lastname = implode(' ',$names);
    $ship_address = array(
        "firstname" => $firstname,
        "lastname" 	=> $lastname,
        "company" 	=> $order->delivery['company'],
        "street" 	=> $order->delivery['street_address'],
        "street2" 	=> "",
        "zip" 		=> $order->delivery['postcode'],
        "city" 		=> $order->delivery['city'],
        "country" 	=> is_array($order->delivery['country']) ? getCountryIsoFromName($order->delivery['country']['title']) : getCountryIsoFromName($order->delivery['country']),
        "phone" 	=> $order->customer['telephone'],
    );


    $names = explode(' ',$order->billing['name']);
    $firstname = array_shift($names);

    $lastname = implode(' ',$names);
    $bill_address = array(
        "firstname" => $firstname,
        "lastname" 	=> $lastname,
        "company" 	=> $order->billing['company'],
        "street" 	=> $order->billing['street_address'],
        "street2" 	=> "",
        "zip" 		=> $order->billing['postcode'],
        "city" 		=> $order->billing['city'],
        "country" 	=> is_array($order->billing['country']) ? getCountryIsoFromName($order->billing['country']['title']) : getCountryIsoFromName($order->billing['country']),
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
    $languageCode = $db->Execute("select code from languages where languages_id = " . $languages_id);
    $langCode  = (strtolower($languageCode->fields['code']) == 'se') ? 'sv' : $languageCode->fields['code'];

    $testmode = false;
    if ((MODULE_PAYMENT_PCBILLMATE_TESTMODE == 'True')) {
        $testmode = true;
    }
    if(!defined('BILLMATE_LANGUAGE')) define('BILLMATE_LANGUAGE',$langCode);
    if(!defined('BILLMATE_SERVER')) define('BILLMATE_SERVER','2.1.7');
    if(isset($_SESSION['billmate_pno'])){
        $pno = $_SESSION['billmate_pno'];
    }

    $k = new BillMate($eid,$secret,$ssl,$testmode,$debug,$codes);
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
        "accepturl" => zen_href_link(FILENAME_CHECKOUT_PROCESS,'', 'SSL'),
        "cancelurl" => zen_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL'),
        "callbackurl" => zen_href_link('ext/modules/payment/billmate/common_ipn.php', '', 'SSL')
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
    $totalwithtax = round(getTotal($order_id)*100,0);
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


    if(isset($result1->code)){
        billmate_remove_order($cart_billmate_card_ID,true);
        zen_session_unregister('cart_Billmate_card_ID');
        zen_redirect(BillmateUtils::error_link(FILENAME_CHECKOUT_PAYMENT,
            'payment_error=pcbillmate&error=' . utf8_encode($result1->message)));
        exit;
    } else {
        if($result1->status == 'WaitingForBankIDIdentification' || $result1->status == 'WaitingForBankIDIdentificationForAddressCheck'){
            zen_redirect($result1->url);
            exit;
        } else {
            zen_redirect(zen_href_link(FILENAME_CHECKOUT_PROCESS, 'credentials=' . urlencode(json_encode($result1->raw_response['credentials'])) . '&data=' . urlencode(json_encode($result1->raw_response['data'])), 'SSL'));
            exit;
        }
    }
    
}

function invoice($order_id){
    global $customer_id, $currency, $currencies, $sendto, $billto,
           $billmate_ot,$insert_id, $languages_id, $language_id, $language, $currency, $cart_billmate_card_ID,$billmate_pno,$billmate_billing,$db;

    $billmate_ot = $_SESSION['billmate_ot'];

    require_once(DIR_FS_CATALOG . DIR_WS_CLASSES . 'billmate/billmateutils.php');
    include_once(DIR_WS_LANGUAGES . $language . '/modules/payment/billmate_invoice.php');
    require(DIR_WS_CLASSES . 'order.php');



    $orderTemp = new Order;

    $order = new Order($order_id);

   

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

        if (MODULE_PAYMENT_BILLMATE_ARTNO == 'id' ||
            MODULE_PAYMENT_BILLMATE_ARTNO == '') {
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
                    $order->products[$i][MODULE_PAYMENT_BILLMATE_ARTNO],
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

            $price_without_tax = $currencies->get_value($currency) * $value * 100;
            if(DISPLAY_PRICE_WITH_TAX == 'true') {
                $price_without_tax = $price_without_tax/(($tax+100)/100);
            }

            $codes[] = $code;
            if( $code == 'ot_discount' ) { $price_without_tax = 0 - $price_without_tax; }
            if( $code == 'ot_shipping' ){
                $shippingPrice = $price_without_tax;
                $shippingTaxRate = $tax;
                $totalValue += $shippingPrice;
                $taxValue += $shippingPrice * ($shippingTaxRate/100);

                continue;
            }
            if( $code == 'ot_billmate_fee' ){
                $handlingPrice = $price_without_tax;
                $handlingTaxRate = $tax;
                $taxValue += $handlingPrice * ($handlingTaxRate/100);
                $totalValue += $handlingPrice;
                continue;
            }

            if ($value != "" && $value != 0) {
                $totals = $totalValue;
                foreach($prepareDiscounts as $tax => $value)
                {
                    $percent = $value / $totals;
                    $price_without_tax_out = $price_without_tax * $percent;
                    $temp = mk_goods_flags(1, "", ($name).' '.(int)$tax.'% '.MODULE_PAYMENT_BILLMATE_VAT, $price_without_tax_out, $tax, 0, 0);
                    $totalValue += $temp['withouttax'];
                    $taxValue += $temp['tax'];
                    $goodsList[] = $temp;
                }
            }

        }
    }

    $secret = MODULE_PAYMENT_BILLMATE_SECRET;
    $eid = MODULE_PAYMENT_BILLMATE_EID;

    $ship_address = $bill_address = array();
    $countryData = BillmateCountry::getSwedenData();
    //$order->delivery = $order->billing = $billmate_billing;

    $names = explode(' ',$order->delivery['name']);


    $firstname = array_shift($names);
    $lastname = implode(' ',$names);
    $ship_address = array(
        "firstname" => $firstname,
        "lastname" 	=> $lastname,
        "company" 	=> $order->delivery['company'],
        "street" 	=> $order->delivery['street_address'],
        "street2" 	=> "",
        "zip" 		=> $order->delivery['postcode'],
        "city" 		=> $order->delivery['city'],
        "country" 	=> is_array($order->delivery['country']) ? getCountryIsoFromName($order->delivery['country']['title']) : getCountryIsoFromName($order->delivery['country']),
        "phone" 	=> $order->customer['telephone'],
    );
    $names = explode(' ',$order->billing['name']);

    $firstname = array_shift($names);
    $lastname = implode(' ',$names);
    $bill_address = array(
        "firstname" => $firstname,
        "lastname" 	=> $lastname,
        "company" 	=> $order->billing['company'],
        "street" 	=> $order->billing['street_address'],
        "street2" 	=> "",
        "zip" 		=> $order->billing['postcode'],
        "city" 		=> $order->billing['city'],
        "country" 	=> is_array($order->billing['country']) ? getCountryIsoFromName($order->billing['country']['title']) : getCountryIsoFromName($order->billing['country']),
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
    $testmode =
    $languageCode = $db->Execute("select code from languages where languages_id = " . $_SESSION['languages_id']);
    $langCode  = (strtolower($languageCode->fields['code']) == 'se') ? 'sv' : $languageCode->fields['code'];

    if(!defined('BILLMATE_LANGUAGE')) define('BILLMATE_LANGUAGE',$langCode);
    if(!defined('BILLMATE_SERVER')) define('BILLMATE_SERVER','2.1.7');
    if(isset($_SESSION['billmate_pno'])){
        $pno = $_SESSION['billmate_pno'];
    }
    $testmode = false;
    if ((MODULE_PAYMENT_BILLMATE_TESTMODE == 'True')) {
        $testmode = true;
    }
    $k = new BillMate($eid,$secret,$ssl,$testmode,$debug,$codes);
    $invoiceValues = array();
    $lang = $languageCode['code'] == 'se' ? 'sv' : $languageCode['code'];
    $invoiceValues['PaymentData'] = array(	"method" => "1",		//1=Factoring, 2=Service, 4=PartPayment, 8=Card, 16=Bank, 24=Card/bank and 32=Cash.
        "currency" => $currency, //"SEK",
        "language" => $lang,
        "country" => "SE",
        "orderid" => (string)$cart_billmate_card_ID,
        "bankid" => true,
        "returnmethod" => "GET",
        "accepturl" => zen_href_link(FILENAME_CHECKOUT_PROCESS,'', 'SSL'),
        "cancelurl" => zen_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL'),
        "callbackurl" => zen_href_link('ext/modules/payment/billmate/common_ipn.php', '', 'SSL')
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

    $totaltax = round($taxValue,0);
    $totalwithtax = round(getTotal($order_id)*100,0);
    //$totalwithtax += $shippingPrice * ($shippingTaxRate/100);
    $totalwithouttax = $totalValue;
    $rounding = $totalwithtax - ($totalwithouttax+$totaltax);

    $invoiceValues['Cart'] = array(
        "Handling" => array(
            "withouttax" => ($handlingPrice)?round($handlingPrice,0):0,
            "taxrate" => ($handlingTaxRate)?$handlingTaxRate:0
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
    if(isset($result1->code)){
        billmate_remove_order($order_id,true);
        zen_session_unregister('cart_Billmate_card_ID');
        zen_redirect(BillmateUtils::error_link(FILENAME_CHECKOUT_PAYMENT,
            'payment_error=billmate_invoice&error=' . utf8_encode($result1->message)));
        exit;
    } else {
        if($result1->status == 'WaitingForBankIDIdentification' || $result1->status == 'WaitingForBankIDIdentificationForAddressCheck'){
            zen_redirect($result1->url);
            exit;
        } else {
            zen_redirect(zen_href_link(FILENAME_CHECKOUT_PROCESS, 'credentials=' . urlencode(json_encode($result1->raw_response['credentials'])) . '&data=' . urlencode(json_encode($result1->raw_response['data'])), 'SSL'));
            exit;
        }
    }
}

switch($_GET['method']){
    case 'invoice':
        error_log(print_r($_GET,true));
        invoice($_GET['order_id']);
        break;
    case 'partpay':
        partpay($_GET['order_id']);
        break;
}

function getOrder($order_id){
    global $db;
    
    $languages_id = $_SESSION['languages_id'];
    $toReturn = new stdClass();
    $order = $db->Execute("select customers_id, customers_name, customers_company, customers_street_address, customers_suburb, customers_city, customers_postcode, customers_state, customers_country, customers_telephone, customers_email_address, customers_address_format_id, delivery_name, delivery_company, delivery_street_address, delivery_suburb, delivery_city, delivery_postcode, delivery_state, delivery_country, delivery_address_format_id, billing_name, billing_company, billing_street_address, billing_suburb, billing_city, billing_postcode, billing_state, billing_country, billing_address_format_id, payment_method, cc_type, cc_owner, cc_number, cc_expires, currency, currency_value, date_purchased, orders_status, last_modified from " . TABLE_ORDERS . " where orders_id = '" . (int)$order_id . "'");


    $totals_query = $db->Execute("select title, text from " . TABLE_ORDERS_TOTAL . " where orders_id = '" . (int)$order_id . "' order by sort_order");
    while (!$totals_query->EOF) {
        $toReturn->totals[] = array('title' => $totals_query->fields['title'],
            'text' => $totals_query->fields['text']);
        $totals_query->MoveNext();
    }

    $order_total = $db->Execute("select text from " . TABLE_ORDERS_TOTAL . " where orders_id = '" . (int)$order_id . "' and class = 'ot_total'");

    $shipping_method = $db->Execute("select title from " . TABLE_ORDERS_TOTAL . " where orders_id = '" . (int)$order_id . "' and class = 'ot_shipping'");

    $order_status = $db->Execute("select orders_status_name from " . TABLE_ORDERS_STATUS . " where orders_status_id = '" . $order['orders_status'] . "' and language_id = '" . (int)$languages_id . "'");

    $toReturn->info = array('currency' => $order->fields['currency'],
        'currency_value' => $order->fields['currency_value'],
        'payment_method' => $order->fields['payment_method'],
        'cc_type' => $order->fields['cc_type'],
        'cc_owner' => $order->fields['cc_owner'],
        'cc_number' => $order->fields['cc_number'],
        'cc_expires' => $order->fields['cc_expires'],
        'date_purchased' => $order->fields['date_purchased'],
        'orders_status' => $order_status->fields['orders_status_name'],
        'last_modified' => $order->fields['last_modified'],
        'total' => strip_tags($order_total->fields['text']),
        'shipping_method' => ((substr($shipping_method->fields['title'], -1) == ':') ? substr(strip_tags($shipping_method->fields['title']), 0, -1) : strip_tags($shipping_method->fields['title'])));

    $toReturn->customer = array('id' => $order->fields['customers_id'],
        'name' => $order->fields['customers_name'],
        'company' => $order->fields['customers_company'],
        'street_address' => $order->fields['customers_street_address'],
        'suburb' => $order->fields['customers_suburb'],
        'city' => $order->fields['customers_city'],
        'postcode' => $order->fields['customers_postcode'],
        'state' => $order->fields['customers_state'],
        'country' => array('title' => $order->fields['customers_country']),
        'format_id' => $order->fields['customers_address_format_id'],
        'telephone' => $order->fields['customers_telephone'],
        'email_address' => $order->fields['customers_email_address']);

    $toReturn->delivery = array('name' => trim($order->fields['delivery_name']),
        'company' => $order->fields['delivery_company'],
        'street_address' => $order->fields['delivery_street_address'],
        'suburb' => $order->fields['delivery_suburb'],
        'city' => $order->fields['delivery_city'],
        'postcode' => $order->fields['delivery_postcode'],
        'state' => $order->fields['delivery_state'],
        'country' => array('title' => $order->fields['delivery_country']),
        'format_id' => $order->fields['delivery_address_format_id']);

    if (empty($toReturn->delivery['name']) && empty($toReturn->delivery['street_address'])) {
        $toReturn->delivery = false;
    }

    $toReturn->billing = array('name' => $order->fields['billing_name'],
        'company' => $order->fields['billing_company'],
        'street_address' => $order->fields['billing_street_address'],
        'suburb' => $order->fields['billing_suburb'],
        'city' => $order->fields['billing_city'],
        'postcode' => $order->fields['billing_postcode'],
        'state' => $order->fields['billing_state'],
        'country' => array('title' => $order->fields['billing_country']),
        'format_id' => $order->fields['billing_address_format_id']);
    return $toReturn;
}

function getCountryIsoFromName($name){
    global $db;
    $country = $db->Execute("select * from " . TABLE_COUNTRIES . " where countries_name = '" . $name . "'");

    return $country->fields['countries_iso_code_2'];
}

function getTotal($id){
    global $db;
    $total = $db->Execute("select * from " . TABLE_ORDERS_TOTAL . " where class = 'ot_total'  AND orders_id = '" . $id . "'");
    return $total->fields['value'];
}
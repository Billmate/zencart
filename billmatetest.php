<?php

require ( dirname(__FILE__).'includes/classes/billmate/billmate_api.php');
require( dirname(__FILE__). 'includes/classes/billmate/billmateutils.php');

if(empty($_GET['test'])) {
	$form_action_url = 'https://cardpay.billmate.se/pay/test';
} else {
	$form_action_url = 'https://cardpay.billmate.se/pay';
}

$bill_address = $ship_address = array(
	"email" => "vipan.eminence@gmail.com",
    "telno" => "0760123456",
    "cellno" => "0760123456",
    "fname" => "test kumar",
    "lname" => "Lastname",
    "company" => "",
    "street" => "Streetname no",
    "zip" => "ZipCode",
    "city" => "City",
    "country" => "Denmark",
);

$goodsList = array(
	array(
		"goods" => array
            (
                artno => "VGN-TXN27N/B",
                title => 'Sony VAItestå,ä,ötestå,ä,öO VGN-TXN27N/B 11.1" Notebook PC',
                price => 337499,
                vat => 25,
                discount => 0,
                flags => 0
            ),
        "qty" => 1
	),
	array(
		"goods" => array
	        (
	            artno => "flatrate_flatrate",
	            title => 'Frakt - Fixed',
	            price => 850,
	            vat => 25,
	            discount => 0,
	            flags => 8
	        ),
	    "qty" => 1
	),
	array(
		"goods" => array
	        (
	            artno => "invoice_fee",
	            title => 'Faktureringsavgift',
	            price => 2000,
	            vat => 25,
	            discount => 0,
	            flags => 16
	        ),
	    "qty" => 1
	)
	
);
$post = array
(
    'status' => 0
    'order_id' => 1380526749
    'currency' => 'SEK'
    'mac' => 'aab629195d4a98ce10bcd84669f9aaa307c7b4a4b2594018b63f4835ed3ed097'
    'approval_code' => '2345'
    'merchant_id' => '7270'
    'exp_year' => '15'
    'exp_mon' => '02'
    'error_message' => 'Approved'
    'amount' => 5625
    'time' => '2013-09-30 09:39:28.371433'
    'test' => 'YES'
    'pay_method' => 'visa'
    'trans_id' => '800505955'
    'card_no' => '444433......1111'
));
$transaction = array(
	"order1"=>"O12345",
	"order2"=>"654321",
	"comment"=>"Comment text",
	"flags"=>0,
	"reference"=>"",
	"reference_code"=>"",
	"currency"=>0,
	"country"=>209,
	"language"=>138,
	"pclass"=>-1,
	"shipInfo"=>array("delay_adjust"=>"1"),
	"travelInfo"=>array(),
	"incomeInfo"=>array(),
	"bankInfo"=>array(),
	"sid"=>array("time"=>microtime(true)),
	"extraInfo"=>array(array("cust_no"=>(string)time(),"creditcard_data"=>$_POST['']))
);

$k = new BillMateAPI($eid,$secret,$ssl,$debug);
$result1 = $k->AddInvoice('',$ship_address,$bill_address,$goodsList,$transaction);
var_dump($result1);

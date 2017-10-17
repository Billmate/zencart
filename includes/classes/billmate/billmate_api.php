<?php
global $BILL_ISO3166_SE,$BILL_ISO639_SE, $BILL_SEK, $BILL_NOK, $BILL_EUR, $BILL_DKK;
$BILL_ISO3166_SE = 209; //	 SWEDEN

$BILL_ISO639_SE = 125;    // Northern Sami
$BILL_SEK = 0;
$BILL_NOK = 1;
$BILL_EUR = 2;
$BILL_DKK = 3;
define('PLUGIN_VERSION', '1.6.1');
define('BILLMATE_VERSION',  "PHP:ZenCart:".PLUGIN_VERSION );

require_once(dirname( __FILE__ )."/lib/xmlrpc.inc");
require_once(dirname( __FILE__ )."/lib/xmlrpcs.inc");
require_once dirname(__FILE__).'/Billmate.php';

function mk_goods_flags($qty, $artno, $title, $price, $vat, $discount, $flags){
	our_settype_integer($qty);
	our_settype_integer($flags);
	our_settype_string($artno);
	our_settype_string($title);
	our_settype_integer($price);
	settype($vat, "double");
	settype($discount, "double");
	return array("goods" => array("artno" => $artno,
	"title" => $title,
	"price" => $price,
	"vat" => $vat,
	"discount" => $discount,
	"flags" => $flags),
	"qty" => $qty);
}



// new function
function total_credit_purchase_cost($sum, $pc, $cur, &$result)
{
	global $BILL_SEK, $BILL_NOK, $BILL_EUR, $BILL_DKK,$BILL_DIVISOR_PCLASS, $BILL_ANNUITY_PCLASS;
	if (($cur == $BILL_NOK) && (get_type($pc) == $BILL_DIVISOR_PCLASS))
	$months = 12;
	else
	$months = get_months($pc);


	$monthfee = get_month_fee($pc);
	$startfee = get_start_fee($pc);
	$rate = get_rate($pc);

	$result = $months * total_monthly_cost($sum, $months, $monthfee, $rate, $startfee, $cur);
}

/* Calculates the total monthly cost (annuity payments) i.e the cost per
' month including all fees, the purchase sum and interest, taking account:
'- that the startfee is paid at the first payment (with no interest) and
' - the monthly fee is paid with every payment before any amortizing is done.
' This cost should be as close as possible as the real cost will be.*/
function total_monthly_cost($sum, $months, $monthfee, $rate, $startfee, $cur)
{
	$sum_j_to_N = 0;
	// reference: Avbetalningsplaner.pdf
	$K0 = $sum;
	$A = $monthfee;
	$S = $startfee;
	$R = (pow(1.0+$rate/10000, 1.0/12.0));
	$N = $months;
	if ($rate == 0)
	$T = (1 / $N)*($K0 + ($N * $A) + $S);
	else
	{
		// the total cost of fees plus the cost of paying off the monthly fee first and after that amortize
		for ($j = 2; $j < $N+1; $j++)
		{
			$sum_j_to_N = $sum_j_to_N + pow($R, $N - $j) * $A;
		}
		$T = (($R - 1) / (pow($R, $N) - 1)) * (pow($R, $N) * $K0 + $sum_j_to_N + pow($R, $N - 1) * ($A + $S));
	}

	return round($T, 0);
}


/*
* API: periodic_cost
*/
function periodic_cost($eid, $sum, $pclass, $currency, $flags, $secret,
&$result){
	$months     = get_months($pclass);
	$monthfee   = get_month_fee($pclass);
	$startfee   = get_start_fee($pclass);
	$rate       = get_rate($pclass);
	$result = periodic_cost2($sum, $months, $monthfee, $rate, $startfee);
	return 0;
}

function periodic_cost2($sum, $months, $monthfee, $rate, $startfee){
	$dailyrate  = daily_rate($rate);
	$monthpayment = calc_monthpayment($sum + $startfee, $dailyrate, $months);
	return round($monthpayment + $monthfee);
}


function calc_monthpayment($sum, $dailyrate, $months){
	$dates      = 0;
	$totdates   = (($months - 1) * 30);
	$denom      = calc_denom($dailyrate, $totdates);
	$totdates   = $totdates + 60;
	return ((pow($dailyrate, $totdates) * $sum) / $denom);
}

function calc_denom($dailyrate, $totdates){
	$sum = 1;
	$startdates = 0;
	while ($totdates > $startdates){
		$startdates = $startdates + 30;
		$sum = ($sum + pow($dailyrate, $startdates));
	}
	return $sum;
}

function daily_rate($rate){
	return pow((($rate / 10000) + 1), (1 / 365.25));
}


function monthly_cost($sum, $rate, $months, $monthsfee, $flags, $currency, &$result)
{
	global $BILL_ISO3166_SE, $BILL_ISO3166_NO, $BILL_ISO3166_DK, $BILL_ISO3166_FI, $BILL_ISO3166_DE, $BILL_ISO3166_NL, $BILL_EUR, $BILL_NOK, $BILL_SEK, $BILL_DKK;
	if($rate < 100)
	$rate = $rate*100;

	switch ($country) {
		case $BILL_SEK:
		case $BILL_ISO3166_SE:
		$lowest_monthly_payment = 5000.00;
		$currency = $BILL_SEK;
		break;
		case $BILL_NOK:
		case $BILL_ISO3166_NO:
		$lowest_monthly_payment = 9500.00;
		$currency = $BILL_NOK;
		break;
		case $BILL_EUR: //DE, NL needs to use country code!
		case $BILL_ISO3166_FI:
		$lowest_monthly_payment = 895.00;
		$currency = $BILL_EUR;
		break;
		case $BILL_DKK:
		case $BILL_ISO3166_DK:
		$lowest_monthly_payment = 8900.00;
		$currency = $BILL_DKK;
		break;
		case $BILL_ISO3166_DE:
		$lowest_monthly_payment = 695.00;
		$currency = $BILL_EUR;
		break;
		case $BILL_ISO3166_NL:
		$lowest_monthly_payment = 500.00;
		$currency = $BILL_EUR;
		break;
		default:
		return -2;
	}

	$average_interest_period = 45;
	$calcRate = ($rate / 10000);

	$interest_value = ($average_interest_period / 365.0) * $calcRate * $sum;
	$periodic_cost =  ($sum + $interest_value)/$months;

	if ($flags == 1)
	{
		$result = round_up($periodic_cost, $currency);
	}
	else if ($flags == 0)
	{
		$periodic_cost = $periodic_cost + $monthsfee;
		if ($periodic_cost < $lowest_monthly_payment)
		$result = round($lowest_monthly_payment, 0);
		else
		$result = round_up($periodic_cost, $currency);
	}
	else
	return -2;

	return 0;
}

/*************************************************************************
* API: monthly_cost_new
*************************************************************************/
function calc_monthly_cost($sum, $pclass,
$flags, $currency,
&$result){
	$months;
	$monthsfee;
	$type;
	$rate;
	$startfee;


	$months = get_months($pclass);
	$monthsfee = get_month_fee($pclass);
	$type = get_type($pclass);
	$rate = get_rate($pclass);
	$startfee = get_start_fee($pclass);

	return monthly_cost_new2($sum, $rate, $months, $monthsfee, $startfee, $flags,
	$currency, $type, $result);
}

/*************************************************************************
* API: monthly_cost_new2
*************************************************************************/
function monthly_cost_new2($sum, $rate, $months,
$monthsfee, $startfee,
$flags, $currency,
$type, &$result){
	global $BILL_ANNUITY_PCLASS, $BILL_DIVISOR_PCLASS;
	if($type == $BILL_ANNUITY_PCLASS){
		return periodic_cost_new($sum, $months, $monthsfee, $rate, $startfee,
		$currency, $flags, $result);
	}else if($type == $BILL_DIVISOR_PCLASS){
		return monthly_cost($sum, $rate, $months, $monthsfee, $flags, $currency,
		$result);
	}

	return -1;
}

/*************************************************************************
* API: periodic_cost
*************************************************************************/
function periodic_cost_new($sum, $months, $monthfee,
$rate, $startfee, $currency, $flags, &$result){

	global $BILL_ACTUAL_COST, $BILL_LIMIT_COST;
	$mountscost = 0;
	$dailyrate = daily_rate($rate);
	$monthpayment = calc_monthpayment($sum+$startfee, $dailyrate, $months);
	if($flags == $BILL_ACTUAL_COST){
		$mountscost = $monthpayment + 0.5;
	}else if($flags == $BILL_LIMIT_COST){
		$mountscost = $monthpayment + $monthfee + 0.5;
	}else{
		$result = -2;
	}
	$result = (int)$mountscost;
}

function round_up($value, $curr) //value, currency
{
	$result;
	$divisor;

	if ($curr == 2)
	$divisor = 10;
	else
	$divisor = 100;

	$result = $divisor * round((($divisor/2)+$value)/$divisor); //We want to roundup to closest integer.

	return $result;
}


function fetch_pclasses($eid, $cur, $secret, $country, $language, &$result){
	our_settype_integer($eid);
	our_settype_integer($cur);
	our_settype_integer($country);
	our_settype_integer($language);
	$ssl = true;
	$debug = false;
	$k = new BillMate($eid,$secret,$ssl,$debug);
	$additionalinfo = array(
	"currency"=>$cur,
	"country"=>$country,
	"language"=>$language,
	);
	return $result = $k->FetchCampaigns($additionalinfo);
}

function our_settype_integer(&$x) {
	if (is_double($x)) {
		$x = round($x)+(($x>0)?0.00000001:-0.00000001);
	} else if (is_float($x)) {
		$x = round($x)+(($x>0)?0.00000001:-0.00000001);
	} else if (is_string($x)) {
		$x = preg_replace("/[ \n\r\t\e]/", "", $x);
	}

	settype($x, "integer");
}

function our_strip_string(&$x) {
	$x = str_replace(array(" ","-"), "", $x);
}

function our_settype_string(&$x){
	$res = "";
	$length = strlen($x);
	for($i = 0; $i < $length; $i++){
		if($x[$i] >= " "){
			$res .= $x[$i];
		}else{
			$res .= " ";
		}
	}
	$x = $res;
}

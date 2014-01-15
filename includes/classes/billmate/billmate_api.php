<?php
global $KRED_ISO3166_SE,$KRED_ISO639_SE, $KRED_SEK, $KRED_NOK, $KRED_EUR, $KRED_DKK;
$KRED_ISO3166_SE = 209; //	 SWEDEN

$KRED_ISO639_SE = 125;    // Northern Sami
$KRED_SEK = 0;
$KRED_NOK = 1;
$KRED_EUR = 2;
$KRED_DKK = 3;

require_once(dirname( __FILE__ )."/lib/xmlrpc.inc");
require_once(dirname( __FILE__ )."/lib/xmlrpcs.inc");
require_once dirname(__FILE__).'/BillMate.php';

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
	global $KRED_SEK, $KRED_NOK, $KRED_EUR, $KRED_DKK,$KRED_DIVISOR_PCLASS, $KRED_ANNUITY_PCLASS;
	if (($cur == $KRED_NOK) && (get_type($pc) == $KRED_DIVISOR_PCLASS))
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
	global $KRED_ISO3166_SE, $KRED_ISO3166_NO, $KRED_ISO3166_DK, $KRED_ISO3166_FI, $KRED_ISO3166_DE, $KRED_ISO3166_NL, $KRED_EUR, $KRED_NOK, $KRED_SEK, $KRED_DKK;
	if($rate < 100)
	$rate = $rate*100;

	switch ($country) {
		case $KRED_SEK:
		case $KRED_ISO3166_SE:
		$lowest_monthly_payment = 5000.00;
		$currency = $KRED_SEK;
		break;
		case $KRED_NOK:
		case $KRED_ISO3166_NO:
		$lowest_monthly_payment = 9500.00;
		$currency = $KRED_NOK;
		break;
		case $KRED_EUR: //DE, NL needs to use country code!
		case $KRED_ISO3166_FI:
		$lowest_monthly_payment = 895.00;
		$currency = $KRED_EUR;
		break;
		case $KRED_DKK:
		case $KRED_ISO3166_DK:
		$lowest_monthly_payment = 8900.00;
		$currency = $KRED_DKK;
		break;
		case $KRED_ISO3166_DE:
		$lowest_monthly_payment = 695.00;
		$currency = $KRED_EUR;
		break;
		case $KRED_ISO3166_NL:
		$lowest_monthly_payment = 500.00;
		$currency = $KRED_EUR;
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
	global $KRED_ANNUITY_PCLASS, $KRED_DIVISOR_PCLASS;
	if($type == $KRED_ANNUITY_PCLASS){
		return periodic_cost_new($sum, $months, $monthsfee, $rate, $startfee,
		$currency, $flags, $result);
	}else if($type == $KRED_DIVISOR_PCLASS){
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

	global $KRED_ACTUAL_COST, $KRED_LIMIT_COST;
	$mountscost = 0;
	$dailyrate = daily_rate($rate);
	$monthpayment = calc_monthpayment($sum+$startfee, $dailyrate, $months);
	if($flags == $KRED_ACTUAL_COST){
		$mountscost = $monthpayment + 0.5;
	}else if($flags == $KRED_LIMIT_COST){
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
	$k = new BillMateAPI($eid,$secret,$ssl,$debug);
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

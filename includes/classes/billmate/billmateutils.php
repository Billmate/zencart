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

require_once dirname(__FILE__) . '/Billmate.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'commonfunctions.php';

require_once(DIR_FS_CATALOG . DIR_WS_CLASSES . 'billmate/billmatecalc.php');

function mk_goods_flags($qty, $artno, $title, $price, $vat, $discount, $includeTax = false){

	$price_without_tax = $price * $qty;

    $goods_tax = ($price_without_tax / 100) * $vat;

	
	return array(	"artnr" => $artno,
					"title" => $title,
					"quantity" => $qty,
					"aprice" => round($price),
					"taxrate" => $vat,
					"discount" => $discount,
					"withouttax" => round($price_without_tax),
					"tax" => round($goods_tax),
				);

}
function billmate_remove_order($order_id, $restock = false) {
    global $db;
    if ($restock == 'on') {
        
        $order = $db->Execute("select products_id, products_quantity from " . TABLE_ORDERS_PRODUCTS . " where orders_id = '" . (int)$order_id . "'");
        while (!$order->EOF) {
            $db->Execute("update " . TABLE_PRODUCTS . " set products_quantity = products_quantity + " . $order->fields['products_quantity'] . ", products_ordered = products_ordered - " . $order->fields['products_quantity'] . " where products_id = '" . (int)$order->fields['products_id'] . "'");
            $order->MoveNext();
        }
    }

    $db->Execute("delete from " . TABLE_ORDERS . " where orders_id = '" . (int)$order_id . "'");
    $db->Execute("delete from " . TABLE_ORDERS_PRODUCTS . " where orders_id = '" . (int)$order_id . "'");
    $db->Execute("delete from " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . " where orders_id = '" . (int)$order_id . "'");
    $db->Execute("delete from " . TABLE_ORDERS_STATUS_HISTORY . " where orders_id = '" . (int)$order_id . "'");
    $db->Execute("delete from " . TABLE_ORDERS_TOTAL . " where orders_id = '" . (int)$order_id . "'");
}

function prepare_sql_array($arr){
    $output = array();
    $i = 0;
    foreach ($arr as $key => $value){
        $output[$i] = array('fieldName' => $key, 'value' => $value);
        $i++;
    }
    return $output;
}
/**
 *
 */
class BillmateUtils {



    public static function get_display_jQuery($code)
    {
        return "<script type=\"text/javascript\" src='".HTTP_SERVER."/billmatepopup.js'></script>";
    }

    /**
     *
     */
    /*
    public static function get_display_jQuery($code) {
        return "<script type=\"text/javascript\" src='".HTTP_SERVER."/billmatepopup.js'></script><script type='text/javascript'>
                if(typeof jQuery != 'undefined')
                jQuery(document).ready(function() {
                    var input = jQuery('input[value=\"".$code."\"][name=\"payment\"]');
                    var elem = input.parent().parent().next().children().children().children().children().next();
                    elem.attr('hidden', 'true');
                    elem.hide();

                    var showFunc = function() {
                        var input = jQuery('input[value=\"".$code."\"][name=\"payment\"]');
                        jQuery('input[name=\"payment\"]:checked').attr('checked', false);
                        $.each(document.checkout_payment.payment,function(index,val){
                            if($(val).val() == \"".$code."\"){
                                document.checkout_payment.payment[index].checked = true;
                            }
                        });
                        //input.attr('checked', 'checked');
                        var element = input.parent().parent().next().children().children().children().children().next();
                        console.log(element.attr('hidden'));
                        console.log(element);
                        if(element.attr('hidden') == 'hidden') {
                            element.fadeIn();
                            element.attr('hidden', 'false');
                            var checkelem = jQuery('input[name=\"".$code."_invoice_type\"]:checked');
                            var checkid = checkelem.attr('id');
                            if(!checkelem.attr('hidden') && input.attr('value').substr(0, 6) == 'billmate') {
                                if(checkid == 'company') {
                                    toggle('company');
                                }
                                else {
                                    toggle('private');
                                }
                            }
                        }
                        return true;
                    };

                    input.parent().parent().click(showFunc);
                    input.change(showFunc);
                    var inputs = jQuery('input[name=\"payment\"]').not(input);

                    var hideFunc = function() {
                        var input = jQuery('input[value=\"".$code."\"][name=\"payment\"]');
                        input.attr('checked', false);
                        var element = input.parent().parent().next().children().children().children().children().next();
                        if(element.attr('hidden') != 'true') {
                            element.hide();
                            element.attr('hidden', 'true');
                        }
                    }

                    inputs.parent().parent().click(hideFunc);
                    inputs.change(hideFunc);
                });
            </script>";
    }
    */

    /**
     * @param  array  $pclasses
     * @return array
     */
    public static function get_cheapest_pclass($pclasses,$total) {
        $lowest = false;
        $lowest_pp;
        foreach($pclasses as $pclass) {
            if($pclass['type'] < 2 && $total >= $pclass['minamount'] &&( $total <= $pclass['maxamount'] || $pclass['maxamount'] == 0)) {

                if($pclass['minpay'] < $lowest_pp || !isset($lowest_pp)) {

	                if($pclass['minpay'] >= BillmateCalc::get_lowest_payment_for_account($pclass['country']))
	                {
		                $lowest_pp = $pclass['minpay'];
		                $lowest    = $pclass;
	                }
                }
            }
        }

        return $lowest;
    }


    /**
     * flags = 0 => checkout.
     * flags = 1 => product page.
     *
     * @param  float  $total
     * @param  string $table
     * @param  int    $country
     * @param  int    $flags
     * @return array
     */
    public static function calc_monthly_cost($total, $table, $country, $flags, $language, $month_text) {
        global $KRED_ISO3166_NO, $currencies;

        $lowest_pp = false;
        $listarray = array();
        $special = array();

        $pclasses = self::get_pclasses($table, $country,$language);
        foreach($pclasses as &$pclass) {
			$pclass['description'] = utf8_decode($pclass['description']);

            if($total >= ($pclass['minamount']) && ($total <= ($pclass['maxamount']) || $pclass['maxamount'] == 0 ) ) {
                if($pclass['type'] < 2) {
                    $pclass['minpay'] = ceil(BillmateCalc::calc_monthly_cost($total, $pclass, $flags));

                    $pclass['description'] = htmlentities($pclass['description']);
                    $pclass['text'] = $pclass['description']." - ".$currencies->format($pclass['minpay'], false).'/'.$month_text;

                    //Norway only
                    if($country === $KRED_ISO3166_NO) {
                        $pclass['tcpc'] = BillmateCalc::total_credit_purchase_cost($total, $pclass['interestrate'], $pclass['handlingfee'], $pclass['minpay'], $pclass['nbrofmonths'], $pclass['startfee'], $pclass['type']);
                        $pclass['text'] .= " ".BILLMATE_LANG_NO_PAYMENTTEXT2_EACH." (* ".$currencies->format(ceil($pclass['tcpc']), false).")";
                    }

                    $listarray[] = $pclass;
                }
                else {
                    $pclass['text'] = htmlentities($pclass['description']);
                    $special[] = $pclass;
                }
            }
        }

        $listarray = array_merge($listarray, $special);


        if(!function_exists('pck_cmp')) { //this if-case is needed, otherwise it will break.
            function pck_cmp($a, $b) {
                if(!isset($a['text']) && !isset($b['text']))
                    return 0;
                else if(!isset($a['text']))
                    return 1;
                else if(!isset($b['text']))
                    return -1;

                return strnatcmp($a['text'], $b['text'])*-1;
            }
        }

        /*if(count($listarray) > 0) {
            usort($listarray, "pck_cmp");
        }*/

        return $listarray;
    }

    /**
     * Displays the pclasses as a HTML table.
     *
     * @param  string $table
     * @param  int    $country
     * @return void
     */
    public static function display_pclasses($table, $country) {

        $pclasses = self::get_pclasses($table, $country,false);

		if( sizeof($pclasses) == 0 ) return false;
		?>
		<tr><td valign="top" colspan="3">
		<table border="0" cellspacing="0" cellpadding="2" width="100%">
			<tr class=""><th colspan="8" class="pageHeading" style="text-align:left"><?php echo MODULE_PAYMENT_PCBILLMATE_TEXT_TITLE ?> - Pclasses / Campaigns:</th></tr>
			<tr class="dataTableHeadingRow">
				<th class="dataTableHeadingContent">ID</th>
				<th class="dataTableHeadingContent">Desc</th>
				<th class="dataTableHeadingContent">Months</th>
				<th class="dataTableHeadingContent">Interest Rate</th>
				<th class="dataTableHeadingContent">Handling Fee</th>
				<th class="dataTableHeadingContent">Start Fee</th>
				<th class="dataTableHeadingContent">Min Amount</th>
				<th class="dataTableHeadingContent">Max Amount</th>                
				<th class="dataTableHeadingContent">Country</th>
				<th class="dataTableHeadingContent">Expiry</th>
			</tr>
		<?php
        foreach($pclasses as $pclass) {
		
            if(strtolower(CHARSET) == 'utf-8') {
                $desc = self::forceUTF8($pclass['description']);
            }
            else {
                $desc = self::forceLatin1($pclass['description']);
            }

			echo '<tr class="dataTableRow"><td class="dataTableContent" align="center">', $pclass['id'],'</td>';
			echo '<td class="dataTableContent">', $desc,'</td>';
			echo '<td class="dataTableContent" align="center">', $pclass['nbrofmonths'],'</td>';
			echo '<td class="dataTableContent" align="center">', $pclass['interestrate'],'</td>';
			echo '<td class="dataTableContent" align="center">', $pclass['handlingfee'],'</td>';
			echo '<td class="dataTableContent" align="center">', $pclass['startfee'],'</td>';
			echo '<td class="dataTableContent" align="center">', $pclass['minamount'],'</td>';
			echo '<td class="dataTableContent" align="center">', $pclass['maxamount'],'</td>';			
			echo '<td class="dataTableContent" align="center">', ($pclass['country'] == 209 ? 'SWEDEN' : $pclass['country']),'</td>';
			echo '<td class="dataTableContent" align="center">', $pclass['expirydate'],'</td></tr>';

            /*printf(" %-6s,");
            printf(" %-13s,");
            printf(" %-12s,");
            printf(" %-9s,");
            printf(" %-10s,");
            printf(" %-7s",  );
            echo "<br />";*/
        }
		echo '</table></td></tr>';
    }

    /**
     *
     * @param  string $table
     * @param  int    $country
     * @return array
     */
    public static function get_pclasses($table, $country,$language = false) {
        global $db;
        if(strlen(trim($table)) > 0) {
            self::create_db($table); //incase it doesn't exist, below will not cause an error.
            if($language)
                $query = $db->Execute("SELECT * FROM `".$table."` WHERE `language` ='".strtolower($language)."'");
            else
                $query = $db->Execute("SELECT * FROM `".$table."`");
            $tmp = array();
            while(!$query->EOF) {
                $tmp[] = $query->fields;
                $query->MoveNext();
            }
            return $tmp;
        }
        return array();
    }

    /**
     *
     * @param string $table
     */
    public static function create_db($table) {
        global $db;
		$db->Execute("CREATE TABLE IF NOT EXISTS `".$table."` (
		  `eid` int(10) unsigned NOT NULL,
		  `id` int(10) NOT NULL,
		  `description` varchar(250) NOT NULL,
		  `nbrofmonths` int(2) NOT NULL,
		  `startfee` decimal(10,2) NOT NULL,
		  `handlingfee` decimal(10,2) NOT NULL,
		  `minamount` decimal(10,2) NOT NULL,
		  `maxamount` decimal(15,2) NOT NULL,
		  `country` varchar(5) NOT NULL,
		  `type` int(10) NOT NULL,
		  `expirydate` date NOT NULL,
		  `interestrate` decimal(10,2) NOT NULL,
		  `currency` varchar(5) NOT NULL,
		  `language` varchar(5) NOT NULL,
		  `activated` int(10) NOT NULL,
		  UNIQUE KEY `id` (`id`)
		)");

        /* If module is updated and is missing the column language */
        $table_columns = array();
        $row = $db->Execute("select column_name from information_schema.columns where table_name='".$table."'");
        while (!$row->EOF) {
            $table_columns[] = $row->fields['column_name'];
            $row->MoveNext();
        }
        if(!in_array("language", $table_columns)) {
            /* Language column is missing, add language column */
            self::remove_db($table);
            self::create_db($table);
        }
    }

    /**
     *
     * @param string $table
     */
    public static function remove_db($table) {
        global $db;
        $db->Execute("DROP TABLE IF EXISTS `".$table."`");
    }

    /**
     *
     * @param string $table
     * @param array  $pclasses
     */
    public static function update_pclasses($table, $pclasses,$language) {
        global $db;
        if(strlen(trim($table)) > 0) {

            self::create_db($table);
            //Create table, will not do anything if it exists.
            

            $db->Execute("DELETE IGNORE FROM `".$table."` WHERE `language` = '".$language."'");

			$eid = $pclasses['eid'];
            foreach((array)$pclasses as $key=>$pclass) {
				if( is_array($pclass) ) :
					//Delete existing pclass
					$pclass_id = $key+1;
					$pclass['startfee'] /= 100; $pclass['handlingfee'] /= 100; $pclass['minamount'] /= 100; $pclass['maxamount'] /= 100;

					//Insert new pclass (replace into only exists for MySQL...)
					$db->Execute("INSERT INTO `".$table."` (`eid`, `id`, `description`, `nbrofmonths`, `startfee`, `handlingfee`, `minamount`, `maxamount`, `country`, 
								  `type`, `expirydate`, `interestrate`, `currency`, `language`, `activated`) VALUES ('".$eid."', '".$pclass['paymentplanid']."','".$pclass['description']."',
								  '".$pclass['nbrofmonths']."', '".$pclass['startfee']."', '".$pclass['handlingfee']."', '".$pclass['minamount']."', '".$pclass['maxamount']."', 
								  '".$pclass['country']."', '".$pclass['type']."', '".$pclass['expirydate']."', '".$pclass['interestrate']."', '".$pclass['currency']."', '".$pclass['language']."', '')");
				endif;
            }
        }
    }


    /**
     * Creates a SEO safe error link.
     *
     * @param string $page
     * @param string $parameters
     * @param string $connection
     * @param bool   $add_session_id
     * @param bool   $search_engine_safe
     * @return string
     */
    static function error_link($page = '', $parameters = '', $connection = 'NONSSL', $add_session_id = true, $search_engine_safe = true) {
        $request_type = $_SESSION['request_type']; 
        $session_started = $_SESSION['session_started']; 
        $SID = $_SESSION['SID'];

        if (!zen_not_null($page)) {
            die('<br><br><font color="#f3014d"><b>Error!</b></font><br><br><b>Unable to determine the page link!<br><br>');
        }

        if ($connection == 'NONSSL') {
            $link = HTTP_SERVER . DIR_WS_HTTP_CATALOG;
        }
        else if ($connection == 'SSL') {
            if (ENABLE_SSL == true) {
                $link = HTTPS_SERVER ;
            }
            else {
                $link = HTTP_SERVER ;
            }
        }
        else {
            die('<br><br><font color="#f3014d"><b>Error!</b></font><br><br><b>Unable to determine connection method on a link!<br><br>Known methods: NONSSL SSL</b><br><br>');
        }

        if (zen_not_null($parameters)) {
            $link .= $page . '?' . zen_output_string($parameters);
            $separator = '&';
        }
        else {
            $link .= $page;
            $separator = '?';
        }

        while ( (substr($link, -1) == '&') || (substr($link, -1) == '?') ) {
            $link = substr($link, 0, -1);
        }

        // Add the session ID when moving from different HTTP and HTTPS servers, or when SID is defined
        if ( ($add_session_id == true) && ($session_started == true) && (SESSION_FORCE_COOKIE_USE == 'False') ) {
            if (zen_not_null($SID)) {
                $_sid = $SID;
            }
            else if ( ( ($request_type == 'NONSSL') && ($connection == 'SSL') && (ENABLE_SSL == true) ) || ( ($request_type == 'SSL') && ($connection == 'NONSSL') ) ) {
                if (HTTP_COOKIE_DOMAIN != HTTPS_COOKIE_DOMAIN) {
                    $_sid = zen_session_name() . '=' . zen_session_id();
                }
            }
        }

        if ( (SEARCH_ENGINE_FRIENDLY_URLS == 'true') && ($search_engine_safe == true) ) {
            while (strstr($link, '&&')) {
                $link = str_replace('&&', '&', $link);
            }

            $link = str_replace('?', '/', $link);
            $link = str_replace('&', '/', $link);
            $link = str_replace('=', '/', $link);

            $separator = '?';
        }

        if (isset($_sid)) {
            $link .= $separator . $_sid;
        }

        return $link;
    }

    /**
     * Decodes html entities and fixes the scrambled characters (if existing)
     *
     * @param string $parameters
     * @return string
     */
    public static function error_params($parameters) {
        $charset = strtolower(CHARSET);
        $html_decoded = html_entity_decode($parameters);

        if($charset == 'iso-8859-1') {
            return urlencode($html_decoded);
        }

        $utf8_encoded = utf8_encode($html_decoded);
        $utf8_decoded = utf8_decode($html_decoded);

        if($charset == 'utf-8') {
            return urlencode($utf8_encoded);
        }

        $html_len = strlen($html_decoded);

        //Three characters are lost when it goes from a scrambled char to incorrect.
        //One character is added when the scrambled char is completed (two bytes).
        //Using this logic, the one closest (utf8_enc or dec) should be the one which is correct.

        return ( abs($html_len - strlen($utf8_encoded)) <= abs($html_len - strlen($utf8_decoded)) ) ? urlencode($utf8_encoded) : urlencode($utf8_decoded);
    }

    /**
     * Converts all $_POST values to iso-8859-1.
     *
     * @param mixed $value
     * @return string
     */
    public static function convertData($value) {
        return  self::forceLatin1($value);
    }

    /**
     * @author   "Sebasti�n Grignoli" <grignoli@framework2.com.ar>
     * @package  forceUTF8
     * @version  1.1
     * @link     http://www.framework2.com.ar/dzone/forceUTF8-es/
     * @example  http://www.framework2.com.ar/dzone/forceUTF8-es/
     */
    public static function forceUTF8($text) {
        /**
         * Function forceUTF8
         *
         * This function leaves UTF8 characters alone, while converting almost all non-UTF8 to UTF8.
         *
         * It may fail to convert characters to unicode if they fall into one of these scenarios:
         *
         * 1) when any of these characters:   ��������������������������������
         *    are followed by any of these:  ("group B")
         *                                    ����������������������?��������
         * For example:   %ABREPRESENT%C9%BB. �REPRESENTɻ
         * The "�" (%AB) character will be converted, but the "�" followed by "�" (%C9%BB)
         * is also a valid unicode character, and will be left unchanged.
         *
         * 2) when any of these: ����������������  are followed by TWO chars from group B,
         * 3) when any of these: ����  are followed by THREE chars from group B.
         *
         * @name forceUTF8
         * @param string $text  Any string.
         * @return string  The same string, UTF8 encoded
         *
         */

        if(is_array($text)) {
            foreach($text as $k => $v) {
                $text[$k] = self::forceUTF8($v);
            }
            return $text;
        }

        $max = strlen($text);
        $buf = "";
        for($i = 0; $i < $max; $i++) {
            $c1 = $text{$i};
            if($c1>="\xc0") { //Should be converted to UTF8, if it's not UTF8 already
                $c2 = $i+1 >= $max? "\x00" : $text{$i+1};
                $c3 = $i+2 >= $max? "\x00" : $text{$i+2};
                $c4 = $i+3 >= $max? "\x00" : $text{$i+3};
                if($c1 >= "\xc0" & $c1 <= "\xdf") { //looks like 2 bytes UTF8
                    if($c2 >= "\x80" && $c2 <= "\xbf") { //yeah, almost sure it's UTF8 already
                        $buf .= $c1 . $c2;
                        $i++;
                    } else { //not valid UTF8.  Convert it.
                        $cc1 = (chr(ord($c1) / 64) | "\xc0");
                        $cc2 = ($c1 & "\x3f") | "\x80";
                        $buf .= $cc1 . $cc2;
                    }
                } elseif($c1 >= "\xe0" & $c1 <= "\xef") { //looks like 3 bytes UTF8
                    if($c2 >= "\x80" && $c2 <= "\xbf" && $c3 >= "\x80" && $c3 <= "\xbf") { //yeah, almost sure it's UTF8 already
                        $buf .= $c1 . $c2 . $c3;
                        $i = $i + 2;
                    } else { //not valid UTF8.  Convert it.
                        $cc1 = (chr(ord($c1) / 64) | "\xc0");
                        $cc2 = ($c1 & "\x3f") | "\x80";
                        $buf .= $cc1 . $cc2;
                    }
                } elseif($c1 >= "\xf0" & $c1 <= "\xf7") { //looks like 4 bytes UTF8
                    if($c2 >= "\x80" && $c2 <= "\xbf" && $c3 >= "\x80" && $c3 <= "\xbf" && $c4 >= "\x80" && $c4 <= "\xbf") { //yeah, almost sure it's UTF8 already
                        $buf .= $c1 . $c2 . $c3;
                        $i = $i + 2;
                    } else { //not valid UTF8.  Convert it.
                        $cc1 = (chr(ord($c1) / 64) | "\xc0");
                        $cc2 = ($c1 & "\x3f") | "\x80";
                        $buf .= $cc1 . $cc2;
                    }
                } else { //doesn't look like UTF8, but should be converted
                    $cc1 = (chr(ord($c1) / 64) | "\xc0");
                    $cc2 = (($c1 & "\x3f") | "\x80");
                    $buf .= $cc1 . $cc2;
                }
            } elseif(($c1 & "\xc0") == "\x80") { // needs conversion
                $cc1 = (chr(ord($c1) / 64) | "\xc0");
                $cc2 = (($c1 & "\x3f") | "\x80");
                $buf .= $cc1 . $cc2;
            } else { // it doesn't need convesion
                $buf .= $c1;
            }
        }
        return $buf;
    }

    public static function forceLatin1($text) {
        if(is_array($text)) {
            foreach($text as $k => $v) {
                $text[$k] = self::forceLatin1($v);
            }
            return $text;
        }
        return utf8_decode(self::forceUTF8($text));
    }

    public static function fixUTF8($text) {
        if(is_array($text)) {
            foreach($text as $k => $v) {
                $text[$k] = self::fixUTF8($v);
            }
            return $text;
        }

        $last = "";
        while($last <> $text) {
            $last = $text;
            $text = self::forceUTF8(utf8_decode(self::forceUTF8($text)));
        }
        return $text;
    }

}

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

require_once(DIR_FS_CATALOG . DIR_WS_CLASSES . 'billmate/billmate_api.php');
require_once(DIR_FS_CATALOG . DIR_WS_CLASSES . 'billmate/billmatecalc.php');

/**
 *
 */
class BillmateUtils {

    /**
     *
     */
    public static function get_display_jQuery($code) {
        return "<script type='text/javascript'>
                if(typeof jQuery != 'undefined')
                jQuery(document).ready(function() {
                    var input = jQuery('input[value=\"".$code."\"][name=\"payment\"]');
                    var elem = input.parent().parent().next().children().children().children().children().next();
                    elem.attr('khidden', 'true');
                    elem.hide();

                    var showFunc = function() {
                        var input = jQuery('input[value=\"".$code."\"][name=\"payment\"]');
                        jQuery('input[name=\"payment\"]:checked').attr('checked', false);
                        input.attr('checked', 'checked');
                        var element = input.parent().parent().next().children().children().children().children().next();
                        if(element.attr('khidden') != 'false') {
                            element.fadeIn();
                            element.attr('khidden', 'false');
                            var checkelem = jQuery('input[name=\"".$code."_invoice_type\"]:checked');
                            var checkid = checkelem.attr('id');
                            if(!checkelem.attr('khidden') && input.attr('value').substr(0, 6) == 'billmate') {
                                if(checkid == 'company') {
                                    toggle('company');
                                }
                                else {
                                    toggle('private');
                                }
                            }
                        }
                    };

                    input.parent().parent().parent().click(showFunc);
                    input.change(showFunc);

                    var inputs = jQuery('input[name=\"payment\"]').not(input);

                    var hideFunc = function() {
                        var input = jQuery('input[value=\"".$code."\"][name=\"payment\"]');
                        input.attr('checked', false);
                        var element = input.parent().parent().next().children().children().children().children().next();
                        if(element.attr('khidden') != 'true') {
                            element.hide();
                            element.attr('khidden', 'true');
                        }
                    }

                    inputs.parent().parent().parent().click(hideFunc);
                    inputs.change(hideFunc);
                });
            </script>";
    }

    /**
     * @param  array  $pclasses
     * @return array
     */
    public static function get_cheapest_pclass($pclasses) {
        $lowest = false;
        $lowest_pp;
        foreach($pclasses as $pclass) {
            if($pclass['type'] < 2) {
                if($pclass['minpay'] < $lowest_pp || !isset($lowest_pp)) {
                    $lowest_pp = $pclass['minpay'];
                    $lowest = $pclass;
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
    public static function calc_monthly_cost($total, $table, $country, $flags) {
        global $BILL_ISO3166_NO, $currencies;

        $lowest_pp = false;
        $listarray = array();
        $special = array();

        $pclasses = self::get_pclasses($table, $country);
        foreach($pclasses as &$pclass) {
			$pclass['description'] = utf8_decode($pclass['description']);
            if($total >= ($pclass['minamount']/100)) {
                if($pclass['type'] < 2) {
                    $pclass['minpay'] = ceil(BillmateCalc::calc_monthly_cost($total, $pclass['months'], $pclass['fee']/100, $pclass['startfee']/100, $pclass['interest']/100, $pclass['type'], $flags, $country));

                    $pclass['description'] = htmlentities($pclass['description']);
                    $pclass['text'] = $pclass['description']." - ".$currencies->format($pclass['minpay'], false);

                    //Norway only
                    if($country === $BILL_ISO3166_NO) {
                        $pclass['tcpc'] = BillmateCalc::total_credit_purchase_cost($total, $pclass['interest']/100, $pclass['fee']/100, $pclass['minpay'], $pclass['months'], $pclass['startfee']/100, $pclass['type']);
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

        if(count($listarray) > 0) {
            usort($listarray, "pck_cmp");
        }

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

        $pclasses = self::get_pclasses($table, $country);
		if( sizeof( $pclasses) <=0  ) return false;
		?>
		<tr><td valign="top" colspan="5">
		<table border="0" cellspacing="0" cellpadding="2" width="100%">
			<tr class=""><th colspan="8" class="pageHeading" style="text-align:left"><?php echo MODULE_PAYMENT_PCBILLMATE_TEXT_TITLE ?> - Pclasses / Campaigns:</th></tr>
			<tr class="dataTableHeadingRow">
				<th class="dataTableHeadingContent">ID</th>
				<th class="dataTableHeadingContent">Description</th>
				<th class="dataTableHeadingContent">Months</th>
				<th class="dataTableHeadingContent">Interest Rate</th>
				<th class="dataTableHeadingContent">Handling Fee</th>
				<th class="dataTableHeadingContent">Start Fee</th>
				<th class="dataTableHeadingContent">Min Amount</th>
				<th class="dataTableHeadingContent">Country</th>
				<th class="dataTableHeadingContent">Expiry</th>
			</tr>
		<?php
        foreach($pclasses as $pclass) {
            if(strtolower(CHARSET) == 'utf-8') {
                $description = self::forceUTF8($pclass['description']);
            }
            else {
                $description = self::forceLatin1($pclass['description']);
            }

			echo '<tr class="dataTableRow"><td class="dataTableContent" align="center">', $pclass['id'],'</td>';
			echo '<td class="dataTableContent">', $description,'</td>';
			echo '<td class="dataTableContent" align="center">', $pclass['months'],'</td>';
			echo '<td class="dataTableContent" align="center">', $pclass['interest'],'</td>';
			echo '<td class="dataTableContent" align="center">', $pclass['fee'],'</td>';
			echo '<td class="dataTableContent" align="center">', $pclass['startfee'],'</td>';
			echo '<td class="dataTableContent" align="center">', $pclass['minamount'],'</td>';
			echo '<td class="dataTableContent" align="center">', ($pclass['country'] == 209 ? 'SWEDEN' : $pclass['country']),'</td>';
			echo '<td class="dataTableContent" align="center">', $pclass['expiry_date'],'</td></tr>';

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
    public static function get_pclasses($table, $country) {
		global $db;
        if(strlen(trim($table)) > 0) {
            self::create_db($table); //incase it doesn't exist, below will not cause an error.
            $query = mysql_query("SELECT * FROM `".$table."` WHERE `country` = '".$country."'");
            $tmp = array();
            while($row = mysql_fetch_assoc($query)) {
                $tmp[] = $row;
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
          `id` int(10) unsigned NOT NULL,
          `type` tinyint(4) NOT NULL,
          `description` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
          `months` int(11) NOT NULL,
          `interest` int(11) NOT NULL,
          `fee` int(11) NOT NULL,
          `startfee` int(11) NOT NULL,
          `minamount` int(11) NOT NULL,
          `country` int(11) NOT NULL,
		  `expiry_date` varchar(20) NOT NULL,
          KEY `id` (`id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");
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
    public static function update_pclasses($table, $pclasses) {
		global $db;
		
        if(strlen(trim($table)) > 0) {
            //Create table, will not do anything if it exists.
            BillmateUtils::create_db($table);

            foreach((array)$pclasses as $pclass) {
                $pclass_id = $pclass[0];
                $pclass_type = $pclass[8];
                $pclass_desc = utf8_encode($pclass[1]);
                $pclass_months = $pclass[2];
                $pclass_startfee = $pclass[3];
                $pclass_fee = $pclass[4];
                $pclass_interest = $pclass[5];
                $pclass_minamount = $pclass[6];
                $pclass_country = $pclass[7];
				$pclass_expiry = $pclass[9];

                //Delete existing pclass
                $db->Execute("DELETE FROM `".$table."` WHERE `id` = '".$pclass_id."'");
				
                //Insert new pclass (replace into only exists for MySQL...)
                $db->Execute("INSERT INTO `".$table."` (`id`, `type`, `description`, `months`, `interest`, `fee`, `startfee`, `minamount`, `country`,`expiry_date`) " .
                        "VALUES ('".$pclass_id."', '".$pclass_type."', '".$pclass_desc."', '".$pclass_months."', '".$pclass_interest."', ".
                        "'".$pclass_fee."', '".$pclass_startfee."', '".$pclass_minamount."', '".$pclass_country."','".$pclass_expiry."')");
            }
        }
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
     * @author   "Sebastián Grignoli" <grignoli@framework2.com.ar>
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
         * 1) when any of these characters:   ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖ×ØÙÚÛÜÝÞß
         *    are followed by any of these:  ("group B")
         *                                    ¡¢£¤¥¦§¨©ª«¬­®¯°±²³´µ¶?¸¹º»¼½¾¿
         * For example:   %ABREPRESENT%C9%BB. «REPRESENTÉ»
         * The "«" (%AB) character will be converted, but the "É" followed by "»" (%C9%BB)
         * is also a valid unicode character, and will be left unchanged.
         *
         * 2) when any of these: àáâãäåæçèéêëìíîï  are followed by TWO chars from group B,
         * 3) when any of these: ðñòó  are followed by THREE chars from group B.
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

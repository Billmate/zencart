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
class ot_billmate_fee {
    var $title, $output;

    function ot_billmate_fee() {
        $this->code = 'ot_billmate_fee';
        if(strpos($_SERVER['SCRIPT_FILENAME'],'admin')) {
            $this->title = "Billmate - Invoice fee";
        }
        else {
            $this->title = MODULE_BILLMATE_FEE_TITLE;
        }
        $this->description = MODULE_BILLMATE_FEE_DESCRIPTION;
        $this->enabled = MODULE_BILLMATE_FEE_STATUS;
        $this->sort_order = MODULE_BILLMATE_FEE_SORT_ORDER;
        $this->tax_class = MODULE_BILLMATE_FEE_TAX_CLASS;
        $this->output = array();
    }

    function process() {

        global $order, $ot_subtotal, $currencies, $db;

        $od_amount = $this->calculate_credit($this->get_order_total());
//die;
        //Disable module when $od_amount is <= 0
        if ($od_amount <= 0)
            $this->enabled = false;

        if ($od_amount != 0) {
            $tax_rate =zen_get_tax_rate(MODULE_BILLMATE_FEE_TAX_CLASS);

            $this->output[] = array('title' => $this->title . ':',
                    'text' => $currencies->format($od_amount),
                    'value' => $od_amount,
                    'tax_rate' => $tax_rate);
            $order->info['total'] = $order->info['total'] + $od_amount;
        }
    }


    function calculate_credit($amount) {
        global $order, $customer_id, $payment, $sendto, $customer_id,
        $customer_zone_id, $customer_country_id, $cart, $currencies;
        $currency = $_SESSION['currency'];

        $od_amount=0;
	
		// check payment method
        if ($_SESSION['payment'] != "billmate_invoice")
            return $od_amount;

		$od_amount = MODULE_BILLMATE_FEE_FIXED * $currencies->get_value($currency);

        if ($od_amount == 0)
            return $od_amount;

        if (MODULE_BILLMATE_FEE_TAX_CLASS > 0) {
            $tod_rate =zen_get_tax_rate(MODULE_BILLMATE_FEE_TAX_CLASS);
            $tod_amount = $od_amount - $od_amount/($tod_rate/100+1);
            $order->info['tax'] += $tod_amount;
            $tax_desc = zen_get_tax_description(
                    MODULE_BILLMATE_FEE_TAX_CLASS,
                    $customer_country_id, $customer_zone_id);
            $order->info['tax_groups']["$tax_desc"] += $tod_amount;
        }

        if (DISPLAY_PRICE_WITH_TAX=="true") {
            $od_amount = $od_amount;
        } else {
            $od_amount = $od_amount-$tod_amount;
            $order->info['total'] += $tod_amount;
        }

        return $od_amount;
    }


    function get_order_total() {
		
        global  $order, $cart, $currencies, $db;
        $order_total = $order->info['total'];
        $currency = $_SESSION['currency'];

// Check if gift voucher is in cart and adjust total
        $products = $order->products;

        for ($i=0; $i<sizeof($products); $i++) {
            $t_prid = zen_get_prid($products[$i]['id']);

            $gv_result = $db->Execute(
                    "select products_price, products_tax_class_id, ".
                    "products_model from " . TABLE_PRODUCTS .
                    " where products_id = '" . $t_prid . "'");

            if (preg_match('/^GIFT/', addslashes($gv_result->fields['products_model']))) {

                $qty = $products[$i]['qty'];
                $products_tax = zen_get_tax_rate($gv_result->fields['products_tax_class_id']);

                if ($this->include_tax =='false') {
                    $gv_amount = $gv_result->fields['products_price'] * $qty;
                } else {
                    $gv_amount = ($gv_result->fields['products_price'] +
                                    zen_calculate_tax(
                                    $gv_result->fields['products_price'],
                                    $products_tax)) * $qty;
                }
                $order_total=$order_total - $gv_amount;
            }
        }
        
		
        if (isset($this->include_tax) && $this->include_tax == 'false')
            $order_total=$order_total-$order->info['tax'];

        if (isset($this->include_shipping) && $this->include_shipping == 'false')
            $order_total=$order_total-$order->info['shipping_cost'];

        return $order_total*$currencies->get_value($currency);
    }


    function check() {
		global $db;

        if (!isset($this->check)) {
            $check_query = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_BILLMATE_FEE_STATUS'");
            $this->check = $check_query->RecordCount() > 0 ? true : false;
        }
        return $this->check;
    }

    function keys() {
        return array('MODULE_BILLMATE_FEE_STATUS',
                'MODULE_BILLMATE_FEE_FIXED',
                'MODULE_BILLMATE_FEE_TAX_CLASS',
                'MODULE_BILLMATE_FEE_SORT_ORDER'
        );
    }

    function install() {
		global $db;
        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Display Total', 'MODULE_BILLMATE_FEE_STATUS', 'true', 'Do you want to display the payment charge', '6', '1','zen_cfg_select_option(array(\'true\', \'false\'), ', now())");
        
        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort Order', 'MODULE_BILLMATE_FEE_SORT_ORDER', '0', 'Sort order of display.', '6', '2', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Fixed invoice charge', 'MODULE_BILLMATE_FEE_FIXED', '20', 'Fixed invoice charge (inc. VAT) in SEK.', '6', '7', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Tax Class', 'MODULE_BILLMATE_FEE_TAX_CLASS', '0', 'Use the following tax class on the payment charge.', '6', '6', 'zen_get_tax_class_title', 'zen_cfg_pull_down_tax_classes(', now())");

    }

    function remove() {
		global $db;
        $keys = '';
        $keys_array = $this->keys();
        for ($i=0; $i<sizeof($keys_array); $i++) {
            $keys .= "'" . $keys_array[$i] . "',";
        }
        $keys = substr($keys, 0, -1);

        $db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_key in (" . $keys . ")");
    }
}
?>

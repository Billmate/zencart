<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2010 osCommerce

  Released under the GNU General Public License
*/
global $db;
chdir('../../../../');
require('includes/application_top.php');


if ((!defined('MODULE_PAYMENT_BILLMATE_STATUS') || (MODULE_PAYMENT_BILLMATE_STATUS  != 'True')) || (!defined('MODULE_PAYMENT_PCBILLMATE_STATUS') || (MODULE_PAYMENT_PCBILLMATE_STATUS  != 'True'))) {
	exit;
}

@include_once(DIR_FS_CATALOG . DIR_WS_CLASSES . 'billmate/billmate_lang.php');
require(DIR_FS_CATALOG . DIR_WS_CLASSES . 'billmate/billmateutils.php');
if(!class_exists('Encoding',false)){
	require_once DIR_FS_CATALOG . DIR_WS_CLASSES.'billmate/utf8.php';
	require_once DIR_FS_CATALOG . DIR_WS_CLASSES.'billmate/commonfunctions.php';
}

$response = file_get_contents("php://input");
$input = json_decode($response, true);
$_DATA = $input['data'];
$_DATA['order_id']= $_DATA['orderid'];

if(isset($_DATA['status']) || ($_DATA['status'] == 'Paid' || $_DATA['status'] == 'Created')){
	if (isset($_DATA['orderid']) && ($_DATA['orderid'] > 0)) {

		$secret = MODULE_PAYMENT_BILLMATE_SECRET;
		$eid = MODULE_PAYMENT_BILLMATE_EID;
		$ssl = true;
		$debug = false;
		$testmode = ((MODULE_PAYMENT_BILLMATE_TESTMODE == 'True')) ? true : false;

		$k = new BillMate($eid,$secret,$ssl,$testmode,$debug);
		$result1 = (object)$k->UpdatePayment( array('PaymentData'=> array("number"=>$_DATA['number'], "orderid"=>(string)$_DATA['order_id'])));


		if(is_string($result1) || (isset($result1->message) && is_object($result1))){
		} else {


			$has_BILLMATE_ref = false;
			$fields = $db->Execute("show columns from " . TABLE_ORDERS);
			while(!$fields->EOF) {
				if ( $fields->fields['Field'] == "billmateref" )
					$has_BILLMATE_ref = true;
				$fields->MoveNext();
			}

			if ($has_BILLMATE_ref) {
				$db->Execute("update " . TABLE_ORDERS . " set billmateref='" .
					$result1->number . "' " . " where orders_id = '" .
					$_DATA['orderid'] . "'");
			}

			// Insert transaction # into history file

			$sql_data_array = array('orders_id' => $_DATA['orderid'],
				'orders_status_id' => MODULE_PAYMENT_BILLMATE_ORDER_STATUS_ID,
				'date_added' => 'now()',
				'customer_notified' => 0,
				'comments' => ('Billmate_IPN')
			);
			$db->perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);

			$sql_data_array = array('orders_id' => $_DATA['orderid'],
				'orders_status_id' => MODULE_PAYMENT_BILLMATE_ORDER_STATUS_ID,
				'date_added' => 'now()',
				'customer_notified' => 0,
				'comments' => ('Accepted by Billmate ' . date("Y-m-d G:i:s") .' Invoice #: ' . $result1->number)
			);
			$db->perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);

			$db->Execute("update " . TABLE_ORDERS . " set orders_status = '" . (MODULE_PAYMENT_BILLMATE_ORDER_STATUS_ID ) . "', last_modified = now() where orders_id = '" . (int)$_DATA['order_id'] . "'");

		}
	}
} 
if(isset($_DATA['status']) && ($_DATA['status'] == 'Failed' || $_DATA['status'] == 'Cancelled')){
	billmate_remove_order($_DATA['orderid'],true);

}
exit;

// -------------------------------------------------------------------------------------- //

require('includes/application_bottom.php');
?>

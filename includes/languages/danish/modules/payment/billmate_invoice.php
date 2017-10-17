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

  // Translations in installer
  define('MODULE_PAYMENT_BILLMATE_ALLOWED_TITLE', 'Leave this blank!');
  define('MODULE_PAYMENT_BILLMATE_ALLOWED_DESC', '');
  define('MODULE_PAYMENT_BILLMATE_STATUS_TITLE', 'Enable Billmate module');
  define('MODULE_PAYMENT_BILLMATE_STATUS_DESC', 'Do you want to accept Billmate payments?');
  define('MODULE_PAYMENT_BILLMATE_ORDER_STATUS_ID_TITLE', 'Set Order Status');
  define('MODULE_PAYMENT_BILLMATE_ORDER_STATUS_ID_DESC', 'Set the status of orders made with this payment module to this value');
  define('MODULE_PAYMENT_BILLMATE_EID_TITLE', 'Merchant ID');
  define('MODULE_PAYMENT_BILLMATE_EID_DESC', 'Merchant ID (estore id) to use for the Billmate service (provided by Billmate)');
  define('MODULE_PAYMENT_BILLMATE_SECRET_TITLE', 'Shared secret');
  define('MODULE_PAYMENT_BILLMATE_SECRET_DESC', 'Shared secret to use with the Billmate service (provided by Billmate)');
  define('MODULE_PAYMENT_BILLMATE_ARTNO_TITLE', 'Product artno attribute (id or model)');
  define('MODULE_PAYMENT_BILLMATE_ARTNO_DESC', 'Use the following product attribute for ArtNo.');
//  define('MODULE_PAYMENT_BILLMATE_AUTO_ACTIVATE_TITLE', 'Enable Auto Activate');
//  define('MODULE_PAYMENT_BILLMATE_AUTO_ACTIVATE_DESC', 'Do you want to enable invoice auto activate?');
//  define('MODULE_PAYMENT_BILLMATE_AUTO_ACTIVATE_SEND_DELAY_TITLE', 'Activate delay (days)');
//  define('MODULE_PAYMENT_BILLMATE_AUTO_ACTIVATE_SEND_DELAY_DESC', 'When auto activating delay sending the invoice for x days.');
//  define('MODULE_PAYMENT_BILLMATE_PRE_POPULATE_TITLE', 'Pre-populate Personnummer field');
//  define('MODULE_PAYMENT_BILLMATE_PRE_POPULATE_DESC', 'Do you want to pre-populate the personnummer field?');
//  define('MODULE_PAYMENT_BILLMATE_YSALARY_TITLE', 'Yearly Salary');
//  define('MODULE_PAYMENT_BILLMATE_YSALARY_DESC', 'Any order above set value will show a yearly salary input field in checkout. (-1 to disable)'); 
  define('MODULE_PAYMENT_BILLMATE_ORDER_LIMIT_TITLE', 'Credit limit');
  define('MODULE_PAYMENT_BILLMATE_ORDER_LIMIT_DESC', 'Only show this payment alternative for orders less than the value below.');
  define('MODULE_PAYMENT_BILLMATE_ORDER_TOTAL_IGNORE_TITLE', 'Ignore table');
  define('MODULE_PAYMENT_BILLMATE_ORDER_TOTAL_IGNORE_DESC', 'Ignore these entries from order total list when compiling the invoice data');
  define('MODULE_PAYMENT_BILLMATE_ZONE_TITLE', 'Payment Zone');
  define('MODULE_PAYMENT_BILLMATE_ZONE_DESC', 'If a zone is selected, only enable this payment method for that zone.');
  define('MODULE_PAYMENT_BILLMATE_TAX_CLASS_TITLE', 'Tax Class');
  define('MODULE_PAYMENT_BILLMATE_TAX_CLASS_DESC', 'Use the following tax class on the payment charge.');
  define('MODULE_PAYMENT_BILLMATE_SORT_ORDER_TITLE', 'Sort order of display.');
  define('MODULE_PAYMENT_BILLMATE_SORT_ORDER_DESC', 'Sort order of display. Lowest is displayed first.');
  define('MODULE_PAYMENT_BILLMATE_LIVEMODE_TITLE', 'Live Server');
  define('MODULE_PAYMENT_BILLMATE_LIVEMODE_DESC', 'Do you want to use Billmate LIVE server (true) or BETA server (false)?');
  define('MODULE_PAYMENT_BILLMATE_TESTMODE_TITLE', 'Testmode');
  define('MODULE_PAYMENT_BILLMATE_TESTMODE_DESC', 'Do you want to activate the Testmode? We will not pay for the invoices created with the test persons nor companies and we will not collect any fees as well.');

  define('MODULE_PAYMENT_BILLMATE_TEXT_TITLE', 'Billmate - Invoice (SE)');
  define('MODULE_PAYMENT_BILLMATE_TEXT_DESCRIPTION', 'Swedish invoice from Billmate');
  define('MODULE_PAYMENT_BILLMATE_TEXT_CONFIRM_DESCRIPTION', 'www.billmate.se');
  

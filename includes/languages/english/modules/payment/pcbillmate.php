<?php
/**
 *  Copyright 2015 Billmate AB. All rights reserved.
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
 *  or implied, of Billmate AB.
 *
 */

  // Translations in installer
  define('MODULE_PAYMENT_PCBILLMATE_ALLOWED_TITLE', 'Leave this blank!');
  define('MODULE_PAYMENT_PCBILLMATE_ALLOWED_DESC', '');
  define('MODULE_PAYMENT_PCBILLMATE_STATUS_TITLE', 'Enable Billmate Part Payment Module');
  define('MODULE_PAYMENT_PCBILLMATE_STATUS_DESC', 'Do you want to accept Billmate payments?');
  define('MODULE_PAYMENT_PCBILLMATE_ORDER_STATUS_ID_TITLE', 'Set Order Status');
  define('MODULE_PAYMENT_PCBILLMATE_ORDER_STATUS_ID_DESC', 'Set the status of orders made with this payment module to this value');
  define('MODULE_PAYMENT_PCBILLMATE_EID_TITLE', 'Merchant ID');
  define('MODULE_PAYMENT_PCBILLMATE_EID_DESC', 'Merchant ID (estore id) to use for the Billmate service (provided by Billmate)');
  define('MODULE_PAYMENT_PCBILLMATE_SECRET_TITLE', 'Shared secret');
  define('MODULE_PAYMENT_PCBILLMATE_SECRET_DESC', 'Shared secret to use with the Billmate service (provided by Billmate)');
  define('MODULE_PAYMENT_PCBILLMATE_ARTNO_TITLE', 'Product artno attribute (id or model)');
  define('MODULE_PAYMENT_PCBILLMATE_ARTNO_DESC', 'Use the following product attribute for ArtNo.');
  define('MODULE_PAYMENT_PCBILLMATE_AUTO_ACTIVATE_TITLE', 'Enable Auto Activate');
  define('MODULE_PAYMENT_PCBILLMATE_AUTO_ACTIVATE_DESC', 'Do you want to enable invoice auto activate?');
  define('MODULE_PAYMENT_PCBILLMATE_AUTO_ACTIVATE_SEND_DELAY_TITLE', 'Activate delay (days)');
  define('MODULE_PAYMENT_PCBILLMATE_AUTO_ACTIVATE_SEND_DELAY_DESC', 'When auto activating delay sending the invoice for x days.');
  define('MODULE_PAYMENT_PCBILLMATE_PRE_POPULATE_TITLE', 'Pre-populate Personnummer field');
  define('MODULE_PAYMENT_PCBILLMATE_PRE_POPULATE_DESC', 'Do you want to pre-populate the personnummer field?');
  define('MODULE_PAYMENT_PCBILLMATE_ORDER_LIMIT_TITLE', 'Credit limit');
  define('MODULE_PAYMENT_PCBILLMATE_ORDER_LIMIT_DESC', 'Only show this payment alternative for orders less than the value below.');
  define('MODULE_PAYMENT_PCBILLMATE_ORDER_TOTAL_IGNORE_TITLE', 'Ignore table');
  define('MODULE_PAYMENT_PCBILLMATE_ORDER_TOTAL_IGNORE_DESC', 'Ignore these entries from order total list when compiling the invoice data');
  define('MODULE_PAYMENT_PCBILLMATE_ZONE_TITLE', 'Payment Zone');
  define('MODULE_PAYMENT_PCBILLMATE_ZONE_DESC', 'If a zone is selected, only enable this payment method for that zone.');
  define('MODULE_PAYMENT_PCBILLMATE_TAX_CLASS_TITLE', 'Tax Class');
  define('MODULE_PAYMENT_PCBILLMATE_TAX_CLASS_DESC', 'Use the following tax class on the payment charge.');
  define('MODULE_PAYMENT_PCBILLMATE_SORT_ORDER_TITLE', 'Sort order of display.');
  define('MODULE_PAYMENT_PCBILLMATE_SORT_ORDER_DESC', 'Sort order of display. Lowest is displayed first.');
  define('MODULE_PAYMENT_PCBILLMATE_LIVEMODE_TITLE', 'Live Server');
  define('MODULE_PAYMENT_PCBILLMATE_LIVEMODE_DESC', 'Do you want to use Billmate LIVE server (true) or BETA server (false)?');
  define('MODULE_PAYMENT_PCBILLMATE_TESTMODE_TITLE', '(TESTMODE)');
  define('MODULE_PAYMENT_PCBILLMATE_TESTMODE_DESC', 'Do you want to activate the Testmode? We will not pay for the invoices created with the test persons nor companies and we will not collect any fees as well.');
  define('MODULE_PAYMENT_PCBILLMATE_PCLASS_TITLE', 'Set Pclass ID for campaigns');
  define('MODULE_PAYMENT_PCBILLMATE_PCLASS_DESC', 'Semicolon separated list, with no spaces.');
  define('MODULE_PAYMENT_PCBILLMATE_MONTH_TITLE', 'Set Month');
  define('MODULE_PAYMENT_PCBILLMATE_MONTH_DESC', 'Semicolon separated list, with no spaces.');
  define('MODULE_PAYMENT_PCBILLMATE_MONTH_FEE_TITLE', 'Set Monthly Fee');
  define('MODULE_PAYMENT_PCBILLMATE_MONTH_FEE_DESC', 'Semicolon separated list, with no spaces.');
  define('MODULE_PAYMENT_PCBILLMATE_MONTH','month');
  define('MODULE_PAYMENT_PCBILLMATE_RATE_TITLE', 'Set Rate');
  define('MODULE_PAYMENT_PCBILLMATE_RATE_DESC', 'Semicolon separated list, with no spaces.');
  define('MODULE_PAYMENT_PCBILLMATE_START_FEE_TITLE', 'Set Start Fee');
  define('MODULE_PAYMENT_PCBILLMATE_START_FEE_DESC', 'Semicolon separated list, with no spaces.');
  define('MODULE_PAYMENT_PCBILLMATE_PCLASS_DEFAULT_TITLE', 'Pclass for Account');
  define('MODULE_PAYMENT_PCBILLMATE_PCLASS_DEFAULT_DESC', 'Pclass for \"Account\"');

  define('MODULE_PAYMENT_PCBILLMATE_TEXT_TITLE', 'Billmate Part Payment');
  define('MODULE_PAYMENT_PCBILLMATE_FRONTEND_TEXT_TITLE', 'Billmate Part Payment');
  define('MODULE_PAYMENT_PCBILLMATE_TEXT_DESCRIPTION', 'Part Pay from Billmate');
  define('MODULE_PAYMENT_PCBILLMATE_TEXT_CONFIRM_DESCRIPTION', 'www.billmate.se');


define('MODULE_PAYMENT_PCBILLMATE_ADDRESS_WRONG', 'Pay with invoice can only be made with a registered adress. Would you like to make the purchase with the following registered address:');
define('MODULE_PAYMENT_PCBILLMATE_CORRECT_ADDRESS', 'Correct Address is :');
define('MODULE_PAYMENT_PCBILLMATE_CORRECT_ADDRESS_OPTION', 'Click Yes to continue with new address, No to choose other payment method');
define('MODULE_PAYMENT_PCBILLMATE_YES', 'Yes, make purchase with this address.');
define('MODULE_PAYMENT_PCBILLMATE_NO', 'No, I want to specify another person / company or change payment method.');
  define('MODULE_PAYMENT_PCBILLMATE_CHOOSEALTERNATIVES', 'Choose alternative address below');
  define('MODULE_PAYMENT_PCBILLMATE_ERRORDIVIDE', 'Billmate Account - error');

  define('MODULE_PAYMENT_PCBILLMATE_PERSON_NUMBER','Social Security Number / Corporate Registration Number:');
  define('MODULE_PAYMENT_PCBILLMATE_EMAIL','My email is accurate and can be used for invoicing.<br/>I also confirm the <a style="text-decoration: underline !important;" id="terms-delbetalning" href="javascript:;">terms and conditions</a> and accept the liability.
    <script>
    if (!window.jQuery) {
      var script = document.createElement(\'script\');
      script.type = "text/javascript";
      script.src = "http://code.jquery.com/jquery-1.9.1.js";
      document.getElementsByTagName(\'head\')[0].appendChild(script);
    }
    </script>
    <script type="text/javascript">
      setTimeout(function(){
      var eid = "%s";
        jQuery(function(){
          $.getScript("https://billmate.se/billmate/base.js", function(){
          $("#terms-delbetalning").Terms("villkor_delbetalning",{eid: eid,effectiverate:34});
          });
        });
      },1000);
    </script>');
  define('MODULE_PAYMENT_PCBILLMATE_ADDR_TITLE','');
  define('MODULE_PAYMENT_PCBILLMATE_CONDITIONS','');
  define('MODULE_PAYMENT_PCBILLMATE_ADDR_NOTICE','<br/>Note: Your billing and shipping address will<br/>automatically be updated to your registered address.');
  define('MODULE_PAYMENT_PCBILLMATE_CHOOSECONSUMERCREDIT','Payment Options');
	define('MODULE_PAYMENT_PCBILLMATE_VAT','VAT');

  define('MODULE_PAYMENT_PCBILLMATE_TITLE', 'Billmate Part Pay from xx / month'); //replace xx with amount + currency (e.g. 100 kr)
  define('MODULE_PAYMENT_PCBILLMATE_WITHOUT_TAX', 'Prices are excluding VAT');



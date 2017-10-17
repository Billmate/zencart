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
  define('MODULE_PAYMENT_BILLMATE_ALLOWED_TITLE', 'Leave this blank!');
  define('MODULE_PAYMENT_BILLMATE_ALLOWED_DESC', '');
  define('MODULE_PAYMENT_BILLMATE_STATUS_TITLE', 'Enable Billmate Invoice Module');
  define('MODULE_PAYMENT_BILLMATE_STATUS_DESC', 'Do you want to accept Billmate payments?');
  define('MODULE_PAYMENT_BILLMATE_ORDER_STATUS_ID_TITLE', 'Approved Payments Order Status');
  define('MODULE_PAYMENT_BILLMATE_ORDER_STATUS_ID_DESC', 'Set the order status of approved payments.');
  define('MODULE_PAYMENT_BILLMATE_EID_TITLE', 'Merchant ID');
  define('MODULE_PAYMENT_BILLMATE_EID_DESC', 'Merchant ID to use for the Billmate service (provided by Billmate)');
  define('MODULE_PAYMENT_BILLMATE_SECRET_TITLE', 'Shared secret');
  define('MODULE_PAYMENT_BILLMATE_SECRET_DESC', 'Shared secret to use with the Billmate service (provided by Billmate)');
  define('MODULE_PAYMENT_BILLMATE_ARTNO_TITLE', 'Product artno attribute (id or model)');
  define('MODULE_PAYMENT_BILLMATE_ARTNO_DESC', 'Use the following product attribute for ArtNo.');
  define('MODULE_PAYMENT_BILLMATE_PERSON_NUMBER','Social Security Number / Corporate Registration Number:');
  define('MODULE_PAYMENT_BILLMATE_EMAIL','My email is accurate and can be used for invoicing.<br/>I also confirm the <a style="text-decoration: underline !important;" id="terms" href="javascript:;">terms and conditions</a> and accept the liability.
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
        jQuery(function(){
          $.getScript("https://billmate.se/billmate/base.js", function(){
            $("#terms").Terms("villkor",{invoicefee: 0});
          });
        });
      },1000);
    </script>');
  define('MODULE_PAYMENT_BILLMATE_ADDR_TITLE','<br/>Note: Your billing and shipping address will<br/>automatically be updated to your registered address.');
  define('MODULE_PAYMENT_BILLMATE_CONDITIONS','');
  define('MODULE_PAYMENT_BILLMATE_ADDR_NOTICE','');
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
  define('MODULE_PAYMENT_BILLMATE_TEXT_TESTMODE_TITLE', '(TESTMODE)');
  define('MODULE_PAYMENT_BILLMATE_TEXT_TESTMODE_DESC', 'Do you want to activate the Testmode? We will not pay for the invoices created with the test persons nor companies and we will not collect any fees as well.');

  define('MODULE_PAYMENT_BILLMATE_TEXT_TITLE', 'Invoice');
  define('MODULE_PAYMENT_BILLMATE_FRONTEND_TEXT_TITLE', 'Invoice');
  define('MODULE_PAYMENT_BILLMATE_TEXT_DESCRIPTION', 'Invoice Payment method, by Billmate');
  define('MODULE_PAYMENT_BILLMATE_TEXT_CONFIRM_DESCRIPTION', 'www.billmate.se');
  define('MODULE_PAYMENT_BILLMATE_EXTRA_FEE',' - %s invoice fee is added to the order.');

define('MODULE_PAYMENT_BILLMATE_ADDRESS_WRONG', 'Pay with invoice can only be made with a registered adress. Would you like to make the purchase with the following registered address:');
  define('MODULE_PAYMENT_BILLMATE_CORRECT_ADDRESS', 'Correct Address is :');
  define('MODULE_PAYMENT_BILLMATE_CORRECT_ADDRESS_OPTION', 'Click Yes to continue with new address, No to choose other payment method');
  define('MODULE_PAYMENT_BILLMATE_YES', 'Yes, make purchase with this address.');
  define('MODULE_PAYMENT_BILLMATE_NO', 'No, I want to specify another person / company or change payment method.');
  define('MODULE_PAYMENT_BILLMATE_VAT','VAT');
  define('MODULE_PAYMENT_BILLMATE_CHOOSEALTERNATIVES', 'Choose alternative address below');
  define('MODULE_PAYMENT_BILLMATE_ERRORINVOICE', 'Billmate - Failed');


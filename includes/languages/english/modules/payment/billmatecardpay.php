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
 *  FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL BILLMATECARDPAY AB OR
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
  define('MODULE_PAYMENT_BILLMATECARDPAY_ALLOWED_TITLE', 'Leave this blank!');
  define('MODULE_PAYMENT_BILLMATECARDPAY_ALLOWED_DESC', '');
  define('MODULE_PAYMENT_BILLMATECARDPAY_STATUS_TITLE', 'Enable Billmate Card module');
  define('MODULE_PAYMENT_BILLMATECARDPAY_STATUS_DESC', 'Do you want to accept Billmate Cardpay payments?');
  define('MODULE_PAYMENT_BILLMATECARDPAY_ORDER_STATUS_ID_TITLE', 'Set Order Status');
  define('MODULE_PAYMENT_BILLMATECARDPAY_ORDER_STATUS_ID_DESC', 'Set the status of orders made with this payment module to this value');
  define('MODULE_PAYMENT_BILLMATECARDPAY_EID_TITLE', 'Merchant ID');
  define('MODULE_PAYMENT_BILLMATECARDPAY_EID_DESC', 'Merchant ID (estore id) to use for the Billmate Cardpay service (provided by Billmate Cardpay)');
  define('MODULE_PAYMENT_BILLMATECARDPAY_SECRET_TITLE', 'Shared secret');
  define('MODULE_PAYMENT_BILLMATECARDPAY_SECRET_DESC', 'Shared secret to use with the Billmate Cardpay service (provided by Billmate Cardpay)');
  define('MODULE_PAYMENT_BILLMATECARDPAY_ARTNO_TITLE', 'Product artno attribute (id or model)');
  define('MODULE_PAYMENT_BILLMATECARDPAY_ARTNO_DESC', 'Use the following product attribute for ArtNo.');
  define('MODULE_PAYMENT_BILLMATECARDPAY_ORDER_LIMIT_TITLE', 'Credit limit');
  define('MODULE_PAYMENT_BILLMATECARDPAY_ORDER_LIMIT_DESC', 'Only show this payment alternative for orders less than the value below.');
  define('MODULE_PAYMENT_BILLMATECARDPAY_ORDER_TOTAL_IGNORE_TITLE', 'Ignore table');
  define('MODULE_PAYMENT_BILLMATECARDPAY_ORDER_TOTAL_IGNORE_DESC', 'Ignore these entries from order total list when compiling the invoice data');
  define('MODULE_PAYMENT_BILLMATECARDPAY_ZONE_TITLE', 'Payment Zone');
  define('MODULE_PAYMENT_BILLMATECARDPAY_ZONE_DESC', 'If a zone is selected, only enable this payment method for that zone.');
  define('MODULE_PAYMENT_BILLMATECARDPAY_TAX_CLASS_TITLE', 'Tax Class');
  define('MODULE_PAYMENT_BILLMATECARDPAY_TAX_CLASS_DESC', 'Use the following tax class on the payment charge.');
  define('MODULE_PAYMENT_BILLMATECARDPAY_SORT_ORDER_TITLE', 'Sort order of display.');
  define('MODULE_PAYMENT_BILLMATECARDPAY_SORT_ORDER_DESC', 'Sort order of display. Lowest is displayed first.');
  define('MODULE_PAYMENT_BILLMATECARDPAY_LIVEMODE_TITLE', 'Live Server');
  define('MODULE_PAYMENT_BILLMATECARDPAY_LIVEMODE_DESC', 'Do you want to use Billmate Cardpay LIVE server (true) or BETA server (false)?');
  define('MODULE_PAYMENT_BILLMATECARDPAY_TESTMODE_TITLE', '(Testmode)');
  define('MODULE_PAYMENT_BILLMATECARDPAY_TESTMODE_DESC', 'Do you want to activate the Testmode? We will not pay for the invoices created with the test persons nor companies and we will not collect any fees as well.');
	define('MODULE_PAYMENT_BILLMATECARDPAY_VAT','Moms');

  define('MODULE_PAYMENT_BILLMATECARDPAY_TEXT_TITLE', 'Billmate Card');
  define('MODULE_PAYMENT_BILLMATECARDPAY_FRONTEND_TEXT_TITLE', 'Billmate Card');
  define('MODULE_PAYMENT_BILLMATECARDPAY_LANG_TESTMODE', '(TESTMODE)');
  define('MODULE_PAYMENT_BILLMATECARDPAY_TEXT_DESCRIPTION', 'Credit Card Purchase from Billmate Card');
  define('MODULE_PAYMENT_BILLMATECARDPAY_TEXT_CONFIRM_DESCRIPTION', 'www.billmate.se');
  define('MODULE_PAYMENT_BILLMATECARDPAY_CANCEL', 'The card payment has been canceled before it was processed. Please try again or choose a different payment method.');
  define('MODULE_PAYMENT_BILLMATECARDPAY_FAILED', 'Unfortunately your card payment was not processed with the provided card details. Please try again or choose another payment method.');




<?php
/**
 *  Copyright 2010 BILLMATECARDPAY AB. All rights reserved.
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
 *  THIS SOFTWARE IS PROVIDED BY BILLMATECARDPAY AB "AS IS" AND ANY EXPRESS OR IMPLIED
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
 *  or implied, of BILLMATECARDPAY AB.
 *
 */

  // Translations in installer
  define('MODULE_PAYMENT_BILLMATECARDPAY_ALLOWED_TITLE', 'Lämna det tomt!');
  define('MODULE_PAYMENT_BILLMATECARDPAY_ALLOWED_DESC', '');
  define('MODULE_PAYMENT_BILLMATECARDPAY_STATUS_TITLE', 'Aktivera Billmate Kort');
  define('MODULE_PAYMENT_BILLMATECARDPAY_STATUS_DESC', 'Vill du ta emot Billmate Korts betalningar?');
  define('MODULE_PAYMENT_BILLMATECARDPAY_ORDER_STATUS_ID_TITLE', 'Ställ Orderstatus');
  define('MODULE_PAYMENT_BILLMATECARDPAY_ORDER_STATUS_ID_DESC', 'Ställ status för beställningar som görs med denna betalningsmetod modul till detta värde');
  define('MODULE_PAYMENT_BILLMATECARDPAY_EID_TITLE', 'Merchant ID');
  define('MODULE_PAYMENT_BILLMATECARDPAY_EID_DESC', 'Merchant ID (eStore id) som ska användas för Billmate Kort tjänsten (tillhandahålls av Billmate Kort)');
  define('MODULE_PAYMENT_BILLMATECARDPAY_SECRET_TITLE', 'delad hemlighet');
  define('MODULE_PAYMENT_BILLMATECARDPAY_SECRET_DESC', 'Delad hemlighet att använda med Billmate Kort tjänst (som tillhandahålls av Billmate Kort)');
  define('MODULE_PAYMENT_BILLMATECARDPAY_ARTNO_TITLE', 'Produkt art nr attribut (id eller modell)');
  define('MODULE_PAYMENT_BILLMATECARDPAY_ARTNO_DESC', 'Använd följande produkt attribut för artnr.');
  define('MODULE_PAYMENT_BILLMATECARDPAY_ORDER_LIMIT_TITLE', 'kreditgräns');
  define('MODULE_PAYMENT_BILLMATECARDPAY_ORDER_LIMIT_DESC', 'Visa endast denna betalning alternativ för beställningar med färre än värdet nedan.');
  define('MODULE_PAYMENT_BILLMATECARDPAY_ORDER_TOTAL_IGNORE_TITLE', 'Ignorera tabell');
  define('MODULE_PAYMENT_BILLMATECARDPAY_ORDER_TOTAL_IGNORE_DESC', 'Ignorera dessa poster från den totala ordersumman listan när de sammanställer fakturaunderlag');
  define('MODULE_PAYMENT_BILLMATECARDPAY_ZONE_TITLE', 'Betalning Zone');
  define('MODULE_PAYMENT_BILLMATECARDPAY_ZONE_DESC', 'Om en zon är markerad, endast aktivera denna betalningsmetod för den zonen.');
  define('MODULE_PAYMENT_BILLMATECARDPAY_TAX_CLASS_TITLE', 'Tax Class');
  define('MODULE_PAYMENT_BILLMATECARDPAY_TAX_CLASS_DESC', 'Använd följande skatteklass på betalning laddning.');
  define('MODULE_PAYMENT_BILLMATECARDPAY_SORT_ORDER_TITLE', 'Sortera ordningen på displayen.');
  define('MODULE_PAYMENT_BILLMATECARDPAY_SORT_ORDER_DESC', 'Sortera ordningen på displayen. Lägst visas först.');
  define('MODULE_PAYMENT_BILLMATECARDPAY_LIVEMODE_TITLE', 'Live Server');
  define('MODULE_PAYMENT_BILLMATECARDPAY_LIVEMODE_DESC', 'Vill du använda Billmate Kort LIVE server (true) eller BETA-servern (falskt)?');
  define('MODULE_PAYMENT_BILLMATECARDPAY_TESTMODE_TITLE', 'Test-läge');
  define('MODULE_PAYMENT_BILLMATECARDPAY_TESTMODE_DESC', 'Vill du aktivera testläget? Vi kommer inte att betala för de fakturor som skapas med testpersonerna eller företag, och vi kommer inte att samla in några avgifter också.');

  define('MODULE_PAYMENT_BILLMATECARDPAY_TEXT_TITLE', 'Billmate Kort');
  define('MODULE_PAYMENT_BILLMATECARDPAY_LANG_TESTMODE', '(TESTLÄGE)');
  define('MODULE_PAYMENT_BILLMATECARDPAY_TEXT_DESCRIPTION', 'Credit Kortköp från Billmate Kort');
  define('MODULE_PAYMENT_BILLMATECARDPAY_TEXT_CONFIRM_DESCRIPTION', 'www.billmate.se');
  

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
  define('MODULE_PAYMENT_PCBILLMATE_ALLOWED_TITLE', 'Lämna det tomt!');
  define('MODULE_PAYMENT_PCBILLMATE_ALLOWED_DESC', '');
  define('MODULE_PAYMENT_PCBILLMATE_STATUS_TITLE', 'Aktivera Billmate Delbetalning');
  define('MODULE_PAYMENT_PCBILLMATE_STATUS_DESC', 'Vill du ta emot Billmate betalningar?');
  define('MODULE_PAYMENT_PCBILLMATE_ORDER_STATUS_ID_TITLE', 'Ställ Orderstatus');
  define('MODULE_PAYMENT_PCBILLMATE_ORDER_STATUS_ID_DESC', 'Ställ status för beställningar som görs med denna betalningsmetod modul till detta värde');
  define('MODULE_PAYMENT_PCBILLMATE_EID_TITLE', 'Merchant ID');
  define('MODULE_PAYMENT_PCBILLMATE_EID_DESC', 'Merchant ID (eStore id) som ska användas för Billmate tjänst (som tillhandahålls av Billmate)');
  define('MODULE_PAYMENT_PCBILLMATE_SECRET_TITLE', 'delad hemlighet');
  define('MODULE_PAYMENT_PCBILLMATE_SECRET_DESC', 'Delad hemlighet att använda med Billmate tjänst (som tillhandahålls av Billmate)');
  define('MODULE_PAYMENT_PCBILLMATE_ARTNO_TITLE', 'Produkt art nr attribut (id eller modell)');
  define('MODULE_PAYMENT_PCBILLMATE_ARTNO_DESC', 'Använd följande produkt attribut för artnr.');
  define('MODULE_PAYMENT_PCBILLMATE_ORDER_LIMIT_TITLE', 'kreditgräns');
  define('MODULE_PAYMENT_PCBILLMATE_ORDER_LIMIT_DESC', 'Visa endast denna betalning alternativ för beställningar med färre än värdet nedan.');
  define('MODULE_PAYMENT_PCBILLMATE_ORDER_TOTAL_IGNORE_TITLE', 'Ignorera tabell');
  define('MODULE_PAYMENT_PCBILLMATE_ORDER_TOTAL_IGNORE_DESC', 'Ignorera dessa poster från den totala ordersumman listan när de sammanställer fakturaunderlag');
  define('MODULE_PAYMENT_PCBILLMATE_ZONE_TITLE', 'Betalning Zone');
  define('MODULE_PAYMENT_PCBILLMATE_ZONE_DESC', 'Om en zon är markerad, endast aktivera denna betalningsmetod för den zonen.');
  define('MODULE_PAYMENT_PCBILLMATE_TAX_CLASS_TITLE', 'Tax Class');
  define('MODULE_PAYMENT_PCBILLMATE_TAX_CLASS_DESC', 'Använd följande skatteklass på betalning laddning.');
  define('MODULE_PAYMENT_PCBILLMATE_SORT_ORDER_TITLE', 'Sortera ordningen på displayen.');
  define('MODULE_PAYMENT_PCBILLMATE_SORT_ORDER_DESC', 'Sortera ordningen på displayen. Lägst visas först.');
  define('MODULE_PAYMENT_PCBILLMATE_LIVEMODE_TITLE', 'Live Server');
  define('MODULE_PAYMENT_PCBILLMATE_LIVEMODE_DESC', 'Vill du använda Billmate LIVE server (true) eller BETA-servern (falskt)?');
  define('MODULE_PAYMENT_PCBILLMATE_TESTMODE_TITLE', 'Testläget');
  define('MODULE_PAYMENT_PCBILLMATE_TESTMODE_DESC', 'Vill du aktivera testläget? Vi kommer inte att betala för de fakturor som skapas med testpersonerna eller företag, och vi kommer inte att samla in några avgifter också.');
  define('MODULE_PAYMENT_PCBILLMATE_PCLASS_TITLE', 'Ställ Pclass ID för kampanjer');
  define('MODULE_PAYMENT_PCBILLMATE_PCLASS_DESC', 'Semikolon separerad lista, utan mellanslag.');
  define('MODULE_PAYMENT_PCBILLMATE_MONTH_TITLE', 'Ställ Månad');
  define('MODULE_PAYMENT_PCBILLMATE_MONTH_DESC', 'Semikolon separerad lista, utan mellanslag.');
  define('MODULE_PAYMENT_PCBILLMATE_MONTH_FEE_TITLE', 'Ställ Månadsavgift');
  define('MODULE_PAYMENT_PCBILLMATE_MONTH_FEE_DESC', 'Semikolon separerad lista, utan mellanslag.');
  define('MODULE_PAYMENT_PCBILLMATE_RATE_TITLE', 'Ställ Rate');
  define('MODULE_PAYMENT_PCBILLMATE_RATE_DESC', 'Semikolon separerad lista, utan mellanslag.');
  define('MODULE_PAYMENT_PCBILLMATE_START_FEE_TITLE', 'Ställ startavgift');
  define('MODULE_PAYMENT_PCBILLMATE_START_FEE_DESC', 'Semikolon separerad lista, utan mellanslag.');
  define('MODULE_PAYMENT_PCBILLMATE_PCLASS_DEFAULT_TITLE', 'Pclass för Konto');
  define('MODULE_PAYMENT_PCBILLMATE_PCLASS_DEFAULT_DESC', 'Pclass för \"konto\"');
  
  define('MODULE_PAYMENT_PCBILLMATE_TEXT_TITLE', 'Billmate - Delbetalning');
  define('MODULE_PAYMENT_PCBILLMATE_TEXT_DESCRIPTION', 'Delbetalning Sverige från Billmate');
  define('MODULE_PAYMENT_PCBILLMATE_TEXT_CONFIRM_DESCRIPTION', 'www.billmate.se');
  
  define('MODULE_PAYMENT_PCBILLMATE_PERSON_NUMBER','Persnr / Orgnr');
  define('MODULE_PAYMENT_PCBILLMATE_EMAIL','Min e-postadress %s är korrekt och får användas för fakturering.');
  define('MODULE_PAYMENT_PCBILLMATE_ADDR_TITLE','Observera');
  define('MODULE_PAYMENT_PCBILLMATE_CONDITIONS','Köpvillkor');
  define('MODULE_PAYMENT_PCBILLMATE_ADDR_NOTICE','Din faktura- och leveransadress kommer att uppdateras automatiskt till din folkbokförda adress.');
  define('MODULE_PAYMENT_PCBILLMATE_CHOOSECONSUMERCREDIT','Välj delbetalning');
  define('MODULE_PAYMENT_PCBILLMATE_WITHOUT_TAX', 'Priser exkl. moms');
  define('MODULE_PAYMENT_PCBILLMATE_TITLE', 'Delbetalning - Billmate fr&aring;n xx/m&aring;n'); //replace xx with amount + currency (e.g. 100 kr)

define('MODULE_PAYMENT_PCBILLMATE_ADDRESS_WRONG', 'Din angivna adress kan inte användas. Köp mot faktura kan bara faktureras och levereras till din bokföringsadress.');
define('MODULE_PAYMENT_PCBILLMATE_CORRECT_ADDRESS', 'Din bokföringsadress:');
define('MODULE_PAYMENT_PCBILLMATE_CORRECT_ADDRESS_OPTION', 'Klicka på Ja för att fortsätta med den nya adressen, Nej för att välja ett annat betalningssätt');
define('MODULE_PAYMENT_PCBILLMATE_YES', 'Ja');
define('MODULE_PAYMENT_PCBILLMATE_NO', 'Nej');

define('MODULE_PAYMENT_PCBILLMATE_CHOOSEALTERNATIVES', 'V&auml;lj alternativ adress nedan');
define('MODULE_PAYMENT_PCBILLMATE_ERRORDIVIDE', 'Billmate Konto - fel');


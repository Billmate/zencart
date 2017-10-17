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


/**
 * Sets the language of the forms.
 *
 * Translates the return error msg to the language used.
 */

// SWEDISH PREDEFINED VARIABLES
define('BILLMATE_LANG_SE_IMGINVOICE',
        '<img src="'.HTTP_SERVER.DIR_WS_HTTP_CATALOG.'/images/billmate/bm_faktura_l.png" />');
define('BILLMATE_LANG_SE_IMGCARDPAY',
        '<img src="'.HTTP_SERVER.DIR_WS_HTTP_CATALOG.'/images/billmate/bm_kort_l.png" />');
define('BILLMATE_LANG_SE_IMGBANK',
        '<img src="'.HTTP_SERVER.DIR_WS_HTTP_CATALOG.'/images/billmate/billmate_bank_s.png" />');
define('BILLMATE_LANG_SE_IMGCONSUMERCREDIT',
        '<img src="'.HTTP_SERVER.DIR_WS_HTTP_CATALOG.'/images/billmate/bm_delbetalning_l.png" />');
		
define('BILLMATE_LANG_ADDRESS_WRONG', 'Your Billing address is wrong.');
define('BILLMATE_LANG_CORRECT_ADDRESS', 'Correct Address is :');
define('BILLMATE_LANG_CORRECT_ADDRESS_OPTION', 'Click Yes to continue with new address, No to choose other payment method');
define('BILLMATE_LANG_YES', 'Yes');
define('BILLMATE_LANG_NO', 'No');
define('BILLMATECARDPAY_LANG_SE_ERRORINVOICE','');
define('BILLMATE_LANG_SE_ADDRESS_WRONG', 'Din angivna adress kan inte användas. Köp mot faktura kan bara faktureras och levereras till din bokföringsadress.');
define('BILLMATE_LANG_SE_CORRECT_ADDRESS', 'Din bokföringsadress:');
define('BILLMATE_LANG_SE_CORRECT_ADDRESS_OPTION', 'Klicka på Ja för att fortsätta med den nya adressen, Nej för att välja ett annat betalningssätt');
define('BILLMATE_LANG_SE_YES', 'Ja');
define('BILLMATE_LANG_SE_NO', 'Nej');

define('BILLMATE_LANG_SE_BILLMATE', 'Konto - %s/m&aring;n');
define('BILLMATE_LANG_SE_FIRSTNAME', 'Ditt f&ouml;rnamn');
define('BILLMATE_LANG_SE_LASTNAME', 'Ditt efternamn');
define('BILLMATE_LANG_SE_STREETADDRESS', 'Adress');
define('BILLMATE_LANG_SE_POSTCODE', 'Postnummer');
define('BILLMATE_LANG_SE_CITY', 'Ort');
define('BILLMATE_LANG_SE_PHONENUMBER', 'Telefonnummer');
define('BILLMATE_LANG_SE_EMAIL', 'Min e-postadress %s är korrekt och får användas för fakturering.');
define('BILLMATE_LANG_SE_CONDITIONS', 'K&ouml;pvillkor');
define('BILLMATE_LANG_SE_PERSONALNUMBER', 'Persnr / Orgnr');
define('BILLMATE_LANG_SE_ADDR_TITLE', 'Observera');
define('BILLMATE_LANG_SE_ADDR_NOTICE','Din faktura- och leveransadress kommer att<br />uppdateras automatiskt till din folkbokf&ouml;rda adress.');
define('BILLMATE_LANG_SE_PAYMENT', 'Betalningsvillkor 14 dagar');
define('BILLMATE_LANG_SE_ERRORCURRENCY',
        'Billmate accepterar endast betalningar i Svenska kronor (SEK)');
define('BILLMATE_LANG_SE_CHOOSECONSUMERCREDIT', 'V&auml;lj delbetalning');
define('BILLMATE_LANG_SE_YEARLYINCOME', '&Aring;rsinkomst');
define('BILLMATE_LANG_SE_ACCEPTCONDITION1_ACCEPT', 'Jag godk&auml;nner');
define('BILLMATE_LANG_SE_ACCEPTCONDITION2_LINK',
        'http://www.billmate.se/kontovillkor.pdf');
define('BILLMATE_LANG_SE_ACCEPTCONDITION3_CONDITIONS', 'k&ouml;pvillkoren');
define('BILLMATE_LANG_SE_ERRORACCEPTCONDITION', 'V&auml;nligen godk&auml;nn k&ouml;pvillkoren');
define('BILLMATE_LANG_SE_CHOOSEALTERNATIVES', 'V&auml;lj alternativ adress nedan');
define('BILLMATE_LANG_SE_PAYMENTTEXT1_PAY', '');
define('BILLMATE_LANG_SE_PAYMENTTEXT2_EACH', '/m&aring;n');
define('BILLMATE_LANG_SE_PAYMENTTEXT3_MONTH', ' m&aring;n - ');
define('BILLMATE_LANG_SE_ERRORINVOICE', 'Billmate Faktura - fel');
define('BILLMATE_LANG_SE_ERRORDIVIDE', 'Billmate Konto - fel');
define('BILLMATE_LANG_SE_ERRORPREPAY', 'F&ouml;rskottsbetalning - fel');
define('BILLMATE_LANG_SE_FROM', 'fr&aring;n');
define('BILLMATE_LANG_SE_PARTPAYMENT_FROM', 'Delbetala fr&aring;n');
define('BILLMATE_LANG_SE_VALIDATION_ERROR1', 'Tyv&auml;rr, vi kunde inte verifiera f&ouml;ljande information:'); // Unfortunately we could not verify the following information:
define('BILLMATE_LANG_SE_VALIDATION_ERROR2', 'Var v&auml;nlig kontrollera dina personuppgifter.'); // Please check your Personal Information carefully.
define('BILLMATE_LANG_SE_TESTMODE', '(TESTMODE)');
define('BILLMATE_LANG_SE_COMPANY', 'F&ouml;retag');
define('BILLMATE_LANG_SE_PRIVATE', 'Privat');
define('BILLMATE_LANG_SE_REFERENCE', 'Referens');
define('BILLMATE_LANG_SE_WITHOUT_TAX', 'Priser exkl. moms');
define('BILLMATE_LANG_SE_INVOICE_TITLE', 'Faktura - Betala 14 dagar efter leverans');
define('BILLMATE_LANG_SE_CARDPAY_TITLE', 'Billmate Cardpay');
define('BILLMATE_LANG_SE_PARTPAY_TITLE', 'Delbetalning - Billmate fr&aring;n xx/m&aring;n'); //replace xx with amount + currency (e.g. 100 kr)

// FINNISH PREDEFINED VARIABLES       
define('BILLMATE_LANG_FI_IMGINVOICE',
        '<img src="images/billmate/invoice/logo_fi.png" />');
define('BILLMATE_LANG_FI_IMGCONSUMERCREDIT',
        '<img src="images/billmate/account/logo_fi.png" />');
define('BILLMATE_LANG_FIN_BILLMATE', 'Tili - %s/kk');
define('BILLMATE_LANG_FI_FIRSTNAME', 'Etunimi');
define('BILLMATE_LANG_FI_LASTNAME', 'Sukunimi');
define('BILLMATE_LANG_FI_STREETADDRESS', 'Osoite');
define('BILLMATE_LANG_FI_POSTCODE', 'Postinumero');
define('BILLMATE_LANG_FI_CITY', 'Postitoimipaikka');
define('BILLMATE_LANG_FI_PHONENUMBER', 'Puhelinnumero');
define('BILLMATE_LANG_FI_EMAIL', 'S&auml;hk&ouml;posti');
define('BILLMATE_LANG_FI_CONDITIONS', 'Ostoehdot');
define('BILLMATE_LANG_FI_PERSONALNUMBER', 'Henkil&ouml;tunnus / Yritystunnus');
define('BILLMATE_LANG_FI_ADDR_TITLE', 'HUOM!');
define('BILLMATE_LANG_FI_ADDR_NOTICE','Laskutusosoiteesi tulee<br />vaihtumaan toimitusosoitteeksesi.');
define('BILLMATE_LANG_FI_PAYMENT', 'Maksuehdot 14 p&auml;iv&auml;&auml;');
define('BILLMATE_LANG_FI_ERRORCURRENCY',
        'Billmate hyv&auml;ksyy ainoastaan maksuja euroissa (EUR)');
define('BILLMATE_LANG_FI_CHOOSECONSUMERCREDIT', 'Valitse osamaksu');
define('BILLMATE_LANG_FI_YEARLYINCOME', 'Vuositulot');
define('BILLMATE_LANG_FI_ACCEPTCONDITION1_ACCEPT', 'Hyv&auml;ksyn ');
define('BILLMATE_LANG_FI_ACCEPTCONDITION2_LINK',
        'http://online.billmate.com/_fi/_osamaksulla/osamaksulla_tiliehdot.pdf');
define('BILLMATE_LANG_FI_ACCEPTCONDITION3_CONDITIONS', 'ostoehdot');
define('BILLMATE_LANG_FI_ERRORACCEPTCONDITION', 'Ole hyv&auml; ja hyv&auml;ksy ostoehdot');
define('BILLMATE_LANG_FI_CHOOSEALTERNATIVES', 'Valitse toinen osoite t&auml;st&auml; alhaalta');
define('BILLMATE_LANG_FI_PAYMENTTEXT1_PAY', '');
define('BILLMATE_LANG_FI_PAYMENTTEXT2_EACH', '/kk ');
define('BILLMATE_LANG_FI_PAYMENTTEXT3_MONTH', ' kuukautta - ');
define('BILLMATE_LANG_FI_ERRORINVOICE', 'Billmate Lasku - virhe');
define('BILLMATE_LANG_FI_ERRORDIVIDE', 'Billmate Tili - virhe');
define('BILLMATE_LANG_FI_ERRORPREPAY', 'Ennakkolasku - virhe');
define('BILLMATE_LANG_FI_FROM', 'alk');
define('BILLMATE_LANG_FI_PARTPAYMENT_FROM', 'Maksa osissa alk.');
define('BILLMATE_LANG_FI_VALIDATION_ERROR1', 'Valitettavasti emme pystyneet vahvistamaan seuraavia tietoja:');
define('BILLMATE_LANG_FI_VALIDATION_ERROR2', 'Yst&auml;v&auml;llisesti tarkista henkil&ouml;tietosi');
define('BILLMATE_LANG_FI_TESTMODE', '(TESTMODE)');
define('BILLMATE_LANG_FI_COMPANY', 'Yritys');
define('BILLMATE_LANG_FI_PRIVATE', 'Yksityishenkil&ouml;');
define('BILLMATE_LANG_FI_REFERENCE', 'Viite');
define('BILLMATE_LANG_FI_WITHOUT_TAX', 'Hinnat ilman ALV:a');
define('BILLMATE_LANG_FI_INVOICE_TITLE', 'Lasku - 14 p&auml;iv&auml;&auml; maksuaikaa');
define('BILLMATE_LANG_FI_PARTPAY_TITLE', 'Osamaksu - Billmate alk xx/kk'); //replace xx with amount + currency (e.g. 100 EUR)

// DANISH PREDEFINED VARIABLES
define('BILLMATE_LANG_DK_IMGINVOICE',
        '<img src="http://billmate.se/bm_faktura_l" />');
define('BILLMATE_LANG_DK_IMGCONSUMERCREDIT',
        '<img src="http://billmate.se/bm_delbetalning_l" />');
define('BILLMATE_LANG_DK_BILLMATE', 'Konto - %s/m&aring;n');
define('BILLMATE_LANG_DK_FIRSTNAME', 'Dit navn');
define('BILLMATE_LANG_DK_LASTNAME', 'Dit efternavn');
define('BILLMATE_LANG_DK_STREETADDRESS', 'Adresse');
define('BILLMATE_LANG_DK_POSTCODE', 'Postnummer');
define('BILLMATE_LANG_DK_CITY', 'By');
define('BILLMATE_LANG_DK_PHONENUMBER', 'Telefonnummer');
define('BILLMATE_LANG_DK_EMAIL', 'E-mail-adresse');
define('BILLMATE_LANG_DK_CONDITIONS', 'Vilk&aring;r');
define('BILLMATE_LANG_DK_PERSONALNUMBER', 'Persnr / Orgnr');
define('BILLMATE_LANG_DK_ADDR_TITLE', 'Bem&aelig;rk');
define('BILLMATE_LANG_DK_ADDR_NOTICE','Din leveringsadresse vil blive<br />anvendt som faktureringsadresse.'); //Your billing address will be set to the shipping address.
define('BILLMATE_LANG_DK_PAYMENT', 'Betaling 14 dage');
define('BILLMATE_LANG_DK_ERRORCURRENCY',
        'Billmate acceptere kun betaling med danske kroner (DKK)');
define('BILLMATE_LANG_DK_CHOOSECONSUMERCREDIT', 'V&aelig;lg din kredit');
define('BILLMATE_LANG_DK_YEARLYINCOME', '&Aring;rlig inkomst');
define('BILLMATE_LANG_DK_ACCEPTCONDITION1_ACCEPT', 'Jeg accepterer ');
define('BILLMATE_LANG_DK_ACCEPTCONDITION2_LINK',
        'http://www.fakturermig.dk/kontovilkar.pdf');
define('BILLMATE_LANG_DK_ACCEPTCONDITION3_CONDITIONS', 'disse vilk&aring;r');
define('BILLMATE_LANG_DK_ERRORACCEPTCONDITION', 'Var god acceptere vilk&aring;rerne');
define('BILLMATE_LANG_DK_CHOOSEALTERNATIVES', 'V&aelig;lg en alternativ adresse nedenfor');
define('BILLMATE_LANG_DK_PAYMENTTEXT1_PAY', 'Betal ');
define('BILLMATE_LANG_DK_PAYMENTTEXT2_EACH', '/mnd');
define('BILLMATE_LANG_DK_PAYMENTTEXT3_MONTH', ' m&aring;ned');
define('BILLMATE_LANG_DK_ERRORINVOICE', 'Billmate Faktura - fejl');
define('BILLMATE_LANG_DK_ERRORDIVIDE', 'Billmate Konto - fejl');
define('BILLMATE_LANG_DK_ERRORPREPAY', 'Forskudsbetaling - fejl');
define('BILLMATE_LANG_DK_FROM', 'fra');
define('BILLMATE_LANG_DK_PARTPAYMENT_FROM', 'Delbetale fra');
define('BILLMATE_LANG_DK_VALIDATION_ERROR1', 'Desv&aelig;rre kunne vi ikke verificere f&oslash;lgende information');  // Unfortunately we could not verify the following information:
define('BILLMATE_LANG_DK_VALIDATION_ERROR2', 'Venligst tjek dine personlige oplysninger omhyggeligt');  // Please check your Personal Information carefully.
define('BILLMATE_LANG_DK_TESTMODE', '(TESTMODE)');
define('BILLMATE_LANG_DK_COMPANY', 'Erhverv');
define('BILLMATE_LANG_DK_PRIVATE', 'Privat');
define('BILLMATE_LANG_DK_REFERENCE', 'Reference');
define('BILLMATE_LANG_DK_WITHOUT_TAX', 'Priser eks. moms');
define('BILLMATE_LANG_DK_INVOICE_TITLE', 'Faktura - betale om 14 dage');
define('BILLMATE_LANG_DK_PARTPAY_TITLE', 'Delbetaling - Billmate fra xx/m&aring;ned'); //replace xx with amount + currency (e.g. 100 DKK)

// NORWEGIAN PREDEFINED VARIABLES
define('BILLMATE_LANG_NO_IMGINVOICE',
        '<img src="http://billmate.se/bm_faktura_l" />');
define('BILLMATE_LANG_NO_IMGCONSUMERCREDIT',
        '<img src="http://billmate.se/bm_delbetalning_l" />');
define('BILLMATE_LANG_NO_BILLMATE', 'Konto - %s/m&aring;n');
define('BILLMATE_LANG_NO_FIRSTNAME', 'Navn');
define('BILLMATE_LANG_NO_LASTNAME', 'Etternavn');
define('BILLMATE_LANG_NO_STREETADDRESS', 'Adresse');
define('BILLMATE_LANG_NO_POSTCODE', 'Postnummer');
define('BILLMATE_LANG_NO_CITY', 'Sted');
define('BILLMATE_LANG_NO_PHONENUMBER', 'Telefonnummer');
define('BILLMATE_LANG_NO_EMAIL', 'E-post');
define('BILLMATE_LANG_NO_CONDITIONS', 'Vilk&aring;r');
define('BILLMATE_LANG_NO_PERSONALNUMBER', 'Persnr / Orgnr');
define('BILLMATE_LANG_NO_ADDR_TITLE', 'Observer');  //Obs, notice, ...?
define('BILLMATE_LANG_NO_ADDR_NOTICE','Din faktureringsadresse vil v&aelig;re<br />den samme som din leveringsadresse.'); //Your billing address will be set to the shipping address.
define('BILLMATE_LANG_NO_PAYMENT', 'Betaling 14 dager');
define('BILLMATE_LANG_NO_ERRORCURRENCY',
        'Billmate aksepterer kun betalinger i norske kroner (NOK)');
define('BILLMATE_LANG_NO_CHOOSECONSUMERCREDIT', 'Velg forbrukerkreditt');
define('BILLMATE_LANG_NO_YEARLYINCOME', '&Aring;rlig innkomst');
define('BILLMATE_LANG_NO_ACCEPTCONDITION1_ACCEPT', 'Jeg godtar');
define('BILLMATE_LANG_NO_ACCEPTCONDITION2_LINK',
        'http://www.fakturermig.dk/kontovilkar.pdf');
define('BILLMATE_LANG_NO_ACCEPTCONDITION3_CONDITIONS', 'disse vilk&aring;rene');
define('BILLMATE_LANG_NO_ERRORACCEPTCONDITION', 'Vennligst godta vilk&aring;rene');
define('BILLMATE_LANG_NO_CHOOSEALTERNATIVES', 'Velg alternativ adresse nedenfor');
define('BILLMATE_LANG_NO_PAYMENTTEXT1_PAY', 'Betal ');
define('BILLMATE_LANG_NO_PAYMENTTEXT2_EACH', '/mnd');
define('BILLMATE_LANG_NO_PAYMENTTEXT3_MONTH', ' m&aring;neder');
define('BILLMATE_LANG_NO_ERRORINVOICE', 'Billmate Faktura - feil');
define('BILLMATE_LANG_NO_ERRORDIVIDE', 'Billmate Konto - feil');
define('BILLMATE_LANG_NO_ERRORPREPAY', 'Forh&aring;ndsbetaling - feil');
define('BILLMATE_LANG_NO_FROM', 'fra');
define('BILLMATE_LANG_NO_PARTPAYMENT_FROM', 'Delbetale fra');
define('BILLMATE_LANG_NO_VALIDATION_ERROR1', 'Desverre kunne vi ikke verifisere f&oslash;lgende informasjon');  // Unfortunately we could not verify the following information:
define('BILLMATE_LANG_NO_VALIDATION_ERROR2', 'Vennligst kontroller din personlige informasjon');  // Please check your Personal Information carefully.
define('BILLMATE_LANG_NO_PAYMENTTEXT2_PAY', 'Kredittkj&oslash;pspris');
define('BILLMATE_LANG_NO_PAYMENTTEXT3_PAY', '&Aring;rsrente');
define('BILLMATE_LANG_NO_TESTMODE', '(TESTMODE)');
define('BILLMATE_LANG_NO_COMPANY', 'Firma');
define('BILLMATE_LANG_NO_PRIVATE', 'Privat');
define('BILLMATE_LANG_NO_REFERENCE', 'Referanse');
define('BILLMATE_LANG_NO_WITHOUT_TAX', 'Priser ekskl. Mva');
define('BILLMATE_LANG_NO_INVOICE_TITLE', 'Faktura - betale om 14 dager');
define('BILLMATE_LANG_NO_PARTPAY_TITLE', 'Delbetaling via Billmate - betal fra xx i m&aring;neden'); //replace xx with amount + currency (e.g. 100 NOK)

// German PREDEFINED VARIABLES
define('BILLMATE_LANG_DE_IMGINVOICE',
        '<img src="images/billmate/invoice/logo_de.png" />');
define('BILLMATE_LANG_DE_IMGCONSUMERCREDIT',
        '<img src="images/billmate/account/logo_de.png" />');
define('BILLMATE_LANG_DE_BILLMATE', 'Konto - %s/Monat');
define('BILLMATE_LANG_DE_FIRSTNAME', 'Vorname');
define('BILLMATE_LANG_DE_LASTNAME', 'Nachname');
define('BILLMATE_LANG_DE_STREETADDRESS', 'Strasse / Nr');
define('BILLMATE_LANG_DE_POSTCODE', 'Postleitzahl');
define('BILLMATE_LANG_DE_CITY', 'Ort');
define('BILLMATE_LANG_DE_PHONENUMBER', 'Telefonnummer');
define('BILLMATE_LANG_DE_EMAIL', 'E-Mail');
define('BILLMATE_LANG_DE_CONDITIONS', 'AGB');
define('BILLMATE_LANG_DE_PERSONALNUMBER', 'Geburtsdatum (ttmmjjjj)');
define('BILLMATE_LANG_DE_ADDR_TITLE', 'Zu beachten');
define('BILLMATE_LANG_DE_ADDR_NOTICE','Ihre Lieferungsadresse wird als<br />Rechnungsadresse angewendet.');
define('BILLMATE_LANG_DE_PAYMENT', 'Zahlungsbedingungen 14 Tage');
define('BILLMATE_LANG_DE_ERRORCURRENCY',
        'Billmate akzeptiert nur Zahlungen in Euro (EUR)');
define('BILLMATE_LANG_DE_CHOOSECONSUMERCREDIT', 'Finanzierung w&auml;hlen');
define('BILLMATE_LANG_DE_YEARLYINCOME', 'Jahreseinkommen');
define('BILLMATE_LANG_DE_ACCEPTCONDITION1_ACCEPT', 'Ich akzeptiere ');
define('BILLMATE_LANG_DE_ACCEPTCONDITION2_LINK',
        'http://www.billmate.se/kontovillkor.pdf');
define('BILLMATE_LANG_DE_ACCEPTCONDITION3_CONDITIONS', 'AGB');
define('BILLMATE_LANG_DE_ERRORACCEPTCONDITION', 'Bitte akzeptieren Sie die AGB');
define('BILLMATE_LANG_DE_CHOOSEALTERNATIVES', 'W&auml;hlen Sie eine alternative Adresse weiter unten');
define('BILLMATE_LANG_DE_PAYMENTTEXT1_PAY', '');
define('BILLMATE_LANG_DE_PAYMENTTEXT2_EACH', '/Monat');
define('BILLMATE_LANG_DE_PAYMENTTEXT3_MONTH', ' Monate - ');
define('BILLMATE_LANG_DE_ERRORINVOICE', 'Billmate Rechnung - Fehler');
define('BILLMATE_LANG_DE_ERRORDIVIDE', 'Billmate Ratenkauf - Fehler');
define('BILLMATE_LANG_DE_ERRORPREPAY', 'Vorkasse - Fehler');
define('BILLMATE_LANG_DE_FROM', 'von');
define('BILLMATE_LANG_DE_PARTPAYMENT_FROM', 'Ratenkauf ab');
define('BILLMATE_LANG_DE_GENDER', 'Anrede');
define('BILLMATE_LANG_DE_MALE', 'Herr');
define('BILLMATE_LANG_DE_FEMALE', 'Frau');
define('BILLMATE_LANG_DE_VALIDATION_ERROR1', 'Leider konnten wir folgende angegebene Angaben nicht verifizieren:');
define('BILLMATE_LANG_DE_VALIDATION_ERROR2', 'Bitte kontrollieren Sie, ob Sie dieses korrekt angegeben haben.');
define('BILLMATE_LANG_DE_TESTMODE', '(TESTMODE)');
define('BILLMATE_LANG_DE_COMPANY', 'Firma');
define('BILLMATE_LANG_DE_PRIVATE', 'Privat');
define('BILLMATE_LANG_DE_REFERENCE', 'Referenz');
define('BILLMATE_LANG_DE_WITHOUT_TAX', 'Preis exkl. Mwst');
define('BILLMATE_LANG_DE_INVOICE_TITLE', 'Rechnung - Zahlung innerhalb von 14 Tagen');
define('BILLMATE_LANG_DE_PARTPAY_TITLE', 'Ratenkauf ab xx'); //replace xx with amount + currency (e.g. 100 EUR)

// Dutch PREDEFINED VARIABLES
define('BILLMATE_LANG_NL_IMGINVOICE',
        '<img src="images/billmate/invoice/logo_nl.png" />');
define('BILLMATE_LANG_NL_IMGCONSUMERCREDIT',
        '<img src="images/billmate/account/logo_nl.png" />');
define('BILLMATE_LANG_NL_BILLMATE', 'Account - %s/Maand');
define('BILLMATE_LANG_NL_FIRSTNAME', 'Voornaam');
define('BILLMATE_LANG_NL_LASTNAME', 'Achternaam');
define('BILLMATE_LANG_NL_STREETADDRESS', 'Adres');
define('BILLMATE_LANG_NL_POSTCODE', 'Postnummer');
define('BILLMATE_LANG_NL_CITY', 'Plaats');
define('BILLMATE_LANG_NL_PHONENUMBER', 'Telefoonnummer');
define('BILLMATE_LANG_NL_EMAIL', 'E-mailadres');
define('BILLMATE_LANG_NL_CONDITIONS', 'Algemene Voorwaarden');
define('BILLMATE_LANG_NL_PERSONALNUMBER', 'Geboortedatum');
define('BILLMATE_LANG_NL_ADDR_TITLE', 'Let op');
define('BILLMATE_LANG_NL_ADDR_NOTICE','Uw factuuradres zal het<br />zelfde zijn als het postadres'); //Your billing address will be set to the shipping address.
define('BILLMATE_LANG_NL_PAYMENT', 'Betalingsvoorwaarden 14 dagen');
define('BILLMATE_LANG_NL_ERRORCURRENCY',
        'Billmate accepteert enkel betaling in Euro (EUR)');
define('BILLMATE_LANG_NL_CHOOSECONSUMERCREDIT', 'Deelbetaling kiezen');
define('BILLMATE_LANG_NL_YEARLYINCOME', 'Jaarinkomen');
define('BILLMATE_LANG_NL_ACCEPTCONDITION1_ACCEPT', 'Ik accepteer');
define('BILLMATE_LANG_NL_ACCEPTCONDITION2_LINK', 'http://www.billmate.se/kontovillkor.pdf');
define('BILLMATE_LANG_NL_ACCEPTCONDITION3_CONDITIONS', 'Algemene Voorwaarden');
define('BILLMATE_LANG_NL_ERRORACCEPTCONDITION', 'Gelieve de Algemene Voorwaarden te accepteren');
define('BILLMATE_LANG_NL_CHOOSEALTERNATIVES', 'Selecteer hieronder alternatief adres');
define('BILLMATE_LANG_NL_PAYMENTTEXT1_PAY', '');
define('BILLMATE_LANG_NL_PAYMENTTEXT2_EACH', '/maand');
define('BILLMATE_LANG_NL_PAYMENTTEXT3_MONTH', ' Maand -  ');
define('BILLMATE_LANG_NL_ERRORINVOICE', 'Billmate Factuur - fout');
define('BILLMATE_LANG_NL_ERRORDIVIDE', 'Billmate Account - fout');
define('BILLMATE_LANG_NL_ERRORPREPAY', 'Acceptgiro - fout');
define('BILLMATE_LANG_NL_FROM', 'van');
define('BILLMATE_LANG_NL_PARTPAYMENT_FROM', 'Deelbetalen vanaf');
define('BILLMATE_LANG_NL_HOUSEEXT', 'Toevoeging');
define('BILLMATE_LANG_NL_GENDER', 'Geslacht');
define('BILLMATE_LANG_NL_MALE', 'Man');
define('BILLMATE_LANG_NL_FEMALE', 'Vrouw');
define('BILLMATE_LANG_NL_VALIDATION_ERROR1', 'Helaas konden wij de volgende informatie niet verifi&euml;ren');  // Unfortunately we could not verify the following information:
define('BILLMATE_LANG_NL_VALIDATION_ERROR2', 'Zou u alstublieft uw persoonlijke informatie zorgvuldig willen controleren');  // Please check your Personal Information carefully.
define('BILLMATE_LANG_NL_TESTMODE', '(TESTMODE)');
define('BILLMATE_LANG_NL_COMPANY', 'Bedrijf');
define('BILLMATE_LANG_NL_PRIVATE', 'Priv&eacute;');
define('BILLMATE_LANG_NL_REFERENCE', 'Referentie');
define('BILLMATE_LANG_NL_WITHOUT_TAX', 'Prijs excl. BTW');
define('BILLMATE_LANG_NL_INVOICE_TITLE', 'Factuur - Betaal binnen 14 dagen');
define('BILLMATE_LANG_NL_PARTPAY_TITLE', 'Gespreide betaling - Billmate vanaf xx/maand'); //replace xx with amount + currency (e.g. 100 EUR)

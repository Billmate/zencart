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
 * Helper Functions for client side validation
 *
 * @version 0.1.0
 * @package billmate_payment_module
 */


/**
 * Check Pno for Sweden
 *
 * Format for Pno:
 * YYYYMMDDCNNNN, C = -|+, YYYYMMDDNNNN, YYMMDDCNNNN, YYMMDDNNNN, length 10-13
 *
 * @param string $pno	Personal number for Sweden
 *
 * @return bool
 */
function validate_pno_se($pno) {
    $result = false;

    //Pno has 10-13 characters
    if (check_length_ge($pno, 10) && check_length_le($pno, 13)) {
        $result = true;
    }
    return $result;
}

/**
 * Check E-Mail
 *
 * Regular Expression:
 * regExp: '^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*(\.[a-zA-Z0-9-][a-zA-Z0-9-]+)+$'
 *
 * @param string $email		Check E-Mail with regular expression
 *
 * @return bool
 */
function validate_email($email) {
    //Regular expression for the email check
    $exp = "/^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*(\.[a-zA-Z0-9-][a-zA-Z0-9-]+)+$/";
    return check_regexp($email, $exp);
}

/**
 * Searches a string for matches to the regular expression
 *
 * @param string  $field		Search string
 *
 * @param array   $exp			Regular expression
 *
 * @return bool
 */
function check_regexp ($field, $exp) {
    if(preg_match($exp, $field)) {
        return true;
    } else {
        return false;
    }
}

/**
 * Check lengh of a string, is greater than or equal
 *
 * @param string  $field		Search string
 *
 * @param string  $start		Length parameter
 *
 * @return bool
 */
function check_length_ge ($field, $lenght) {
    if(strlen($field) >= $lenght) {
        return true;
    } else {
        return false;
    }
}

/**
 * Check lengh of a string, is less than or equal
 *
 * @param string  $field		Search string
 *
 * @param string  $start		Length parameter
 *
 * @return bool
 */
function check_length_le ($field, $lenght) {
    if(strlen($field) <= $lenght) {
        return true;
    } else {
        return false;
    }
}

/**
 * Check lengh of a string, is equal
 *
 * @param string  $field		Search string
 *
 * @param string  $start		Length parameter
 *
 * @return bool
 */
function check_length_e ($field, $lenght) {
    if(strlen($field) == $lenght) {
        return true;
    } else {
        return false;
    }
}
?>
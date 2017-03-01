<?php
//----------------------------------------------------------------------
// Copyright (c) 2017 Raytheon BBN Technologies
//
// Permission is hereby granted, free of charge, to any person obtaining
// a copy of this software and/or hardware specification (the "Work") to
// deal in the Work without restriction, including without limitation the
// rights to use, copy, modify, merge, publish, distribute, sublicense,
// and/or sell copies of the Work, and to permit persons to whom the Work
// is furnished to do so, subject to the following conditions:
//
// The above copyright notice and this permission notice shall be
// included in all copies or substantial portions of the Work.
//
// THE WORK IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
// OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
// MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
// NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
// HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
// WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE WORK OR THE USE OR OTHER DEALINGS
// IN THE WORK.
//----------------------------------------------------------------------

require_once('ldap_utils.php');
require_once('ssha.php');

$KEY_USER = 'user';
$KEY_PASS = 'pass';

$user = null;
$pass = null;

if (array_key_exists($KEY_USER, $_REQUEST)) {
        $user = $_REQUEST[$KEY_USER];
} else {
        header('X-PHP-Response-Code: 400', true, 400);
        exit;
}

if (array_key_exists($KEY_PASS, $_REQUEST)) {
        $pass = $_REQUEST[$KEY_PASS];
} else {
        header('X-PHP-Response-Code: 400', true, 400);
        exit;
}

$ldapconn = ldap_setup();
if ($ldapconn === -1) {
        error_log("LDAP Connection Failed");
        header('X-PHP-Response-Code: 500', true, 500);
        exit;
}

$filter = "(uid=$user)";
$result = ldap_search($ldapconn, $base_dn, $filter);
$entries = ldap_get_entries($ldapconn, $result);
if ($entries["count"] == 0) {
        // No user found
        header('X-PHP-Response-Code: 400', true, 400);
        exit;
}

$user_entry = $entries[0];
$ldap_password = $user_entry["userpassword"][0];
$verified = SSHA::verifyPassword($pass, $ldap_password);
if ($verified === FALSE) {
        header('X-PHP-Response-Code: 400', true, 400);
        exit;
}
?>

<?php
//----------------------------------------------------------------------
// Copyright (c) 2015 Raytheon BBN Technologies
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
require_once('db_utils.php');
include_once('/etc/geni-ar/settings.php');

// See if we should actually let user who clicked this link change the password
function validate_passwdchange() {
    if (array_key_exists('n', $_REQUEST) && array_key_exists('id', $_REQUEST)) {
        $nonce = $_REQUEST['n'];
        $db_id = $_REQUEST['id'];

        $db_conn = db_conn();

        $sql = "SELECT * from idp_passwd_reset "
        . "where id = " . $db_conn->quote($db_id, 'text')
        . " and nonce = " . $db_conn->quote($nonce, 'text');

        $db_result = db_fetch_row($sql, "get idp_passwd_reset");

        if ($db_result[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) {
            $result = $db_result[RESPONSE_ARGUMENT::VALUE];
            print count($result);
            return true;
        } else {
            error_log("Error getting password reset record: "
                      . $db_result[RESPONSE_ARGUMENT::OUTPUT]);
            return false;
        }
    } else {
        error_log("Failed to get password page because bad url");
        return false; 
    }
}

?>

<!DOCTYPE html>
<html>
<head>
<title>GENI: Reset Password</title>
<link type="text/css" href="kmtool.css" rel="Stylesheet"/>
<style type="text/css">
label.input {font-weight:bold;}
span.required {color:red;}
</style>
</head>
<body>

<?php 
if(validate_passwdchange()) { // print the form for them to actually change their password
    print "<h1>Enter your new password</h1>";
    print "<p><label class='input'>New Password:<span class='required'>*</span>";
    print "<input name='password1' type='password' size='50' required onchange='form.password2.pattern = this.value;'></label></p>";
    print "<p><label class='input'>Confirm New password:<span class='required'>*</span>";
    print "<input name='password2' type='password' size='50' required title='The passwords must match'></label></p>";
} else {
    print "<h1>Invalid request to password change page</h1>";
}

?>

</body>
</html>

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
require_once('ssha.php');
require_once('ar_constants.php');

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
            return count($result) > 0;
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

// once password is changed, delete the entry from idp_passwd_reset
function delete_reset($id, $nonce) {
    $db_conn = db_conn();
    $sql = "delete from idp_passwd_reset";
    $sql .= " where id = ";
    $result = db_execute_statement($sql);
    if ($result[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) {
        $rows = $result[RESPONSE_ARGUMENT::VALUE];
        error_log("Deleted $rows old records");
    } else {
        error_log("Error deleting old records: "
                  . $result[RESPONSE_ARGUMENT::OUTPUT]);
    }
    return $result == RESPONSE_ERROR::NONE;
}

function change_passwd() {
    print_r($_REQUEST);
    if (array_key_exists('n', $_REQUEST) && array_key_exists('id', $_REQUEST)
        && array_key_exists('password1', $_REQUEST) && array_key_exists('password2', $_REQUEST) 
        && array_key_exists('email', $_REQUEST)) {
        $nonce = $_REQUEST['n'];
        $db_id = $_REQUEST['id'];
        $email = $_REQUEST['email'];
        $password = $_REQUEST['password1'];
        $password2 = $_REQUEST['password2'];
        if ($password == $password2) {
            if(validate_passwdchange()) {
                $db_conn = db_conn();
                $sql = "SELECT * from idp_account_request where email=" . $db_conn->quote($email, 'text') 
                     . " and (request_state='APPROVED')"; // ??
                $db_result = db_fetch_rows($sql, "fetch accounts with that email");
                if ($db_result[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) {
                    $result = $db_result[RESPONSE_ARGUMENT::VALUE];
                } else {
                    error_log("Error getting password reset record: "
                              . $db_result[RESPONSE_ARGUMENT::OUTPUT]);
                    return false;
                }
                print_r($result);
                if (count($result) == 1) {
                    $pw_hash = SSHA::newHash($password);
                    $id = $result[0]['id'];
                    $sql = "UPDATE idp_account_request SET password_hash='" . $pw_hash . "' where id='" . $id . "'";
                    $db_result = db_execute_statement($sql, "update user password");
                    if ($db_result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
                        error_log ("Database action failed.  Could not change request status for password change request for " . $uid);
                        return false;
                    } else {
                        send_confirmation_email($email);
                        delete_reset($db_id, $nonce);
                        return true;
                    }
                } else {
                    print("Error retrieving account");
                    error_log("Error retrieving account");
                    return false;
                }
            }
        } else {
            error_log("Non matching passwords passed");
            return false;
        }
    } else {
        error_log("Failed to get password page because bad url");
        return false; 
    }
}

function send_confirmation_email($email) {
    global $AR_EMAIL_HEADERS;
    // Send an email with the link
    $subject = "GENI Password Reset Confirmation";
    $body  = "Your GENI Password has been successfully changed. \n"
          .  "If you did not request this change please contact "
          .  "the GENI Project Office immediately at help@geni.net.\n"
          .  "\n"
          . "Thank you,\n"
          . "GENI Operations\n";
    $headers = $AR_EMAIL_HEADERS;
    $headers .= "Cc: $idp_approval_email";
    mail($email, $subject, $body, $headers);
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

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(change_passwd()) {
        print "<h1>Password successfully changed</h1>";
    } else {
        print "<h1>Failed to change password</h1>";
    }
} else {
    if(validate_passwdchange()) { // print the form for them to actually change their password
        print "<h1>Enter your new password</h1>";
        print "<form action='newpasswd.php' method='POST'>";
        print "<p><label>Email:<span class='required'>*</span>";
        print "<input name='email' size='50' required></label></p>";
        print "<p><label>New Password:<span class='required'>*</span>";
        print "<input name='password1' type='password' size='50' required onchange='form.password2.pattern = this.value;'></label></p>";
        print "<p><label>Confirm New password:<span class='required'>*</span>";
        print "<input name='password2' type='password' size='50' required title='The passwords must match'></label></p>";
        print "<input type='hidden' name='n' value='{$_REQUEST['n']}'/>";
        print "<input type='hidden' name='id' value='{$_REQUEST['id']}'/>";
        print "<input type='submit'/>";
        print "</form>";
    } else {
        print "<h1>Invalid request to password change page</h1>";
    }
}
?>

</body>
</html>

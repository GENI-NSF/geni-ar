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
require_once('log_actions.php');

// See if we should actually let user who clicked this link change the password
// Returns user's email if valid, "" otherwise.
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
            return $result['email'];
        } else {
            error_log("Error getting password reset record: "
                      . $db_result[RESPONSE_ARGUMENT::OUTPUT]);
            return "";
        }
    } else {
        error_log("Failed to get password page because bad url");
        return ""; 
    }
}

// Once password is changed, delete the entry from idp_passwd_reset
function delete_reset($id, $nonce) {
    $db_conn = db_conn();
    $sql = "delete from idp_passwd_reset"
         . " where id = " . $db_conn->quote($id, 'text')
         . " and nonce = " . $db_conn->quote($nonce, 'text');
    $result = db_execute_statement($sql);
    if ($result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
        error_log("Error deleting old records: "
                  . $result[RESPONSE_ARGUMENT::OUTPUT]);
    }
    return $result == RESPONSE_ERROR::NONE;
}

function change_passwd() {
    global $base_dn;
    if (array_key_exists('n', $_REQUEST) && array_key_exists('id', $_REQUEST)
        && array_key_exists('password1', $_REQUEST) && array_key_exists('password2', $_REQUEST)) {
        $nonce = $_REQUEST['n'];
        $db_id = $_REQUEST['id'];
        $password = $_REQUEST['password1'];
        $password2 = $_REQUEST['password2'];
        if ($password == $password2) {
            $email = validate_passwdchange();
            if($email) {
                $db_conn = db_conn();
                $sql = "SELECT * from idp_account_request where email=" . $db_conn->quote($email, 'text') 
                     . " and (request_state='APPROVED')"; // ??
                $db_result = db_fetch_rows($sql, "fetch accounts with that email $email");
                if ($db_result[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) {
                    $result = $db_result[RESPONSE_ARGUMENT::VALUE];
                } else {
                    error_log("Error getting password reset record: "
                              . $db_result[RESPONSE_ARGUMENT::OUTPUT]);
                    return false;
                }
                if (count($result) == 1) {
                    $pw_hash = SSHA::newHash($password);
                    $id = $result[0]['id'];
                    $uid = $result[0]['username_requested'];
                    $ldapconn = ldap_setup();
                    if ($ldapconn === -1) {
                        error_log("LDAP Connection Failed");
                        return false;
                    }
                    if (ldap_check_account($ldapconn, $uid) == false) {
                        error_log("Cannot change password for uid=" . $uid . ". Account does not exist.");
                        return false;
                    } 
                    $res = add_log($uid, "Passwd Changed");
                    if ($res != 0) {
                        error_log("Logging failed.  Will not change request status.");
                        return false;
                    } 
                    $filter = "(uidNumber=" . $id . ")";
                    $result = ldap_search($ldapconn, $base_dn, $filter);
                    $entry = ldap_first_entry($ldapconn, $result);

                    $dn = ldap_get_dn($ldapconn, $entry);
                    $newattrs['userPassword'] = $pw_hash;
                    $ret = ldap_modify($ldapconn, $dn, $newattrs);
                    if ($ret === false) {
                        print("ERROR: Failed to change password for ldap account for " . $uid);
                        return false;
                    } 
                    $sql = "UPDATE idp_account_request SET password_hash='" . $pw_hash . "' where id='" . $id . "'";
                    $db_result = db_execute_statement($sql, "update user password");
                    if ($db_result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
                        error_log ("Database action failed.  Could not change request status for password change request for " . $uid);
                        return false;
                    } else {
                        send_admin_email($email);
                        delete_reset($db_id, $nonce);
                        return true;
                    }
                } else {
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

// Alert admins that request has been successfully completed.
// This should probabably be removed after new auto confirmation system deemed working
function send_admin_email($email) {
  global $AR_EMAIL_HEADERS, $idp_approval_email, $acct_manager_url;
  $server_host = $_SERVER['SERVER_NAME'];
  $subject = "New GENI Identity Provider Password Change on $server_host";
  $body = 'A password change has been submitted by user with email $email on host ';
  $body .= "$server_host.\n\n";
  $headers = $AR_EMAIL_HEADERS;
  mail($idp_approval_email, $subject, $body, $headers);
}

// Tell admins that password reset failed
function send_admin_error_email() {
  global $AR_EMAIL_HEADERS, $idp_approval_email, $acct_manager_url;
  $server_host = $_SERVER['SERVER_NAME'];
  $subject = "New GENI Identity Provider Password Change FAILED on $server_host";
  $body = 'A password change submitted by user with email $email on host ';
  $body .= "$server_host failed. Check logs for more information\n\n";
  $headers = $AR_EMAIL_HEADERS;
  mail($idp_approval_email, $subject, $body, $headers);
}

?>

<!DOCTYPE html>
<html>
<head>
<title>GENI: Reset Password</title>
<link href="https://fonts.googleapis.com/css?family=Open+Sans:400,700" rel="stylesheet" type="text/css">
<link type="text/css" href="kmtool.css" rel="Stylesheet"/>
</head>
<body>
<div id="content">
<a id='geni_logo' href="http://www.geni.net" target="_blank">
<img src="geni.png" width="88" height="75" alt="GENI"/>
</a>

<?php 

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(change_passwd()) {
        print "<h2>Password successfully changed</h2>";
        print "<a href='https://portal.geni.net/secure/home.php'>Login to GENI</a>";
    } else {
        // Todo: Tell them to email admins?
        print "<h2>Failed to change password</h2>";
        send_admin_error_email();
    }
} else {
    if(validate_passwdchange()) { // print the form for them to actually change their password
        print "<h2>Enter your new password</h2>";
        print "<form action='newpasswd.php' method='POST'>";
        print "<p><label>New Password:<span class='required'>*</span>";
        print "<input name='password1' type='password' size='30' required onchange='form.password2.pattern = this.value;'></label></p>";
        print "<p><label>Confirm new password:<span class='required'>*</span>";
        print "<input name='password2' type='password' size='30' required title='The passwords must match'></label></p>";
        print "<input type='hidden' name='n' value='{$_REQUEST['n']}'/>";
        print "<input type='hidden' name='id' value='{$_REQUEST['id']}'/>";
        print "<input type='submit'/>";
        print "</form>";
    } else {
        print "<h2>Invalid request to password change page</h2>";
    }
}
?>

</div>

<div id="footer">
Need help? Questions? Email 
<a href="mailto:help@geni.net">GENI Help
help@geni.net</a>.
<br>
<a href="http://www.geni.net/">GENI</a>
is sponsored by the
<a href="http://www.nsf.gov/">
  <img src="https://www.nsf.gov/images/logos/nsf1.gif"
       alt="NSF Logo" height="25" width="25">
  National Science Foundation
</a>
</div>
</body>
</html>

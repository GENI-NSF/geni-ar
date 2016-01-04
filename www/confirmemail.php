<?php
//----------------------------------------------------------------------
// Copyright (c) 2016 Raytheon BBN Technologies
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

require_once('db_utils.php');
include_once('/etc/geni-ar/settings.php');
require_once('institutions.php');
require_once('ar_constants.php');

// Returns user's email if valid, "" otherwise.
function confirm_email() {
    if (array_key_exists('n', $_REQUEST) && array_key_exists('id', $_REQUEST)) {
        $nonce = $_REQUEST['n'];
        $db_id = $_REQUEST['id'];

        $db_conn = db_conn();

        $sql = "SELECT * from idp_email_confirm "
        . "where id = " . $db_conn->quote($db_id, 'text')
        . " and nonce = " . $db_conn->quote($nonce, 'text');

        $db_result = db_fetch_row($sql, "get idp_email_confirm");

        if ($db_result[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) {
            $result = $db_result[RESPONSE_ARGUMENT::VALUE];
            $email = $result['email'];
            // actually do the email confirming 
            // todo: ask tom stuff about what states are, about whitelist and blacklist
            $new_state = "";
            $institution = get_domain($email);

            if (array_key_exists($institution, $INSTITUTIONS)) {
                if ($INSTITUTIONS[$institution] == "allow") {
                    $new_state = "APPROVED";
                    // send congrats email!?
                    // also do an ldap add in this situation
                }
            } else {
                $new_state = "CONFIRM_REQUESTER";
            }

            $sql = "UPDATE idp_account_request SET request_state='$new_state'"
                 . " where email = " . $db_conn->quote($email, 'text')
                 . " and (request_state='REQUESTED')";
            $update_result = db_execute_statement($sql);
            if ($update_result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
                return "";
            }
            delete_reset($db_id, $nonce);
            return $email;
        } else {
            error_log("Error getting email confirm record: " . $db_result[RESPONSE_ARGUMENT::OUTPUT]);
            return "";
        }
    } else {
        error_log("Failed to confirm email because bad url");
        return ""; 
    }
}

// returns the domain from $email, returns "" if not an email address
function get_domain($email) {
    $tmp = explode("@", $email);
    if(count($tmp) != 2) {
        return "";
    } else {
        return $tmp[1];
    }
}

// once email is confirmed, delete the entry from idp_email_confirm
// not clear what to do if there's a DB error here
function delete_reset($id, $nonce) {
    $db_conn = db_conn();
    $sql = "delete from idp_email_confirm"
         . " where id = " . $db_conn->quote($id, 'text')
         . " and nonce = " . $db_conn->quote($nonce, 'text');
    $result = db_execute_statement($sql);
    if ($result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
        error_log("Error deleting old records: "
                  . $result[RESPONSE_ARGUMENT::OUTPUT]);
    }
    return $result == RESPONSE_ERROR::NONE;
}

function send_success_email($email) {
    global $AR_EMAIL_HEADERS, $idp_approval_email;
    $subject = "GENI Email Confirmation";
    $body = "The email you used to sign up for GENI has been successfully confirmed. \n"
          . "You should hear from us in a few days regarding the status of your new account"
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
<link href="https://fonts.googleapis.com/css?family=Open+Sans:400,700" rel="stylesheet" type="text/css">
<link type="text/css" href="kmtool.css" rel="Stylesheet"/>
</head>
<body>
<div id="content">
<a id='geni_logo' href="http://www.geni.net" target="_blank">
<img src="geni.png" width="88" height="75" alt="GENI"/>
</a>

<?php 
$email = confirm_email();

if($email != "") { 
    print "<h2>Email address $email successfully confrimed</h2>";
    print "<p>You should hear from us in a few days regarding the status of your new account</p>";
} else {
    print "<h2>Invalid request to password change page</h2>";
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

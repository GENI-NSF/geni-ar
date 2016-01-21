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

require_once('ldap_utils.php');
require_once('db_utils.php');
include_once('/etc/geni-ar/settings.php');
require_once('ar_constants.php');

// Return username associated with $email, "" if no user found.
function retrieve_username($email) {
    $db_conn = db_conn();
    $sql = "SELECT * from idp_account_request where "
         . "email = " . $db_conn->quote($email, "text")
         . " and request_state = 'APPROVED'";
    $db_result = db_fetch_row($sql, "get from idp_account_request");

    if ($db_result[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) {
        $result = $db_result[RESPONSE_ARGUMENT::VALUE];
        if (count($result) == 0) {
            return "";
        } else {
            return $result["username_requested"];
        }
    } else {
        error_log("Error retrieving account: "
                  . $db_result[RESPONSE_ARGUMENT::OUTPUT]);
        return "";
    }
}

// Send an email with the link for user to click to change password
function send_username_recover_email($user_email, $username) {
    global $AR_EMAIL_HEADERS, $idp_approval_email;
    $subject = "GENI IDP Username";
    $body  = "The username associated with your GENI account is $username.\n\n"
           . "Thank you,\n"
           . "GENI Operations\n";
    $headers = $AR_EMAIL_HEADERS;
    $headers .= "Cc: $idp_approval_email";
    mail($user_email, $subject, $body, $headers);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>GENI: Recover Username</title>
<link href="https://fonts.googleapis.com/css?family=Open+Sans:400,700" rel="stylesheet" type="text/css">
<link type="text/css" href="kmtool.css" rel="Stylesheet"/>
</head>
<body>
<div id="content">
<a id='geni_logo' href="http://www.geni.net" target="_blank">
  <img src="geni.png" width="88" height="75" alt="GENI"/>
</a>

<?php
$error = "";

if (!array_key_exists("email", $_REQUEST)) {
    print "No email given";
} else {
    $email = $_REQUEST["email"];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address given";
    } else {
        $username = retrieve_username($email);
        if ($username != "") {
            print "<h2>Success</h2>";
            print "<p>Your username has been sent to you. ";
            print "If you do not recieve an email within 24 hours, please email ";
            print "<a href='mailto:help@geni.net'>help@geni.net</a>.";
            send_username_recover_email($email, $username);
        } else {
            $error = "Account for email $email could not be found.";
        }
    }
}

if ($error != "") {
    print "<h2>Error</h2>";
    print "<p>$error</p>";
    print "<a href='/geni/usernamerecover.html' class='button'>Back</a>";
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

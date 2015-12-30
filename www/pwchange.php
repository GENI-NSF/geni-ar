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
require_once('ar_constants.php');


// Generate a random string (nums, uppercase, lowercase) of width $width
function random_id($width=6) {
    $result = '';
    for ($i=0; $i < $width; $i++) { 
        $result .= base_convert(strval(rand(0, 35)), 10, 36);
    }
    return strtoupper($result);
}

// make a new /newpasswd.php?id=XXX&n=YYY link for use in new password emails
function create_newpasswd_link($base_path, $id1, $id2) {
    global $acct_manager_url;
    $base_url = parse_url($acct_manager_url);
    $path = dirname($base_path);
    $path .= "/newpasswd.php?id=$id1&n=$id2";
    $url = $base_url["scheme"] . "://" . $base_url["host"] . "$path";
    return $url;
}

// Insert the password change request into the idp_passwd_reset table
function insert_passwd_reset($email, $nonce) {
    $db_conn = db_conn();
    $sql = "insert into idp_passwd_reset (email, nonce) values (";
    $sql .= $db_conn->quote($email, 'text');
    $sql .= ', ';
    $sql .= $db_conn->quote($nonce, 'text');
    $sql .= ") returning id, created";

    $db_result = db_fetch_row($sql, "insert idp_passwd_reset");
    $result = false;
    if ($db_result[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) {
        $result = $db_result[RESPONSE_ARGUMENT::VALUE];
    } else {
        error_log("Error inserting password reset record: "
                  . $db_result[RESPONSE_ARGUMENT::OUTPUT]);
    }

    return $result;
}

// clear out requests older than $hours hours
function delete_expired_resets($hours) {
    $sql = "delete from idp_passwd_reset";
    $sql .= " where created <";
    $sql .= "  (now()  at time zone 'utc') - interval '$hours hours';";
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

function send_passwd_change_email($email, $change_url) {
    global $AR_EMAIL_HEADERS;
    // Send an email with the link
    $subject = "TESTING sending passwd reset link";
    $body  = "Please use the following link to reset your GENI account password \n"
          .  "$change_url\n"
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
$errors = array();
$EMAIL_KEY = 'email';
$success = false;
if (!array_key_exists($EMAIL_KEY, $_REQUEST)) {
    print "No email given";
} else {
    $email = $_REQUEST[$EMAIL_KEY];
    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email address";
    } else {
        $ldapconn = ldap_setup();
        if ($ldapconn === -1) {
            $errors[] = "Internal Error";
        } else {
            $known_email = ldap_check_email($ldapconn, $email);
            if (! $known_email) {
                $errors[] = "Unknown email address";
            } else {
                $success = true;
                // Enter a record in the database for this request
                delete_expired_resets(1);

                // Generate a link to be clicked
                $nonce = random_id(8);
                // TODO: what to do when this is an error
                $db_result = insert_passwd_reset($email, $nonce);
                $db_id = $db_result['id'];

                $change_url = create_newpasswd_link($_SERVER['PHP_SELF'],
                                                    $db_id, $nonce);
                send_passwd_change_email($email, $change_url);
                // TODO: better messages here probably
                print "<h1>An email to reset your password has been sent.</h1>";
                print "<p>If this was done by accident, simply ignore the email you receive</p>";
            }
        }
    }
}

if (!$success) {
    print "Could not reset password because: <ul>";
    foreach ($errors as $error) {
        print "<li>$error</li>";
    }
    print "</ul>";
}

?>
</body>
</html>

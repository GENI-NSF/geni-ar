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
require_once('email_utils.php');
require_once('log_actions.php');

function accept_user($id) {
    $db_conn = db_conn();
    $sql = "SELECT * from idp_account_request where id=" . $db_conn->quote($id, 'integer')
        . " and (request_state='EMAIL_CONFIRMED')";
    $db_result = db_fetch_rows($sql, "fetch accounts with id $id");
    if ($db_result[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) {
        $result = $db_result[RESPONSE_ARGUMENT::VALUE][0];
    } else {
        error_log("Error getting user record: " . $db_result[RESPONSE_ARGUMENT::OUTPUT]);
        return false;
    }
    $uid = $result['username_requested'];
    $user_email = $result['email'];
    $ldapconn = ldap_setup();
    if ($ldapconn === -1) {
        print("LDAP Connection Failed");
        return false;
    }
    if (ldap_check_account($ldapconn, $uid)) {
        error_log("Account for uid=" . $uid . " already exists.");
        return false;
    } else if (ldap_check_email($ldapconn, $user_email)) {
        error_log("Account with email address=" . $user_email . " already exists.");
        return false;
    } else {
        $res = add_log($uid, AR_ACTION::ACCOUNT_CREATED);
        if ($res != 0) {
            error_log("ERROR: Logging failed.  Will not create account for " . $uid);
        }
        $new_dn = get_userdn($uid);
        $attrs = make_ldap_attrs($result);
        $ret = ldap_add($ldapconn, $new_dn, $attrs);
        if ($ret === false) {
            error_log("ERROR: Failed to create new ldap account");
            add_log_comment($uid, AR_ACTION::ACCOUNT_CREATED, "FAILED");
            return false;
        }
        $sql = "UPDATE idp_account_request SET request_state='APPROVED', " 
             . "created_ts=now() at time zone 'utc' where id ='" . $id . '\'';
        $update_result = db_execute_statement($sql);

        if ($update_result[RESPONSE_ARGUMENT::CODE] != 0) {
            error_log("Error updating user record: " . $update_result[RESPONSE_ARGUMENT::OUTPUT]); 
        }
        send_admin_success_email($result);
        send_user_success_email($user_email, $result['first_name'],
                                $result['username_requested']);

        return true;
    } 
}

// Populates an attribute object for ldap_add
function make_ldap_attrs($row) {
    $id = $row['id'];
    $uid = $row['username_requested'];
    $attrs['objectClass'][] = "inetOrgPerson";
    $attrs['objectClass'][] = "eduPerson";
    $attrs['objectClass'][] = "posixAccount";
    $attrs['uid'] = $uid;
    $lastname = $row['last_name'];
    $attrs['sn'] = $lastname;
    $firstname = $row['first_name'];
    $attrs['givenName'] = $firstname;
    $fullname = $firstname . " " . $lastname;
    $attrs['cn'] = $fullname;
    $attrs['displayName'] = $fullname;
    $attrs['userPassword'] = $row['password_hash'];
    $user_email = $row['email'];
    $attrs['mail'] = $user_email;
    $attrs['eduPersonAffiliation'][] = "member";
    $attrs['eduPersonAffiliation'] []= "staff";
    $attrs['telephoneNumber'] = $row['phone'];
    $org = $row['organization'];
    $attrs['o'] = $org;
    $attrs['uidNumber'] = $id;
    $attrs['gidNumber'] = $id;
    $attrs['homeDirectory'] = "";

    return $attrs;
}

function send_user_success_email($user_email, $firstname, $uid) {
    global $AR_EMAIL_HEADERS;
    $filetext = EMAIL_TEMPLATE::load(EMAIL_TEMPLATE::NOTIFICATION);
    $filetext = str_replace("EXPERIMENTER_NAME_HERE", $firstname, $filetext);
    $filetext = str_replace("USER_NAME_GOES_HERE", $uid, $filetext);
    $headers = $AR_EMAIL_HEADERS;
    mail($user_email, "GENI Identity Provider Account Created", $filetext, $headers);
}

function send_admin_success_email($row) {
    global $AR_EMAIL_HEADERS, $idp_audit_email, $acct_manager_url;
    $uid = $row['username_requested'];
    $subject = "New GENI Identity Provider Account Created";
    $body = 'A new GENI Identity Provider account has been created for ';
    $body .= "$uid. Account was approved by " . $_SERVER['PHP_AUTH_USER'] . ".\n\n";
    $email_vars = array('first_name', 'last_name', 'email', 'organization', 'title', 'reason');
    foreach ($email_vars as $var) {
        $val = $row[$var];
        $body .= "$var: $val\n";
    }
    $body .= "\nSee table idp_account_request for complete details.\n";
    $headers = $AR_EMAIL_HEADERS;
    mail($idp_audit_email, $subject, $body, $headers);
}

function print_error($message) {
    print "<h2>Error:</h2>";
    print "<p>$message</p>";
}   

?>

<!DOCTYPE html>
<html>
<head>
<title>GENI: Reset Password</title>
<link href="https://fonts.googleapis.com/css?family=Open+Sans:400,700" rel="stylesheet" type="text/css">
<link type="text/css" href="geni-ar.css" rel="Stylesheet"/>
</head>
<body>
<div id="content" class='card' style="width:500px; margin: 30px auto">

<?php

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (array_key_exists('id', $_REQUEST)) {
        $id = $_REQUEST['id'];
        $db_conn = db_conn();

        $sql = "SELECT * from idp_account_request "
        . "where id = " . $db_conn->quote($id, 'integer')
        . " and (request_state='EMAIL_CONFIRMED')";

        $db_result = db_fetch_row($sql, "get idp_account_request");
        if ($db_result[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) {
            $result = $db_result[RESPONSE_ARGUMENT::VALUE];
            if (count($result) > 0) {
                print "<h2>Approve User</h2>";
                print "<p>Do you want to approve this user?</p>";
                $vars = array('first_name', 'last_name', 'email', 'organization', 'title', 'reason');
                foreach ($vars as $var) {
                    $val = $result[$var];
                    print "$var: $val<br>";
                }
                print "<form action='approve.php' method='POST'>";
                print "<input type='hidden' name='id' value='$id'/>";
                print "<input type='submit' value='Approve'/>";
                print "</form>";
            } else {
                print_error("No confirmed requests with this id found.");
            }
        } else {
            print_error("Couldn't retrieve user info from db.");
        }
    } else {
        print_error("No id given in URL.");
    }
} else if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (array_key_exists('id', $_REQUEST)) {
        if (accept_user($_REQUEST['id'])) {
            print "<h2>Success</h2>";
            print "<p>User succesfully added.</p>";
        } else {
            print_error("Try again or check the logs.");
        }
    } else {
        print_error("No id given in request.");
    }
}



?>

</div>

</body>
</html>

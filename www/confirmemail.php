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
require_once('ar_constants.php');
require_once('log_actions.php');
require_once('email_utils.php');

// Returns user's email if confirmation link was valid, "" otherwise.
function confirm_email($nonce, $db_id) {
    $db_conn = db_conn();
    $sql = "SELECT * from idp_email_confirm "
    . "where id =" . $db_conn->quote($db_id, 'integer')
    . " and nonce =" . $db_conn->quote($nonce, 'text');
    $db_result = db_fetch_row($sql, "get idp_email_confirm");
    if ($db_result[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) {
        $result = $db_result[RESPONSE_ARGUMENT::VALUE];
	if (is_null($result)) {
	  error_log("Found no email confirmation record for id $db_id, nonce $nonce");
          return array("", null);
	}
        $email = $result['email'];

	// Note: We could include the specific request ID as an column in idp_email_confirm,
	// and use that to ensure we update the correct row here.
	// However, there can be only 1 account with given email address awaiting
	// confirmation, so this is not necessary

	// Old style started requests as REQUESTED. Now start as CONFIRM. Cover both,
	// so when this code is applied accounts awaiting confirmation are covered.
	// FIXME: Could we do some heuristic on request_ts to ensure we get the right row here?
        $sql2 = "UPDATE idp_account_request SET request_state='" . AR_STATE::EMAIL_CONF . "'"
             . " where email = " . $db_conn->quote($email, 'text')
	  . " and (request_state='" . AR_STATE::REQUESTED . "' or request_state='" . AR_STATE::CONFIRM . "')"
	  . "RETURNING id";
        $update_result = db_fetch_row($sql2, "update idp_account_request confirm email");
        if ($update_result[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) {
            delete_confirmation($db_id, $nonce);
	    $acctreq_id = $update_result[RESPONSE_ARGUMENT::VALUE]['id'];
	    return array($email, $acctreq_id);
        } else {
            error_log("Failed to update user account status: " .
                      $update_result[RESPONSE_ARGUMENT::OUTPUT]);
            return array($email, null);
        }
    } else {
        error_log("Error getting email confirm record: "
                 . $db_result[RESPONSE_ARGUMENT::OUTPUT]);
        return array("", null);
    }
}

// Return true if $user_email should automatically get account, false otherwise
function check_whitelist($user_email) {
    $db_conn = db_conn();
    $institution = get_domain($user_email);
    $sql = "SELECT * from idp_whitelist "
    . "where institution=" . $db_conn->quote($institution, 'text');
    $result = db_fetch_row($sql, "get from idp_whitelist");

    if ($result[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) {
        return count($result[RESPONSE_ARGUMENT::VALUE]) != 0;
    } else {
        error_log("Failed to lookup in idp_whitelist: "
                  . $result[RESPONSE_ARGUMENT::OUTPUT]);
        return false;
    }
}

function accept_user($user_email, $acctreq_id) {
    $db_conn = db_conn();
    $sql = "SELECT * from idp_account_request where email=" . $db_conn->quote($user_email, 'text')
      . " and (request_state='" . AR_STATE::EMAIL_CONF . "')";
    if (! is_null($acctreq_id)) {
      $sql = $sql . " and id =" . $db_conn->quote($acctreq_id, 'integer');
    }
    $db_result = db_fetch_rows($sql, "fetch accounts with that email $user_email");
    if ($db_result[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) {
        $result = $db_result[RESPONSE_ARGUMENT::VALUE][0];
    } else {
        error_log("Error getting user record: "
                  . $db_result[RESPONSE_ARGUMENT::OUTPUT]);
        return false;
    }

    $id = $result['id'];
    $uid = $result['username_requested'];
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

        $sql = "UPDATE idp_account_request SET request_state='" . AR_STATE::APPROVED . "', "
             . "created_ts=now() at time zone 'utc' where id ='" . $id . '\'';
        $update_result = db_execute_statement($sql);

        if ($update_result[RESPONSE_ARGUMENT::CODE] != 0) {
            error_log("Error updating user record: "
                      . $update_result[RESPONSE_ARGUMENT::OUTPUT]);
        }

        send_admin_success_email($result);
        send_user_success_email($user_email, $result['username_requested'], $result['first_name']);

        return true;
    }
}

function send_user_success_email($user_email, $uid, $firstname) {
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
    $email = $row['email'];
    $institution = get_domain($email);
    $subject = "New GENI Identity Provider Account Created";
    $body = 'A new GENI Identity Provider account has been created for ';
    $body .= "$uid. Account was automatically approved as user email was from $institution \n\n";
    $email_vars = array('first_name', 'last_name', 'email', 'organization', 'title', 'reason');
    foreach ($email_vars as $var) {
        $val = $row[$var];
        $body .= "$var: $val\n";
    }
    $body .= "\nSee table idp_account_request for complete details.\n";
    $headers = $AR_EMAIL_HEADERS;
    mail($idp_audit_email, $subject, $body, $headers);
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

// returns the domain from $email, returns "" if not an email address
function get_domain($email) {
    $tmp = explode("@", $email);
    if(count($tmp) != 2) {
        return "";
    } else {
        return $tmp[1];
    }
}

// Once email is confirmed, delete the entry from idp_email_confirm
function delete_confirmation($id, $nonce) {
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

// Email admins about new request
function send_admin_confirmation_email($user_email, $acctreq_id) {
    global $AR_EMAIL_HEADERS, $idp_approval_email, $acct_manager_url;
    $server_host = $_SERVER['SERVER_NAME'];
    $subject = "New GENI Identity Provider Account Request on $server_host";
    $body = "A new IdP account request for user with email $user_email has been submitted ";
    $body .= "and email confirmed on host ";
    $body .= "$server_host.\n\n";

    $db_conn = db_conn();
    $sql = "SELECT * from idp_account_request where email=" . $db_conn->quote($user_email, 'text');
    if (! is_null($acctreq_id)) {
      $sql = $sql  . " and id = " . $db_conn->quote($acctreq_id, 'integer');
    }

    $db_result = db_fetch_rows($sql, "fetch accounts with that email $user_email");
    if ($db_result[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE && ! is_null($db_result[RESPONSE_ARGUMENT::VALUE]) && count($db_result[RESPONSE_ARGUMENT::VALUE]) == 1) {
        $result = $db_result[RESPONSE_ARGUMENT::VALUE][0];
        $email_vars = array('first_name', 'last_name', 'email', 'organization', 'title', 'reason');
        foreach ($email_vars as $var) {
            $val = $result[$var];
            $body .= "$var: $val\n";
        }
        $id = $result['id'];
        $body .= "\nSee $acct_manager_url" . "/approve.php?id=$id to approve this request.\n";
    } else {
        error_log("Error getting user record for email $user_email, reqid $acctreq_id: " . $db_result[RESPONSE_ARGUMENT::OUTPUT]);
    }

    $body .= "\nSee $acct_manager_url" . "/display_requests.php to handle this request.\n";

    $headers = $AR_EMAIL_HEADERS;
    mail($idp_approval_email, $subject, $body, $headers);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>GENI: Confirm Email</title>
<link href="https://fonts.googleapis.com/css?family=Open+Sans:400,700" rel="stylesheet" type="text/css">
<link type="text/css" href="kmtool.css" rel="Stylesheet"/>
</head>
<body>
<div id="content">
<a id='geni_logo' href="http://www.geni.net" target="_blank">
<img src="geni.png" width="88" height="75" alt="GENI"/>
</a>

<?php
if (array_key_exists('n', $_REQUEST) && array_key_exists('id', $_REQUEST)) {
    $nonce = $_REQUEST['n'];
    $db_id = $_REQUEST['id'];

    list($email, $acctreq_id) = confirm_email($nonce, $db_id);

    if($email != "") {
        if (check_whitelist($email)) {
	  if (accept_user($email, $acctreq_id)) {
                print "<h2>Account successfully created</h2>";
                print "<a href='https://portal.geni.net'>Login to GENI</a>";
            } else {
	        send_admin_confirmation_email($email, $acctreq_id);
                print "<h2>Email address $email successfully confirmed</h2>";
                print "<p>You should hear from us in a few days regarding the status of your new account.</p>";
            }
        } else {
	    send_admin_confirmation_email($email, $acctreq_id);
            print "<h2>Email address $email successfully confirmed</h2>";
            print "<p>You should hear from us in a few days regarding the status of your new account.</p>";
        }
    } else {
        print "<h2>Error</h2>";
        print "<p>Could not confirm email. ";
        print "Please contact <a href='mailto:help@geni.net'>help@geni.net</a>.<br><br>";
        print "Note: if you have already confirmed your email,";
        print " it may take several days for your account to be approved.</p>";
    }
} else {
    print "<h2>Error</h2>";
    print "<p>Couldn't confirm email because bad url given.</p>";
}


?>

</div>

<div id="footer">
Need help? Questions? Email
<a href="mailto:help@geni.net">GENI Help</a>.
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

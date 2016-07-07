<?php
//----------------------------------------------------------------------
// Copyright (c) 2012-2016 Raytheon BBN Technologies
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
require_once('log_actions.php');
require_once('ldap_utils.php');
require_once('response_format.php');
require_once('ssha.php');
require_once('ar_constants.php');
include_once('/etc/geni-ar/settings.php');

// Generate a random string (nums, uppercase, lowercase) of width $width
function random_id($width=6) {
    $result = '';
    for ($i=0; $i < $width; $i++) { 
        $result .= base_convert(strval(rand(0, 35)), 10, 36);
    }
    return strtoupper($result);
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

// make a new /confirmemail.php?id=XXX&n=YYY link for use in email confirmation email
function create_email_confirm_link($base_path, $id1, $id2) {
    global $acct_manager_url;
    $base_url = parse_url($acct_manager_url);
    $path = dirname($base_path);
    $path .= "/confirmemail.php?id=$id1&n=$id2";
    $url = $base_url["scheme"] . "://" . $base_url["host"] . "$path";
    return $url;
}

// Insert the password change request into the idp_email_confirm table
function insert_email_confirm($email, $nonce) {
    $db_conn = db_conn();
    $sql = "insert into idp_email_confirm (email, nonce) values (";
    $sql .= $db_conn->quote($email, 'text');
    $sql .= ', ';
    $sql .= $db_conn->quote($nonce, 'text');
    $sql .= ") returning id, created";

    $db_result = db_fetch_row($sql, "insert idp_email_confirm");
    $result = false;
    if ($db_result[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) {
        $result = $db_result[RESPONSE_ARGUMENT::VALUE];
    } else {
        error_log("Error inserting password reset record: "
                  . $db_result[RESPONSE_ARGUMENT::OUTPUT]);
    }
    return $result;
}

function get_user_conf_email_body($confirm_url) {
  $body = 'Thank you for requesting an Identity Provider account with GENI. ';
  $body .= "Please confirm your email address by clicking this link:\n\n $confirm_url \n\n";
  $body .= "You will be contacted if there are any questions about your request and notified when the account has been created.";
  $body .= "\n\n";
  $body .= "Thanks,\n";
  $body .= "GENI Operations\n";
  return $body;
}

// Email requester about their request
function send_user_confirmation_email($user_email, $confirm_url) {
  global $AR_EMAIL_HEADERS;
  $subject = "GENI Account Request Email Confirmation";
  $body = get_user_conf_email_body($confirm_url);
  $headers = $AR_EMAIL_HEADERS;
  return mail($user_email, $subject, $body, $headers);
}

function print_errors($errors) {
  print "<h2>Problems with request</h2>";
  print "<p>Please fix the following problems with your request:</p>";
  print "<ul>";
  foreach ($errors as $error) {
    print "<li>$error</li>";
  }
  print "</ul>";
  print "<a onclick='window.history.back();' class='button'>Back</a>";
}

$errors = array();

// We'll get 'username', but the column is 'username_requested'
if (array_key_exists('username', $_REQUEST) && $_REQUEST['username']) {
  $_REQUEST['username_requested'] = $_REQUEST['username'];
  unset($_REQUEST['username']);
} else {
  $errors[] = "No username specified.";
  // Do this to avoid an unnecessary message to the user
  // about 'username_requested' not specified.
  $_REQUEST['username_requested'] = ' ';
}

$p1 = null;
$p2 = null;
$email = null;
$ldapconn = ldap_setup();
if ($ldapconn === -1) {
  print "Failed to connect to ldap server";
  exit();
}

$uid = $_REQUEST['username_requested'];
$acct_exists = ldap_check_account($ldapconn,$uid);
  
//sanity checks - does username exist, does email exist
//is username properly formed (1-8 characters, lower case letters and numbers only)

// Todo: can we make it so that this gets checked before they submit?
if ($acct_exists) {
  $errors[] = "The username " . $uid . " is already in use.";
}

//check if there is already an account for this email
if (array_key_exists('email', $_REQUEST) && $_REQUEST['email']) {
  $email = $_REQUEST['email'];
  if (ldap_check_email($ldapconn, $email)) {
    $errors[] = "An account for this email address already exists.";
  }
}

// Get a database connection so that values can be quoted
$db_conn = db_conn();

//check if there is a pending request for this username
$sql = "SELECT * from idp_account_request where username_requested = ";
$sql .= $db_conn->quote($uid, 'text');
$result = db_fetch_rows($sql);
if ($result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
  print("Postgres database query failed");
  error_log("Postgres database query failed");
  exit();
}
if (count($result['value']) != 0) {
  foreach ($result['value'] as $row) {
    $state = $row['request_state'];
    if (   $state === AR_STATE::REQUESTED
	   or $state === AR_STATE::LEADS
	   or $state === AR_STATE::CONFIRM
	   or $state === AR_STATE::EMAIL_CONF
	   or $state === AR_STATE::DELETED) {
      $errors[] = "Username " . $uid . " already exists";
    }
    if ($state == AR_STATE::REQUESTER) {
      //get the request id
      $sql = "SELECT id from idp_account_request where username_requested='" . $uid . "' and (request_state='" . AR_STATE::REQUESTER . "')";
      $result = db_fetch_rows($sql);
      if ($result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
        print("Postgres database query failed");
        error_log("Postgres database query failed");
        exit();
      }
      if (count($result['value']) === 1) {
        $id = $result['value'][0]['id'];
      } else {
        print("Error retrieving account");
        error_log("Error retrieving account");
        exit();
      }
      // deny original request and submit this one
      $sql = "UPDATE idp_account_request SET request_state='" . AR_STATE::DENIED . "' where id='" . $id . '\'';
      $result = db_execute_statement($sql);
      if ($result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
        print ("Database action failed.  Could not change request status for password change request for" . $uid);
        error_log ("Database action failed.  Could not change request status for password change request for " . $uid);
        exit();
      }
    }
  }
}

//check if there is a pending request for this email
$sql = "SELECT * from idp_account_request where email='" . $email . '\'';
$result = db_fetch_rows($sql);
if ($result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
  print("Postgres database query failed");
  error_log("Postgres database query failed");
  exit();
}
if (count($result['value']) != 0) {
  foreach ($result['value'] as $row) {
    $state = $row['request_state'];
    if (   $state === AR_STATE::REQUESTED
	   or $state === AR_STATE::LEADS
	   or $state === AR_STATE::CONFIRM
	   or $state === AR_STATE::REQUESTER
	   or $state === AR_STATE::EMAIL_CONF) {
      $errors[] = "An account request for this email address is pending approval";
    }
  }
}

if (strlen($uid) > 8) {
  $errors[] = "Username cannot be longer than 8 characters.";
}
if (!preg_match('/^[a-z0-9]{1,8}$/', $uid)) {
  $errors[] = "Username must consist of lowercase letters and numbers only.";
}
  
if (array_key_exists('password1', $_REQUEST) && $_REQUEST['password1']) {
  $p1 = $_REQUEST['password1'];
} else {
  $errors[] = "No password specified.";
}
if (array_key_exists('password2', $_REQUEST) && $_REQUEST['password2']) {
  $p2 = $_REQUEST['password2'];
} else {
  $errors[] = "No confirm password specified.";
}
if ($p1 === $p2) {
  // Create the password_hash
  $pw_hash = SSHA::newHash($p1);
  $_REQUEST['password_hash'] = $pw_hash;
} else {
  $errors[] = "Passwords do not match.";
}

$required_vars = array('first_name', 'last_name', 'email', 'username_requested',
                       'phone', 'password_hash', 'organization', 'title', 'reason');

$optional_vars = array('url');

// Write database row
// Build the insert statement
$query_vars = array();
$query_values = array();

$conn = db_conn();

foreach ($required_vars as $name) {
  if (array_key_exists($name, $_REQUEST) && $_REQUEST[$name]) {
    $value = $_REQUEST[$name];
    $query_vars[] = $name;
    $query_values[] = $conn->quote(utf8_encode($value), 'text');
  } else {
    $pretty_name = str_replace("_", " ", $name);
    $errors[] = "No $pretty_name specified.";
  }
}

foreach ($optional_vars as $name) {
  if (array_key_exists($name, $_REQUEST)) {
    $value = $_REQUEST[$name];
    $query_vars[] = $name;
    $query_values[] = $conn->quote(utf8_encode($value), 'text');
  }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>GENI: Request an account</title>
<link href="https://fonts.googleapis.com/css?family=Open+Sans:400,700" rel="stylesheet" type="text/css">
<link type="text/css" href="kmtool.css" rel="Stylesheet"/>
</head>
<body>
  <div id="content">
    <a id='geni_logo' href="http://www.geni.net" target="_blank">
      <img src="geni.png" width="88" height="75" alt="GENI"/>
    </a>

<?php
if ($errors) {
  print_errors($errors);
} else {
  $sql = 'INSERT INTO idp_account_request (';
  $sql .= implode(',', $query_vars);
  $sql .= ') VALUES (';
  $sql .= implode (',', $query_values);
  $sql .= ')';
  $result = db_execute_statement($sql, 'insert idp account request');

  // An error occurred. First, log the query and result for debugging
  if ($result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
    error_log("DB Error query: $sql");
    error_log("DB Error result: " . $result[RESPONSE_ARGUMENT::OUTPUT]);
    // Next send an email about the error
    $headers = $AR_EMAIL_HEADERS;
    $server_host = $_SERVER['SERVER_NAME'];
    mail($idp_approval_email,
         "IdP Account Request Failure $server_host",
         "An error occurred on IdP account request. See log file for details.",
         $headers);

    print "<h2>Account request failed</h2>";
    print "<p> We are sorry, your account request failed. ";
    print "An email has been sent to the operators and they will be in touch with you shortly.</p>";
  } else {
    $nonce = random_id(8);
    $db_result = insert_email_confirm($email, $nonce);
    if ($db_result) {
      $db_id = $db_result['id'];
      $confirm_url = create_email_confirm_link($_SERVER['PHP_SELF'], $db_id, $nonce);
      if (send_user_confirmation_email($email, $confirm_url)) {
	// Change request state
	$sql = "UPDATE " . $AR_TABLENAME . " SET request_state='" . AR_STATE::CONFIRM . "' where request_state = '" . AR_STATE::REQUESTED . "' and email = " . $conn->quote(utf8_encode($email), 'text') . " and username_requested = " . $conn->quote(utf8_encode($uid), 'text');
	$result = db_execute_statement($sql);
	if ($result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
	  error_log("Failed to update request for $email / $uid to " . AR_STATE::CONFIRM . ": " . $result[RESPONSE_ARGUMENT::OUTPUT]);
	  // Keep going though
	}

	// Log in the DB that we did this

	// check for single quotes and backslashes in body before logging
	$email_body = get_user_conf_email_body($confirm_url);
	$email_body = str_replace("'","''",$email_body);
	$email_body = str_replace("\\","\\\\",$email_body);

	$res = add_log_with_comment($db_id, "Requested Confirmation",$email_body);
	if ($res != RESPONSE_ERROR::NONE) {
	  //try again without the comment
	  $res = add_log($db_id,"Requested Confirmation");
	  if ($res != RESPONSE_ERROR::NONE) {
	    error_log("Failed to log email to " . $email . " for account " . $uid);
	    // Keep going though
	  }
	}

	// Produce the result page

	print "<h2>Account request received.</h2>\n";
	print "<p>\n";
	print "A confirmation email has been sent to $email.";
	print " Please confirm your account request by following the";
	print " instructions in that email. If you do not receive an email";
	print " within 24 hours, please contact us at";
	print " <a href=\"mailto:help@geni.net\">help@geni.net</a>.\n";
	print "</p>\n";
      } else {
	// Failed to queue email

	// Notify admins  / put in log
	error_log("Failed to send user confirmation email to " . $email . " for account " . $uid);

	// Next send an email about the error
	$headers = $AR_EMAIL_HEADERS;
	$server_host = $_SERVER['SERVER_NAME'];
	mail($idp_approval_email,
	     "IdP Account Request Failure $server_host",
	     "An error occurred on IdP account request; failed to send confirmation email to $email for account $uid.",
	     $headers);
	// Note you could change the state to DENIED I suppose

	// Produce the result page
	print "<h2>Account request failed</h2>";
	print "<p> We are sorry, your account request failed. ";
	print "An email has been sent to the operators and they will be in touch with you shortly.</p>";
      }
    } else {
      print "<h2>Internal Error</h2>";
      print "<p> We are sorry, but your account request could not be completed. ";
      print "Email <a href=\"mailto:help@geni.net\">help@geni.net</a> for assistance.";
    }
  }
}

?>
      
  </div>
  <div id="footer">
    Need help? Questions? Email <a href="mailto:help@geni.net">GENI help</a>.
    <br>
    <a href="http://www.geni.net/">GENI</a> is sponsored by the
    <a href="http://www.nsf.gov/">
      <img src="https://www.nsf.gov/images/logos/nsf1.gif" alt="NSF Logo" height="25" width="25">
      National Science Foundation
    </a>
  </div>
</body>
</html>

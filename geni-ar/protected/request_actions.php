<?php
//----------------------------------------------------------------------
// Copyright (c) 2012 Raytheon BBN Technologies
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
// HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY
// WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE WORK OR THE USE OR OTHER DEALINGS
// IN THE WORK.
//----------------------------------------------------------------------
include_once('/etc/geni-ar/settings.php');
require_once('ldap_utils.php');
require_once('db_utils.php');
require_once('ar_constants.php');
require_once('log_actions.php');

global $leads_email;
global $acct_manager_url;

//Add account to ldap database
$ldapconn = ldap_setup();
if ($ldapconn === -1) {
  process_error("LDAP Connection Failed");
  exit();
}

$id = $_REQUEST['id'];
$action = $_REQUEST['action'];

$sql = "SELECT * FROM " . $AR_TABLENAME . " WHERE id=" . $id;
$result = db_fetch_rows($sql);
if ($result['code'] != 0) {
  process_error("Postgres database query failed");
  exit();
}

$row = $result['value'][0];

$uid = $row['username_requested']; 

$new_dn = get_userdn($uid);
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
//posixAccount requires these fields although we don't need them
$attrs['gidNumber'] = $id;
$attrs['homeDirectory'] = "";

$title = $row['title'];
$reason = $row['reason'];

if ($row['request_state'] === "PW CHANGE REQUESTED" and $action != "passwd") {
  process_error("This a password change request");
  exit();
}

if ($action === "passwd")
  {
    if ($row['request_state'] != "PW CHANGE REQUESTED") {
      process_error("This is not a password change request");
      exit();
    }
    if (ldap_check_account($ldapconn,$uid) == false) {
      process_error("Cannot change password for uid=" . $uid . ". Account does not exist.");
    } else {
      //notify the user
      $subject = "GENI Identity Provider Account Password Changed";
      $body = 'The password for the account with username=' . $uid . 'has been changed as requested. ';
      $body .= 'If you have any questions please contact the geni project office: portal-help@geni.net.';
      mail($user_email, $subject, $body);

      $res = add_log($uid, "Passwd Changed");
      if ($res != 0) {
	process_error ("Logging failed.  Will not change request status.");
      } else {
	$sql = "UPDATE " . $AR_TABLENAME . " SET request_state='APPROVED' where id ='" . $id . '\'';
	$res = db_execute_statement($sql);
	if ($res['code'] != 0) {
	  process_error ("Database action failed.  Could not change request status for " . $uid);
	  exit();
	}
	header("Location: " . $acct_manager_url . "/display_requests.php");
      }
    }
  }
else if ($action === "approve") 
  {
    //First check if account exists
    if (ldap_check_account($ldapconn,$uid))
      {
	process_error("Account for uid=" . $uid . " already exists.");
      }
    //Next check if email exists
    else if (ldap_check_email($ldapconn,$user_email))
      {
	process_error("Account with email address=" . $user_email . " already exists.");
      }
    else 
      {
	//Add log to action table
	$res = add_log($uid, "Account Created");
	if ($res != 0) {
	  process_error ("ERROR: Logging failed.  Will not create account for " . $uid);
	  exit();
	}
	$ret = ldap_add($ldapconn, $new_dn, $attrs);
	if ($ret === false) {
	  process_error ("ERROR: Failed to create new ldap account");
	  add_log_comment($uid, "Account Created", "FAILED");
	  exit();
	}

	// Now set created timestamp in postgres db
	$sql = "UPDATE " . $AR_TABLENAME . ' SET created_ts=now() at time zone \'utc\' where id =\'' . $id . '\'';
	$result = db_execute_statement($sql);
	if ($result['code'] != 0) {
	  process_error("Postgres database update failed");
	  exit();
	}

	$sql = "UPDATE " . $AR_TABLENAME . " SET request_state='APPROVED' where id ='" . $id . '\'';
	$result = db_execute_statement($sql);
	if ($result['code'] != 0) {
	  process_error("Postgres database update failed");
	  exit();
	}

	// notify in email
	$subject = "New GENI Identity Provider Account Created";
	$body = 'A new GENI Identity Provider account has been created by ' . $_SERVER['PHP_AUTH_USER'] . ' for ';
	$body .= "$uid.\n\n";
	$email_vars = array('first_name', 'last_name', 'email','organization', 'title', 'reason');
	foreach ($email_vars as $var) {
	  $val = $row[$var];
	  $body .= "$var: $val\n";
	}
	$body .= "\nSee table idp_account_request for complete details.\n";
	$res_admin = mail($idp_audit_email, $subject, $body);
	
	// Notify user
	$filename = "/etc/geni-ar/notification-email.txt";
	$file = fopen( $filename, "r" );
	if( $file == false )
	  {
	    echo ( "Error in opening file");
	    exit();
	  }
	$filesize = filesize( $filename );
	$filetext = fread( $file, $filesize );
	fclose( $file );

	$filetext = str_replace("EXPERIMENTER_NAME_HERE",$firstname,$filetext);
	$filetext = str_replace("USER_NAME_GOES_HERE",$uid,$filetext);
	$res_user = mail($user_email, "GENI Identity Provider Account Created", $filetext);
	//$res_user = true;
	if (!($res_admin and $res_user)) {
	  if (!$res_admin)
	    process_error("Failed to send email to " . $portal_admin_email . " for account " . $uid);
	  if (!$res_user)
	    process_error("Failed to send email to " . $user_email . " for account " . $uid);
	  exit();
	}
	header("Location: " . $acct_manager_url . "/display_requests.php");
      }
  } 
else if ($action === 'deny')
  {
    $res = add_log($uid, "Account Denied");
    if ($res != 0) {
      process_error ("ERROR: Logging failed.  Will not deny account");
      exit();
    }
    $sql = "UPDATE " . $AR_TABLENAME . " SET request_state='DENIED' where id ='" . $id . '\'';
    $result = db_execute_statement($sql);
    if ($result['code'] != 0) {
      process_error("Postgres database update failed");
      exit();
    }
    //send email to audit address
    $subject = "GENI Identity Provider Account Request Denied";
    $body = 'The account request for username=' . $uid . ' has been denied by ' . $_SERVER['PHP_AUTH_USER'] . ".";
    mail($idp_audit_email, $subject, $body);

    header("Location: " . $acct_manager_url . "/display_requests.php");
  }
else if ($action === "leads")
  {
    $filename = "/etc/geni-ar/leads-email.txt";
    $file = fopen( $filename, "r" );
    if( $file == false )
      {
	process_error ( "Error in opening file " . $filename);
	exit();
      }
    $filesize = filesize( $filename );
    $filetext = fread( $file, $filesize );
    fclose( $file );
	
    $filetext = str_replace("INSTITUTION",$org,$filetext);
    $filetext = str_replace("TITLE",$title,$filetext);
    $filetext = str_replace("REASON",$reason,$filetext);
    $filetext = str_replace("EMAIL",$user_email,$filetext);
    $filetext = str_replace("NAME",$fullname,$filetext);
    
    print '<head><title>Email Leads</title></head>';
    print '<a href="' . $acct_manager_url . '">Return to main page</a>';
    
    print '<form method="POST" action="send_email.php?arstate=EMAILED_LEADS">';
    print 'To: <input type="text" name="sendto" value="' . $idp_leads_email . '">';
    print '<br><br>';
    $email_body = '<textarea name="email_body" rows="30" cols="80">' . $filetext. '</textarea>';
    print $email_body;
    print '<br><br>';
    print "<input type=\"hidden\" name=\"id\" value=\"$id\"/>";
    print "<input type=\"hidden\" name=\"uid\" value=\"$uid\"/>";
    print "<input type=\"hidden\" name=\"log\" value=\"Emailed Leads\"/>";
    print '<input type="submit" value="SEND"/>';
    print "</form>";
    
  }
else if ($action === "requester")
  {
    $filename = "/etc/geni-ar/user-email.txt";
    $file = fopen( $filename, "r" );
    if( $file == false )
      {
	process_error ( "Error in opening file " . $filename );
	exit();
      }
    $filesize = filesize( $filename );
    $filetext = fread( $file, $filesize );
    fclose( $file );
    
    $filetext = str_replace("REQUESTER",$firstname,$filetext);
    
    print '<head><title>Email Requester</title></head>';
    print '<a href="' . $acct_manager_url . '">Return to main page</a>';
    
    print '<form method="POST" action="send_email.php?arstate=EMAILED_REQUESTER">';
    print 'To: <input type="text" name="sendto" value="' . $user_email . '">';
    print '<br><br>';
    $email_body = '<textarea name="email_body" rows="30" cols="80">' . $filetext. '</textarea>';
    print $email_body;
    print '<br><br>';
    print "<input type=\"hidden\" name=\"id\" value=\"$id\"/>";
    print "<input type=\"hidden\" name=\"uid\" value=\"$uid\"/>";
    print "<input type=\"hidden\" name=\"log\" value=\"Emailed Requester\"/>";
    print '<input type="submit" value="SEND"/>';
    print "</form>";
    
  }    

ldap_close($ldapconn);

function process_error($msg)
{
  global $acct_manager_url;

  print "$msg";
  print ('<br><br>');
  print ('<a href="' . $acct_manager_url . '/display_requests.php">Return to Account Requests</a>'); 
  error_log($msg);
}
?>
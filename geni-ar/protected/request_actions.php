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

global $leads_email;

//Add account to ldap database
$ldapconn = ldap_setup();
if ($ldapconn === -1)
  exit();

$id = $_REQUEST['id'];
$action = $_REQUEST['action'];

$sql = "SELECT * FROM " . $AR_TABLENAME . " WHERE id=" . $id;
$result = db_fetch_rows($sql);
$row = $result['value'][0];

$uid = $row['username_requested']; 

$new_dn = get_userdn($uid);
$attrs['objectClass'][] = "inetOrgPerson";
$attrs['objectClass'][] = "eduPerson";
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

$title = $row['title'];
$reason = $row['reason'];

if ($action === "passwd")
  {
    add_log($$uid, "Passwd Changed");
    $sql = "UPDATE " . $AR_TABLENAME . " SET request_state='APPROVED' where username_requested ='" . $uid . '\'';
    $result = db_execute_statement($sql);
    header("Location: https://shib-idp2.gpolab.bbn.com/manage/display_requests.php");
  }
    
//First check if account exists
else if (ldap_check_account($ldapconn,$uid))
  {
    print("Account for uid=" . $uid . " exists.");
  }
else 
  {
    if ($action==="approve")
      {
	$ret = ldap_add($ldapconn, $new_dn, $attrs);

	// notify in email
	$subject = "New IdP Account Created";
	$body = 'A new IdP account has been created for ';
	$body .= "$uid.\n\n";
	$email_vars = array('first_name', 'last_name', 'email','organization', 'title', 'reason');
	foreach ($email_vars as $var) {
	  $val = $row[$var];
	  $body .= "$var: $val\n";
	}
	$body .= "\nSee table idp_account_request for complete details.\n";
	mail($portal_admin_email, $subject, $body);

	// Now set created timestamp in postgres db
	$sql = "UPDATE " . $AR_TABLENAME . ' SET created_ts=now() where username_requested =\'' . $uid . '\'';
	$result = db_execute_statement($sql);
	$sql = "UPDATE " . $AR_TABLENAME . " SET request_state='APPROVED' where username_requested ='" . $uid . '\'';
	$result = db_execute_statement($sql);

	// Notify user
	$filename = "/etc/geni-ar/notification-email.txt";
	$file = fopen( $filename, "r" );
	if( $file == false )
	  {
	    echo ( "Error in opening file" );
	    exit();
	  }
	$filesize = filesize( $filename );
	$filetext = fread( $file, $filesize );
	fclose( $file );

	$filetext = str_replace("EXPERIMENTER_NAME_HERE",$firstname,$filetext);
	$filetext = str_replace("USER_NAME_GOES_HERE",$uid,$filetext);
	mail($user_email, "GENI IdP Account Created", $filetext);

	//Add log to action table
	add_log($$uid, "Account Created");
	header("Location: https://shib-idp2.gpolab.bbn.com/manage/display_requests.php");
      }
    else if ($action === 'deny')
      {
	$sql = "UPDATE " . $AR_TABLENAME . " SET request_state='DENIED' where username_requested ='" . $uid . '\'';
	$result = db_execute_statement($sql);
	add_log($uid, "Account Denied");
	header("Location: https://shib-idp2.gpolab.bbn.com/manage/display_requests.php");
      }
    else if ($action === "leads")
      {
	$sql = "UPDATE " . $AR_TABLENAME . " SET request_state='EMAILED_LEADS' where username_requested ='" . $uid . '\'';
	$result = db_execute_statement($sql);
	add_log($uid, "Emailed Leads");
	$filename = "/etc/geni-ar/leads-email.txt";
	$file = fopen( $filename, "r" );
	if( $file == false )
	  {
	    echo ( "Error in opening file" );
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
	
	print '<form method="POST" action="send_email.php">';
	print 'To: <input type="text" name="sendto" value="' . $leads_email . '">';
	print '<br><br>';
	$email_body = '<textarea name="email_body" rows="30" cols="80">' . $filetext. '</textarea>';
	print $email_body;
	print '<br><br>';
	print '<input type="submit" value="SEND"/>';
	print "</form>";

      }
    else if ($action === "requester")
      {
	$sql = "UPDATE " . $AR_TABLENAME . " SET request_state='EMAILED_REQUESTER' where username_requested ='" . $uid . '\'';
	$result = db_execute_statement($sql);
	add_log($uid, "Emailed Requester");

	$filename = "/etc/geni-ar/user-email.txt";
	$file = fopen( $filename, "r" );
	if( $file == false )
	  {
	    echo ( "Error in opening file" );
	    exit();
	  }
	$filesize = filesize( $filename );
	$filetext = fread( $file, $filesize );
	fclose( $file );

	$filetext = str_replace("REQUESTER",$firstname,$filetext);
	
	print '<form method="POST" action="send_email.php">';
	print 'To: <input type="text" name="sendto" value="' . $user_email . '">';
	print '<br><br>';
	$email_body = '<textarea name="email_body" rows="30" cols="80">' . $filetext. '</textarea>';
	print $email_body;
	print '<br><br>';
	print '<input type="submit" value="SEND"/>';
	print "</form>";

      }    
  }
ldap_close($ldapconn);

?>
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
// HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,ldapsearch -xLLL -b "dc=shib-idp2,dc=gpolab,dc=bbn,dc=com" uid=* sn givenName cn
// WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE WORK OR THE USE OR OTHER DEALINGS
// IN THE WORK.
//----------------------------------------------------------------------
include_once('/etc/geni-ar/settings.php');
require_once('ldap_utils.php');
require_once('db_utils.php');
require_once('ar_constants.php');

global $user_dn;

//Add account to ldap database
$ldapconn = ldap_setup();
if ($ldapconn === -1)
  exit();

$uid = $_REQUEST['username'];
$sn =  $_REQUEST['lastname'];
$givenName =  $_REQUEST['firstname'];
$sn =  $_REQUEST['lastname'];
$mail =  $_REQUEST['email'];
$phone =  $_REQUEST['phone'];
$pw =  $_REQUEST['pw'];
$org =  $_REQUEST['org'];
$action = $_REQUEST['action'];

//$array = $_REQUEST;
//foreach ($array as $var => $value) {
//    print "$var = $value<br/>";
//   }

$new_dn = "uid=" . $uid . $user_dn;
$attrs['objectClass'][] = "inetOrgPerson";
$attrs['objectClass'][] = "eduPerson";
$attrs['uid'] = $uid;
$attrs['sn'] = $sn;
$attrs['givenName'] = $givenName;
$attrs['cn'] = $givenName . " " . $sn;
$attrs['displayName'] = $givenName . " " . $sn;
$attrs['userPassword'] = $pw;
$attrs['mail'] = $mail;
$attrs['eduPersonAffiliation'][] = "member";
$attrs['eduPersonAffiliation'] []= "staff";
$attrs['telephoneNumber'] = $phone;
$attrs['o'] = $org;

//First check if account exists
if (ldap_check_account($ldapconn,$uid))
  {
    print("Account for uid=" . $uid . " exists.");
  }
else 
  {
    if ($action==="approve")
      {
	$ret = ldap_add($ldapconn, $new_dn, $attrs);
	
	// Now set created timestamp in postgres db
	$sql = "UPDATE " . $AR_TABLENAME . ' SET created_ts=now() where username_requested =\'' . $uid . '\'';
	$result = db_execute_statement($sql);
	$sql = "UPDATE " . $AR_TABLENAME . " SET request_state='APPROVED' where username_requested ='" . $uid . '\'';
	$result = db_execute_statement($sql);
      }
    else if ($action === 'deny')
      {
	$sql = "UPDATE " . $AR_TABLENAME . " SET request_state='DENIED' where username_requested ='" . $uid . '\'';
	$result = db_execute_statement($sql);
      }
    else if ($action === "hold")
      {
	$sql = "UPDATE " . $AR_TABLENAME . " SET request_state='HOLD' where username_requested ='" . $uid . '\'';
	$result = db_execute_statement($sql);
      }

    header("Location: https://shib-idp2.gpolab.bbn.com/manage/display_requests.php");
  }
ldap_close($ldapconn);


?>
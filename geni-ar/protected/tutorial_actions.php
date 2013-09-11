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

//First make sure we can connect to ldap
$ldapconn = ldap_setup();
if ($ldapconn === -1) {
  process_error("LDAP Connection Failed");
  exit();
}

//get request data
$num = $_REQUEST['num'];
$user_prefix = $_REQUEST['userprefix'];
$pw_prefix =  $_REQUEST['pwprefix'];
$org_email =  $_REQUEST['email'];                            
$org_phone =  $_REQUEST['phone'];
$desc = $_REQUEST['desc'];   

//check for valid username
if (strlen($uid) > 6) {
  process_error("username prefix cannot be longer than 6 characters.");
  exit();
  }
  if (!preg_match('/^[a-z0-9]{1,6}$/', $uid)) {
    process_error("username must consist of lowercase letters and numbers only.");
    exit();
  }

//now for each username, check that an account doesn't already exist
//then create an "APPROVED" request in db
for ($x=1; $x<=$num; $x++)
  {
    $usernum = strval($x);
    if (strlen($usernum) == 1)
      {
	$usernum = "0" . $usernum;
      }
    $uid = $user_prefix . $user_num;
    if (ldap_check_account($ldapconn,$uid)) {
      print ("ERROR: username " . $uid . " is already in use");
      exit();
    }
  }
    
function process_error($msg)
{
  global $acct_manager_url;

  print ("$msg");
  print ('<br><br>');
  print ('<a href="' . $acct_manager_url . '/tutorial_requests.php">Back</a>'); 
  print ('<a href="' . $acct_manager_url ."'>Cancel</a>'); 
}

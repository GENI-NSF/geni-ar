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

$id = $_REQUEST['id'];
$action = $_REQUEST['action'];

if ($action === "delete") {
  $ldapconn = ldap_setup();
  if ($ldapconn === -1)
    exit();

  // Delete account
  $ret = ldap_delete($ldapconn, get_userdn($id));
  if ($ret) {
    //change status in postgres database
    $sql = "UPDATE " . $AR_TABLENAME . " SET request_state='DELETED' WHERE username_requested='" . $id . '\'';
    $result = db_execute_statement($sql);
    if ($result['value'] === 1) {
      add_log($id,"Account Deleted");
      header("Location: https://shib-idp2.gpolab.bbn.com/manage/display_accounts.php");
      
    } else
      print "COULD NOT CHANGE STATUS OF REQUEST FOR DELETED ACCOUNT WITH USERNAME=" . $id;
      print ('<br><br>');
      print ('<a href="' . $acct_manager_url . '/display_accounts.php">Return to Current Accounts</a>'); 
  } else {
    print "DELETE OF ACCT " . $id . " FAILED";
      print ('<br><br>');
      print ('<a href="' . $acct_manager_url . '/display_accounts.php">Return to Current Accounts</a>'); 
  }
} else {
  header("Location: https://shib-idp2.gpolab.bbn.com/manage/display_accounts.php");
}
?>
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
// HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
// WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE WORK OR THE USE OR OTHER DEALINGS
// IN THE WORK.
//----------------------------------------------------------------------
include_once('/etc/geni-ar/settings.php');
require_once('ldap_utils.php');
require_once('db_utils.php');
require_once('ar_constants.php');
require_once('log_actions.php');

global $acct_manager_url;
global $base_dn;

$id = $_REQUEST['id'];
$action = $_REQUEST['action'];

if ($action === "delete") {
  $ldapconn = ldap_setup();
  if ($ldapconn === -1) {
    process_error("LDAP Connection Failed");
    exit();
  }

  // Delete account
  $res = add_log($id,"Account Deleted");
  if ($res != 0) {
    process_error ("ERROR: Logging failed.  Will not delete account");
    exit();
  }
  //get the request id if there is one
  $filter = "(uid=" . $id . ")";
  $ret = ldap_search($ldapconn, $base_dn, $filter);
  $entry = ldap_first_entry($ldapconn,$ret);
  $attrs = ldap_get_attributes($ldapconn,$entry);
  $accts = ldap_get_entries($ldapconn,$ret);
  $ret = ldap_delete($ldapconn, get_userdn($id));
  if ($ret) {
    //send email to audit address
    $subject = "GENI Identity Provider Account Deleted";
    $body = 'The account with username=' . $id . ' has been deleted by ' . $_SERVER['PHP_AUTH_USER'] . '.';
    $headers = "Auto-Submitted: auto-generated\r\n";
    $headers .= "Precedence: bulk\r\n";
    $headers .= "Reply-to: portal-help@geni.net\r\n";
    mail($idp_audit_email, $subject, $body,$headers);

    //change status in postgres database
    if (array_key_exists('uidNumber',$attrs)) {
      $acct = $accts[0];
      $req_id = $acct['uidnumber'][0];
      $sql = "UPDATE " . $AR_TABLENAME . " SET request_state='DELETED' WHERE id='" . $req_id . '\'';
      $result = db_execute_statement($sql);
      if ($result['code'] != 0) {
	process_error("Postgres database update failed");
	exit();
      }

      if ($result['value'] === 1) {
	header("Location: " . $acct_manager_url . "/display_accounts.php");
      } else {
	process_error("Failed to change request state for deleted account for " . $id);
	exit();
      } 
    }
  } else {
    add_log_comment($uid, "Account Deleted", "FAILED");
    process_error( "Failed to delete account for " . $id);
    exit();
  }
}
header("Location: " . $acct_manager_url . "/display_accounts.php");

function process_error($msg)
{
  global $acct_manager_url;

  print "$msg";
  print ('<br><br>');
  print ('<a href="' . $acct_manager_url . '/display_accounts.php">Return to Current Accounts</a>'); 
  error_log($msg);
}

?>
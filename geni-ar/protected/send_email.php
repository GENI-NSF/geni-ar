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
require_once('db_utils.php');
require_once('log_actions.php');
require_once('ar_constants.php');
include_once('/etc/geni-ar/settings.php');

global $acct_manager_url;

$arstate = $_GET['arstate'];
$email_body = $_REQUEST['email_body'];
$sendto = $_REQUEST['sendto'];
$uid = $_REQUEST['uid'];
$log = $_REQUEST['log'];

$res = mail($sendto, "GENI IdP Account Request", $email_body);
if ($res === false) {
  process_error("Failed to send email to " . $sendto . " for account " . $uid);
  exit();
}

$sql = "UPDATE " . $AR_TABLENAME . " SET request_state='" . $arstate . "' where username_requested ='" . $uid . '\'';
$result = db_execute_statement($sql);
if ($result['code'] != 0) {
  process_error("Postgres database update failed");
}

// check for single quotes and backslashes in body before logging
$email_body = str_replace("'","''",$email_body);
$email_body = str_replace("\\","\\\\",$email_body);


$res = add_log_with_comment($uid, $log,$email_body);
if ($res != 0) {
  //try again without the comment
  $res = add_log($uid,$log);
  if ($res != 0)
    process_error("Failed to log email to " . $sendto . " for account " . $uid); 
} else {
  header("Location: " . $acct_manager_url . "/display_requests.php");
}

function process_error($msg)
{
  global $acct_manager_url;

  print $msg;
  print ('<br><br>');
  print ('<a href="' . $acct_manager_url . '/display_requests.php">Return to Account Requests</a>'); 
  error_log($msg);
}
?>

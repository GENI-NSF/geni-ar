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
require_once('db_utils.php');
require_once('ar_constants.php');
require_once('log_actions.php');

global $acct_manager_url;

$id = $_REQUEST['id'];
$uid = $_REQUEST['uid'];
$oldnote = $_REQUEST['oldnote'];
$note = $_REQUEST['note'];
$state = $_REQUEST['request_state'];

$ts = gmdate("Y-m-d H:i");
$newnote = $ts . " " . $note;
$text = $oldnote . " " . $newnote;

$conn = db_conn();

//add the note
$sql = "UPDATE " . $AR_TABLENAME . " SET notes=" . $conn->quote($text, 'text') . " WHERE ID=". $conn->quote($id, 'text');
$result = db_execute_statement($sql);
if ($result['code'] != 0) {
  process_error("Postgres datbase update failed. Could not add note.");
} elseif ($state==="REQUESTED") {
  header("Location: " . $acct_manager_url . "/display_requests.php#current");
} elseif ($state==="EMAILED_LEADS") {
  header("Location: " . $acct_manager_url . "/display_requests.php#leads");
} elseif ($state==="EMAILED_REQUESTER") {
  header("Location: " . $acct_manager_url . "/display_requests.php#requester");
}
//log the note
$res = add_log_with_comment($uid, "Note",$note);
if ($res != 0) {
  process_error ("Failed to log note for " . $uid);
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
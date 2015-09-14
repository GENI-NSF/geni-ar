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

global $acct_manager_url;

$user = $_GET["uid"];

$conn = db_conn();
if ($user === "ALL")
  {
    $sql = "SELECT * FROM idp_account_actions order by action_ts desc";
  } else
  {
    $sql = "SELECT * FROM idp_account_actions where uid='" . $user . '\' order by action_ts desc';
  }
$result = db_fetch_rows($sql);
if ($result['code'] != 0) {
  print '<a href="' . $acct_manager_url . '">Return to main page</a>';
  print '<br></br>';
  process_error("Postgres database query failed");
  exit();
}

$rows = $result['value'];

function get_values($row)
{
  global $uid, $action_time, $performer, $action, $comment;

  $uid = $row['uid'];
  $action_time = $row['action_ts'];
  $action_time = substr($action_time,0,16);
  $performer = $row['performer'];
  $action = $row['action_performed'];
  $comment = $row['comment'];
}

if ($user == "ALL") {
  $title = 'Account Request Action Logs';
} else {
  $title = 'Account Request Action Logs for ' . $user;
}

require_once("header.php");
show_header("Account Request Action Logs", array("#accountlogs"));


print '<div class="card" style="margin-top: 80px;">';
print '<h1>Account Request Action Logs</h1>';
print '<table id="accountlogs">';
print '<thead>';
print '<tr>';
print '<th>Username</th><th>Action</th><th>Date/Time (UTC)</th><th>Performer</th><th>Comment</th></tr></thead>';
print '<tbody>';
foreach ($rows as $row) {
  get_values($row);
  print "<tr>";
  print "<td>$uid</td><td>$action</td><td>$action_time</td><td>$performer</td><td>$comment</td>";
  print '</tr>';
}
print '</tbody></table></div></div></body></html>';

function process_error($msg)
{
  print "$msg";
  error_log($msg);
}
?>

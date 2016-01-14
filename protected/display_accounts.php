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
include_once('/etc/geni-ar/settings.php');
require_once('ldap_utils.php');
require_once('db_utils.php');
require_once('ar_constants.php');

global $base_dn;
global $acct_manager_url;

$ldapconn = ldap_setup();
if ($ldapconn === -1)
  exit();

$filter = "(uid=*)";
$result = ldap_search($ldapconn, $base_dn, $filter);
$accts = ldap_get_entries($ldapconn,$result);
//print "Found accounts: " . $accts['count'];

function decode_value($row, $index) {
  return utf8_decode($row[$index][0]);
}

function get_values($row)
{
  global $firstname, $lastname, $email, $uid, $phone, $org;

  /* Some of these values are utf8_decoded for display on the web
   * page. The values that are not utf8_decoded are assumed to be pure
   * ascii.
   */
  $firstname = decode_value($row, 'givenname');
  $lastname = decode_value($row, 'sn');
  $email = $row['mail'][0];
  $uid = $row['uid'][0];
  $phone = $row['telephonenumber'][0];
  $org = decode_value($row, 'o');
}

require_once("header.php");
show_header("View Accounts", array("#currentaccounts", "#deletedaccounts"));

?>


<h2 style="margin-top: 80px;" class="card">Accounts</h2>
<div class='nav2'>
  <ul class='tabs'>
    <li><a class='tab' data-tabindex='1' href='#current'>Current Accounts</a></li>
    <li><a class='tab' data-tabindex='2' href='#deleted'>Deleted Requests</a></li>
  </ul>
</div>

<?php 
print '<div class="card" id="current">';
print '<h2>Current Accounts</h2>';
print '<table id="currentaccounts">';
print '<thead>';
print '<tr>';
print '<th> </th>';
print '<th>Institution</th><th>Username</th><th>Email Address</th><th>First Name</th><th>Last Name</th><th>Phone Number</th></tr></thead>';
print '<tbody>';
foreach ($accts as $acct) {
  get_values($acct);
  if ($uid === null) 
    continue;
  print "<tr>";
  print'<td align="center">';
  print '<form method="POST" action="acct_actions.php">';
  $actions = '<select name=action><option value="delete">DELETE</option></select>';
  print $actions;
  print '<input type="submit" value="SUBMIT"/>';
  print "<input type=\"hidden\" name=\"id\" value=\"$uid\"/>";
  print "</form>";
  print '<a href="' . $acct_manager_url . '/action_log.php?uid=' . $uid . '">View account logs</a>';
  print "</td>";
  print "<td>$org</td><td>$uid</td><td>$email</td><td>$firstname</td><td>$lastname</td><td>$phone</td>";
  print '</tr>';
}
print '</tbody></table></div>';

$sql = "SELECT * FROM " . $AR_TABLENAME . " WHERE request_state='DELETED'";
$result = db_fetch_rows($sql);
if ($result['code'] != 0) {
  process_error("Postgres database query failed");
  exit();
}

$rows = $result['value'];


print '<div class="card" id="deleted">';
print '<h2>Deleted Accounts</h2>';
print '<table id="deletedaccounts">';
print '<thead>';
print '<tr>';
print '<th>Institution</th><th>Username</th><th>Email Address</th><th>First Name</th><th>Last Name</th><th>Performer</th><th>Account Deleted</th></tr></thead>';
print '<tbody>';
foreach ($rows as $row) {
  $firstname = $row['first_name'];
  $lastname = $row['last_name'];
  $email = $row['email'];
  $uname = $row['username_requested'];
  $org = $row['organization'];
  $performer="";
  $action_ts="";
  $sql = "SELECT performer, action_ts from idp_account_actions WHERE uid='" . $uname . "' and action_performed='Account Deleted' ORDER BY id desc";
  $action_result = db_fetch_rows($sql);
  if ($result['code'] != 0) {
    process_error("Postgres database query failed");
    exit();
  }

  $logs = $action_result['value'];
  if ($logs) {
    $performer = $logs[0]['performer'];
    $action_ts = $logs[0]['action_ts'];
    $action_ts = substr($action_ts,0,16);
  }
  print "<td>$org</td><td>$uname</td><td>$email</td><td>$firstname</td><td>$lastname</td><td>$performer</td><td>$action_ts</td>";
  print '</tr>';
}
print '</tbody></table>';

function process_error($msg)
{
  print "$msg";
  error_log($msg);
}

?>
</div></div></body></html>

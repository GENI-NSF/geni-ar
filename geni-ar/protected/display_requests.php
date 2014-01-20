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
require_once('db_utils.php');
require_once('ar_constants.php');
include_once('/etc/geni-ar/settings.php');

global $acct_manager_url;

print '<head><title>Account Request Management</title></head>';
print '<a href="' . $acct_manager_url . '">Return to main page</a>';

print '<h1>';
print '<p>Account Request Management</p>';
print '</h1>';

$conn = db_conn();
$sql = "SELECT * FROM " . $AR_TABLENAME . " WHERE request_state='" . AR_STATE::REQUESTED . "' or request_state='" . AR_STATE::PASSWD . '\'';
$result = db_fetch_rows($sql);
if ($result['code'] != 0) {
  process_error("Query failed to postgres database");
  exit();
}
$rows = $result['value'];

function get_values($row)
{
  global $id, $firstname, $lastname, $email, $uname, $phone, $requested, $created, $org, $title, $reason, $notes;

  $id = $row['id'];
  $firstname = $row['first_name'];
  $lastname = $row['last_name'];
  $email = $row['email'];
  $uname = $row['username_requested'];
  $phone = $row['phone'];
  $requested = $row['request_ts'];
  $requested = substr($requested,0,16);
  $created = $row['created_ts'];
  $created = substr($created,0,16);
  $org = $row['organization'];
  $title = $row['title'];
  if ($row['request_state']=== AR_STATE::PASSWD ) {
    $reason = "Password Change Request";
  } else {
    $reason = $row['reason'];
  }
  $notes = $row['notes'];
}
print '<a name="current"></a>';
print '<h2>';
print '<p>Current Account Requests</p>';
print '</h2>';

print '<table border="1">';
print '<tr>';
print '<th> </th>';
print '<th>Institution</th><th>Job Title</th><th>Account Reason</th>';
print '<th>Email Address</th><th>First Name</th><th>Last Name</th><th>Phone Number</th><th>Username</th><th>Requested (UTC)</th><th>Notes</th></tr>';
foreach ($rows as $row) {
  get_values($row);
  print "<tr>";
  print'<td align="center">';
  print '<form method="POST" action="request_actions.php">';
  $actions = '<select name=action><option value="approve">APPROVE</option><option value="deny">DENY</option><option value="confirm">CONFIRM REQUESTER</option><option value="leads">EMAIL LEADS</option><option value="requester">EMAIL REQUESTER</option><option value="passwd">CHANGE PASSWRD</option><option value="note">ADD NOTE</option></select>';
  print $actions;
  print '<input type="submit" value="SUBMIT"/>';
  print "<input type=\"hidden\" name=\"id\" value=\"$id\"/>";
  print "</form>";
  print "</td>";

  print "<td>$org</td><td>$title</td><td>$reason</td><td>$email</td><td>$firstname</td><td>$lastname</td><td>$phone</td><td>$uname</td><td>$requested</td><td>$notes</td>";
  print '</tr>';
}
print '</table>';

$sql = "SELECT * FROM " . $AR_TABLENAME . " WHERE request_state='" . AR_STATE::CONFIRM . '\'';
$result = db_fetch_rows($sql);
if ($result['code'] != 0) {
  process_error("Postgres database query failed");
  exit();
}
$rows = $result['value'];

print '<a name="confirm"></a>';
print '<h2>';
print '<p>Account Requests Waiting for Requester Confirmation</p>';
print '</h2>';

print '<table border="1">';
print '<tr>';
print '<th> </th>';
print '<th>Institution</th><th>Job Title</th><th>Account Reason</th>';
print '<th>Email Address</th><th>First Name</th><th>Last Name</th><th>Phone Number</th><th>Username</th><th>Requested (UTC)</th><th>Action Performer</th><th>Email Sent</th><th>Notes</th></tr>';
foreach ($rows as $row) {
  get_values($row);
  $sql = "SELECT performer, action_ts from idp_account_actions WHERE uid='" . $uname . "' and action_performed='Requested Confirmation' ORDER BY id desc";
  $action_result = db_fetch_rows($sql);
  if ($action_result['code'] != 0) {
    process_error("Postgres database query failed");
    exit();
  }
  $logs = $action_result['value'];
  if ($logs) {
    $performer = $logs[0]['performer'];
    $action_ts = $logs[0]['action_ts'];
    $action_ts = substr($action_ts,0,16);
  }
  else {
    $performer = "";
    $action_ts = "";
  }
  print "<tr>";
  print'<td align="center">';
  print '<form method="POST" action="request_actions.php">';
  $actions = '<select name=action><option value="approve">APPROVE</option><option value="deny">DENY</option><option value="confirm">CONFIRM REQUESTER</option><option value="leads">EMAIL LEADS</option><option value="requester">EMAIL REQUESTER</option><option value="note">ADD NOTE</option></select>';
  print $actions;
  print '<input type="submit" value="SUBMIT"/>';
  print "<input type=\"hidden\" name=\"id\" value=\"$id\"/>";
  print "</form>";
  print "</td>";
  print "<td>$org</td><td>$title</td><td>$reason</td><td>$email</td><td>$firstname</td><td>$lastname</td><td>$phone</td><td>$uname</td><td>$requested</td><td>$performer</td><td>$action_ts</td><td>$notes</td>";
  print '</tr>';
}
print '</table>';

$sql = "SELECT * FROM " . $AR_TABLENAME . " WHERE request_state='" . AR_STATE::LEADS . '\'';
$result = db_fetch_rows($sql);
if ($result['code'] != 0) {
  process_error("Postgres database query failed");
  exit();
}
$rows = $result['value'];

print '<a name="leads"></a>';
print '<h2>';
print '<p>Account Requests Waiting for Lead Response</p>';
print '</h2>';

print '<table border="1">';
print '<tr>';
print '<th> </th>';
print '<th>Institution</th><th>Job Title</th><th>Account Reason</th>';
print '<th>Email Address</th><th>First Name</th><th>Last Name</th><th>Phone Number</th><th>Username</th><th>Requested (UTC)</th><th>Action Performer</th><th>Email Sent</th><th>Notes</th></tr>';
foreach ($rows as $row) {
  get_values($row);
  $sql = "SELECT performer, action_ts from idp_account_actions WHERE uid='" . $uname . "' and action_performed='Emailed Leads' ORDER BY id desc";
  $action_result = db_fetch_rows($sql);
  if ($action_result['code'] != 0) {
    process_error("Postgres database query failed");
    exit();
  }
  $logs = $action_result['value'];
  if ($logs) {
    $performer = $logs[0]['performer'];
    $action_ts = $logs[0]['action_ts'];
    $action_ts = substr($action_ts,0,16);
  }
  else {
    $performer = "";
    $action_ts = "";
  }
  print "<tr>";
  print'<td align="center">';
  print '<form method="POST" action="request_actions.php">';
  $actions = '<select name=action><option value="approve">APPROVE</option><option value="deny">DENY</option><option value="confirm">CONFIRM REQUESTER</option><option value="leads">EMAIL LEADS</option><option value="requester">EMAIL REQUESTER</option><option value="note">ADD NOTE</option></select>';
  print $actions;
  print '<input type="submit" value="SUBMIT"/>';
  print "<input type=\"hidden\" name=\"id\" value=\"$id\"/>";
  print "</form>";
  print "</td>";
  print "<td>$org</td><td>$title</td><td>$reason</td><td>$email</td><td>$firstname</td><td>$lastname</td><td>$phone</td><td>$uname</td><td>$requested</td><td>$performer</td><td>$action_ts</td><td>$notes</td>";
  print '</tr>';
}
print '</table>';

$sql = "SELECT * FROM " . $AR_TABLENAME . " WHERE request_state='" . AR_STATE::REQUESTER . '\'';
$result = db_fetch_rows($sql);
if ($result['code'] != 0) {
  process_error("Postgres database query failed");
  exit();
}
$rows = $result['value'];

print '<a name="requester"></a>';
print '<h2>';
print '<p>Account Requests Waiting for Requester Response</p>';
print '</h2>';

print '<table border="1">';
print '<tr>';
print '<th> </th>';
print '<th>Institution</th><th>Job Title</th><th>Account Reason</th>';
print '<th>Email Address</th><th>First Name</th><th>Last Name</th><th>Phone Number</th><th>Username</th><th>Requested (UTC)</th><th>Action Performer</th><th>Email Sent</th><th>Notes</th></tr>';
foreach ($rows as $row) {
  get_values($row);
  $sql = "SELECT performer, action_ts from idp_account_actions WHERE uid='" . $uname . "' and action_performed='Emailed Requester' ORDER BY id desc";
  $action_result = db_fetch_rows($sql);
  $logs = $action_result['value'];
  if ($action_result['code'] != 0) {
    process_error("Postgres database query failed");
    exit();
  }

  if ($logs) {
    $performer = $logs[0]['performer'];
    $action_ts = $logs[0]['action_ts'];
    $action_ts = substr($action_ts,0,16);
  } else {
    $performer = "";
    $action_ts = "";
  }
  print "<tr>";
  print'<td align="center">';
  print '<form method="POST" action="request_actions.php">';
  $actions = '<select name=action><option value="approve">APPROVE</option><option value="deny">DENY</option><option value="leads">EMAIL LEADS</option><option value="requester">EMAIL REQUESTER</option><option value="note">ADD NOTE</option></select>';
  print $actions;
  print '<input type="submit" value="SUBMIT"/>';
  print "<input type=\"hidden\" name=\"id\" value=\"$id\"/>";
  print "</form>";
  print "</td>";
  print "<td>$org</td><td>$title</td><td>$reason</td><td>$email</td><td>$firstname</td><td>$lastname</td><td>$phone</td><td>$uname</td><td>$requested</td><td>$performer</td><td>$action_ts</td><td>$notes</td>";
  print '</tr>';
}
print '</table>';

$sql = "SELECT * FROM " . $AR_TABLENAME . " WHERE request_state='" . AR_STATE::APPROVED . "' ORDER BY created_ts desc";
$result = db_fetch_rows($sql);
if ($result['code'] != 0) {
  process_error("Postgres database query failed");
  exit();
}
$rows = $result['value'];

// if created_ts is empty (historic entries), then move to the bottom of the list
foreach ($rows as $row) {
  get_values($row);
  if ($created=="") {
    $blankdate = array_shift($rows);
    $rows[] = $blankdate;
  } else {
    break;
  }
}

print '<a name="approved"></a>';
print '<h2>';
print '<p>Approved Account Requests</p>';
print '</h2>';

print '<table border="1">';
print '<tr>';
print '<th>Institution</th><th>Job Title</th><th>Account Reason</th>';
print '<th>Email Address</th><th>First Name</th><th>Last Name</th><th>Phone Number</th><th>Username</th><th>Requested (UTC)</th><th>Performer</th><th>Created (UTC)</th><th>Notes</th></tr>';
foreach ($rows as $row) {
  get_values($row);
  $sql = "SELECT performer, action_ts from idp_account_actions WHERE uid='" . $uname . "' and action_performed='Account Created' ORDER BY id desc";
  $action_result = db_fetch_rows($sql);
  if ($action_result['code'] != 0) {
    process_error("Postgres database query failed");
    exit();
  }

  $logs = $action_result['value'];
  if ($logs) {
  $performer = $logs[0]['performer'];
  $action_ts = $logs[0]['action_ts'];
  $action_ts = substr($action_ts,0,16);
  }
  print "<td>$org</td><td>$title</td><td>$reason</td><td>$email</td><td>$firstname</td><td>$lastname</td><td>$phone</td><td>$uname</td><td>$requested</td><td>$performer</td><td>$created</td><td>$notes</td>";
  print '</tr>';
}
print '</table>';

$sql = "SELECT * FROM " . $AR_TABLENAME . " WHERE request_state='" . AR_STATE::DENIED . '\'';
$result = db_fetch_rows($sql);

$rows = $result['value'];
if ($result['code'] != 0) {
  process_error("Postgres database query failed");
  exit();
}

print '<a name="denied"></a>';
print '<h2>';
print '<p>Denied Account Requests</p>';
print '</h2>';

print '<table border="1">';
print '<tr>';
print '<th>Institution</th><th>Job Title</th><th>Account Reason</th>';
print '<th>Email Address</th><th>First Name</th><th>Last Name</th><th>Phone Number</th><th>Username</th><th>Requested (UTC)</th><th>Performer</th><th>Denied (UTC)</th><th>Notes</th></tr>';
foreach ($rows as $row) {
  get_values($row);
  $sql = "SELECT performer, action_ts from idp_account_actions WHERE uid='" . $uname . "' and action_performed='Account Denied' ORDER BY id desc";
  $action_result = db_fetch_rows($sql);
  $logs = $action_result['value'];
  if ($logs) {
    $performer = $logs[0]['performer'];
    $action_ts = $logs[0]['action_ts'];
    $action_ts = substr($action_ts,0,16);
  }
  print "<td>$org</td><td>$title</td><td>$reason</td><td>$email</td><td>$firstname</td><td>$lastname</td><td>$phone</td><td>$uname</td><td>$requested</td><td>$performer</td><td>$action_ts</td><td>$notes</td>";
  print '</tr>';
}
print '</table>';

function process_error($msg)
{
  print "$msg";
  error_log($msg);
}

?>

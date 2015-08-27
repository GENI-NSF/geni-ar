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

function process_error($msg)
{
  print "$msg";
  error_log($msg);
}

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

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Account Request Management</title>
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,700" rel="stylesheet" type="text/css">
  <link rel="stylesheet" href="geni-ar.css">
  <script type='text/javascript' charset='utf8' src='https://ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js'></script>
  <script type='text/javascript' charset='utf8' src='https://cdn.datatables.net/1.10.7/js/jquery.dataTables.js'></script>
  <script type='text/javascript' charset='utf8' src='cards.js'></script>
  <script type="text/javascript">
    $(document).ready( function () {
      $("#currentrequests").DataTable({paging: false});
      $("#requesterresponse").DataTable({paging: false});
      $("#requesterconfirmation").DataTable({paging: false});
      $("#waitingforlead").DataTable({paging: false});
      $("#approvedrequests").DataTable({paging: false});
      $("#deniedrequests").DataTable({paging: false});
    });
  </script>
</head>
<body>

<ul id='header'>
  <li class='headerlink'><a href="tutorial_confirmation.php">Create Tutorial Accounts</a></li>
  <li class='headerlink'><a href="action_log.php?uid=ALL" >Account Action Logs</a></li>
  <li class='headerlink'><a href="display_accounts.php" >Manage Current Accounts</a></li>
  <li class='headerlink'><a href="display_requests.php" >Manage Account Requests</a></li>
</ul> 


<div id='content-outer'>
<h1 style='margin-top: 60px;'>Account Request Management</h1>

<div class='nav2'>
  <ul class='tabs'>
    <li><a class='tab' data-tabindex='1' href='#currentrequestsdiv'>Current Reqs</a></li>
    <li><a class='tab' data-tabindex='2' href='#requesterconfirmationdiv'>Waiting for Confirmation</a></li>
    <li><a class='tab' data-tabindex='3' href='#waitingforleaddiv'>Waiting for Lead</a></li>
    <li><a class='tab' data-tabindex='4' href='#requesterresponsediv'>Waiting for Requester Response</a></li>
    <li><a class='tab' data-tabindex='5' href='#approvedrequestsdiv'>Approved Reqs</a></li>
    <li><a class='tab' data-tabindex='6' href='#deniedrequestsdiv'>Denied Reqs</a></li>
  </ul>
</div>

<div class='card' id='currentrequestsdiv'>
<h2>Current Account Requests</h2>
<table id='currentrequests'>
<thead>
<tr>
  <th>&nbsp;</th><th>Institution</th><th>Job Title</th><th>Account Reason</th>
  <th>Email Address</th><th>First Name</th><th>Last Name</th><th>Phone Number</th>
  <th>Username</th><th>Requested (UTC)</th><th>Notes</th>
</tr>
</thead>
<tbody>
<?php

$conn = db_conn();
$sql = "SELECT * FROM " . $AR_TABLENAME . " WHERE request_state='"
  . AR_STATE::REQUESTED . "' or request_state='" . AR_STATE::PASSWD . '\'';
$result = db_fetch_rows($sql);
if ($result['code'] != 0) {
  process_error("Query failed to postgres database");
  exit();
}
$rows = $result['value'];


foreach ($rows as $row) {
  get_values($row);
  print "<tr>";
  print'<td class="actions">';
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
?>
</tbody>
</table>
</div>

<div class='card' id='requesterconfirmationdiv'>
<h2> Account Requests Waiting for Requester Confirmation </h2>
<table id='requesterconfirmation'>
<thead>
<tr>
<th> </th>
<th>Institution</th><th>Job Title</th><th>Account Reason</th>
<th>Email Address</th><th>First Name</th><th>Last Name</th>
  <th>Phone Number</th><th>Username</th><th>Requested (UTC)</th>
  <th>Action Performer</th><th>Email Sent</th><th>Notes</th></tr>
</thead>
<tbody>

<?php

$sql = "SELECT * FROM " . $AR_TABLENAME . " WHERE request_state='" . AR_STATE::CONFIRM . '\'';
$result = db_fetch_rows($sql);
if ($result['code'] != 0) {
  process_error("Postgres database query failed");
  exit();
}
$rows = $result['value'];

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
  print'<td class="actions">';
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
?>
</tbody>
</table>
</div>


<div class='card' id='waitingforleaddiv'>
<h2> Account Requests Waiting for Lead Response </h2>
<table id='waitingforlead'>
<thead>
<tr>
<th> </th>
<th>Institution</th><th>Job Title</th><th>Account Reason</th>
<th>Email Address</th><th>First Name</th><th>Last Name</th>
<th>Phone Number</th><th>Username</th><th>Requested (UTC)</th>
<th>Action Performer</th><th>Email Sent</th><th>Notes</th></tr>
</thead>
<tbody>
<?php
$sql = "SELECT * FROM " . $AR_TABLENAME . " WHERE request_state='" . AR_STATE::LEADS . '\'';
$result = db_fetch_rows($sql);
if ($result['code'] != 0) {
  process_error("Postgres database query failed");
  exit();
}
$rows = $result['value'];
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
  print'<td class="actions">';
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
?>
</tbody>
</table>
</div>


<div class='card' id='requesterresponsediv'>
<h2>Account Requests Waiting for Requester Response</h2>
<table id='requesterresponse'>
<thead>
<tr>
<th> </th>
<th>Institution</th><th>Job Title</th><th>Account Reason</th>
<th>Email Address</th><th>First Name</th><th>Last Name</th>
<th>Phone Number</th><th>Username</th><th>Requested (UTC)</th>
<th>Action Performer</th><th>Email Sent</th><th>Notes</th></tr>
</thead>
<tbody>
<?php
$sql = "SELECT * FROM " . $AR_TABLENAME . " WHERE request_state='" . AR_STATE::REQUESTER . '\'';
$result = db_fetch_rows($sql);
if ($result['code'] != 0) {
  process_error("Postgres database query failed");
  exit();
}
$rows = $result['value'];
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
  print'<td class="actions">';
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
?>
</tbody>
</table>
</div>

<div class='card' id='approvedrequestsdiv'>
<h2>Approved Account Requests</h2>
<table id='approvedrequests'>
<thead>
<tr>
<th>Institution</th><th>Job Title</th><th>Account Reason</th>
<th>Email Address</th><th>First Name</th><th>Last Name</th>
  <th>Phone Number</th><th>Username</th><th>Requested (UTC)</th>
  <th>Performer</th><th>Created (UTC)</th><th>Notes</th></tr>
</thead>
<tbody>
<?php
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
foreach ($rows as $row) {
  get_values($row);
  $sql = ("SELECT performer, action_ts from idp_account_actions"
          . " WHERE uid=" . $conn->quote($uname, "text")
          . "       AND (action_performed = "
          .                   $conn->quote(AR_ACTION::ACCOUNT_CREATED, "text")
          . "            OR action_performed = "
          .            $conn->quote(AR_ACTION::TUTORIAL_ACCOUNT_CREATED, "text")
          . "           )"
          . " ORDER BY id desc");
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
  } else {
    $performer = '&nbsp;';
    $action_ts = '&nbsp;';
  }
  /* Format username as link to log page */
  $uname_link = "<a href='action_log.php?uid=$uname'>$uname</a>";
  print "<tr><td>$org</td><td>$title</td><td>$reason</td><td>$email</td><td>$firstname</td><td>$lastname</td><td>$phone</td><td>$uname_link</td><td>$requested</td><td>$performer</td><td>$created</td><td>$notes</td>";
  print '</tr>';
}
?>
</tbody>
</table>
</div>

<div class='card' id='deniedrequestsdiv'>
<h2>Denied Account Requests</h2>
<table id='deniedrequests'>
<thead>
<tr>
<th>Institution</th><th>Job Title</th><th>Account Reason</th>
<th>Email Address</th><th>First Name</th><th>Last Name</th>
<th>Phone Number</th><th>Username</th><th>Requested (UTC)</th>
<th>Performer</th><th>Denied (UTC)</th><th>Notes</th></tr>
</thead>
<tbody>
<?php
$sql = "SELECT * FROM " . $AR_TABLENAME . " WHERE request_state='" . AR_STATE::DENIED . '\'';
$result = db_fetch_rows($sql);

$rows = $result['value'];
if ($result['code'] != 0) {
  process_error("Postgres database query failed");
  exit();
}

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
?>
</tbody>
</table>
</div>
</div>

</body>
</html>
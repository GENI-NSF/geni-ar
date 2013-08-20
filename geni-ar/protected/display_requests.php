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

$conn = db_conn();
$sql = "SELECT * FROM " . $AR_TABLENAME . " WHERE created_ts IS NULL";
$result = db_fetch_rows($sql);

$rows = $result['value'];

//print 'user: ' . $_SERVER['PHP_AUTH_USER']. "<br/>";

print '<h1>';
print '<p>Current Account Requests</p>';
print '</h1>';

print '<table border="1">';
print '<tr>';
print '<th>First Name</th><th>Last Name</th><th>Email Address</th><th>Username</th><th>Phone Number</th><th>PW Hash</th><th>Asked for Account</th>';
print '<th>Institution</th><th>Job Title</th><th>Account Reason</th></tr>';
foreach ($rows as $row) {
  $firstname = $row['first_name'];
  $lastname = $row['last_name'];
  $email = $row['email'];
  $uname = $row['username_requested'];
  $phone = $row['phone'];
  $pw = $row['password_hash'];
  $requested = $row['request_ts'];
  //$created = $row['created_ts'];
  $org = $row['organization'];
  $title = $row['title'];
  $reason = $row['reason'];
  print '<form method="POST" action="approve_request.php">';
  print "<tr>";
  print "<td>$firstname</td><td>$lastname</td><td>$email</td><td>$uname</td><td>$phone</td><td>$pw</td><td>$requested</td><td>$org</td><td>$title</td><td>$reason</td>";
  print'<td>';
  print '<input type="submit" value="APPROVE"/>';
  print "<input type=\"hidden\" name=\"username\" value=\"$uname\"/>";
  print "<input type=\"hidden\" name=\"firstname\" value=\"$firstname\"/>";
  print "<input type=\"hidden\" name=\"lastname\" value=\"$lastname\"/>";
  print "<input type=\"hidden\" name=\"email\" value=\"$email\"/>";
  print "<input type=\"hidden\" name=\"phone\" value=\"$phone\"/>";
  print "<input type=\"hidden\" name=\"pw\" value=\"$pw\"/>";
  print "<input type=\"hidden\" name=\"org\" value=\"$org\"/>";
  print '</td>';
  print '</tr>';
  print '</form>';	
}
print '</table>';

$sql = "SELECT * FROM " . $AR_TABLENAME . " WHERE created_ts IS NOT NULL";
$result = db_fetch_rows($sql);

$rows = $result['value'];

print '<h1>';
print '<p>Completed Account Requests</p>';
print '</h1>';

print '<table border="1">';
print '<tr>';
print '<th>First Name</th><th>Last Name</th><th>Email Address</th><th>Username</th><th>Phone Number</th><th>PW Hash</th><th>Asked for Account</th>';
print '<th>Account Created</th><th>Institution</th><th>Job Title</th><th>Account Reason</th></tr>';
foreach ($rows as $row) {
  $firstname = $row['first_name'];
  $lastname = $row['last_name'];
  $email = $row['email'];
  $uname = $row['username_requested'];
  $phone = $row['phone'];
  $pw = $row['password_hash'];
  $requested = $row['request_ts'];
  $created = $row['created_ts'];
  $org = $row['organization'];
  $title = $row['title'];
  $reason = $row['reason'];
  print "<td>$firstname</td><td>$lastname</td><td>$email</td><td>$uname</td><td>$phone</td><td>$pw</td><td>$requested</td><td>$created</td><td>$org</td><td>$title</td><td>$reason</td>";
  print '</tr>';
}
print '</table>';
?>

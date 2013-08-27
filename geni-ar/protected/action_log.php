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

$conn = db_conn();
$sql = "SELECT * FROM idp_account_actions";
$result = db_fetch_rows($sql);
$rows = $result['value'];

function get_values($row)
{
  global $uid, $action_time, $performer, $action;

  $uid = $row['uid'];
  $action_time = $row['action_ts'];
  $action_time = substr($action_time,0,16);
  $performer = $row['performer'];
  $action = $row['action_performed'];
}

print '<h1>';
print '<p>Account Request Action Logs</p>';
print '</h1>';

print '<table border="1">';
print '<tr>';
print '<th>Username</th><th>Action</th><th>Date</th><th>Performer</th></tr>';
foreach ($rows as $row) {
  get_values($row);
  print "<tr>";
  print "<td>$uid</td><td>$action</td><td>$action_time</td><td>$performer</td>";
  print '</tr>';
}
print '</table>';

function add_log($uid, $action)
{
	$query_vars[] = 'uid';
	$query_vars[] = 'performer';
	$query_vars[] = 'action_performed';

	$performer = $_SERVER['PHP_AUTH_USER'];
	print $performer;

	$query_values[] = "'$uid'";
	$query_values[] = "'$performer'";
	$query_values[] = "'$action'";

	$sql = "INSERT INTO idp_account_actions (";
	$sql .= implode (',',$query_vars);
	$sql .= ') VALUES (';
	$sql .= implode(',',$query_values);
	$sql .= ')';
	print $sql;
	$result = db_execute_statement($sql);
}

?>
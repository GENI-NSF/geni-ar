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
require_once('ldap_utils.php');
include_once('/etc/geni-ar/settings.php');

if (array_key_exists("PHP_AUTH_USER", $_SERVER)) {
  $performer = $_SERVER["PHP_AUTH_USER"];
} else {
  $performer = "self";
}

/**
 *  Add log entry to action log table
 * 
 */
function add_log($uid, $action)
{
  global $performer;
  $query_vars[] = 'uid';
  $query_vars[] = 'performer';
  $query_vars[] = 'action_performed';
  
  $query_values[] = "'$uid'";
  $query_values[] = "'$performer'";
  $query_values[] = "'$action'";
  
  return create_log($query_vars,$query_values);
}

/**
 *  Add log entry to action log table
 * 
 */
function add_log_with_comment($uid, $action, $comment)
{
  global $performer;  $query_vars[] = 'uid';
  $query_vars[] = 'performer';
  $query_vars[] = 'action_performed';
  $query_vars[] = 'comment';
  
  $query_values[] = "'$uid'";
  $query_values[] = "'$performer'";
  $query_values[] = "'$action'";
  $query_values[] = "'$comment'";

  return create_log($query_vars,$query_values);
}

function create_log($query_vars,$query_values)
{
  $sql = "INSERT INTO idp_account_actions ("; 
  $sql .= implode (',',$query_vars);
  $sql .= ') VALUES (';
  $sql .= implode(',',$query_values);
  $sql .= ')';
  $result = db_execute_statement($sql);
  if ($result['code'] != 0) {
    process_error("Couldn't create log. Postgres database insert failed");
    error_log("Database error executing: $sql");
    error_log("Database error: " . $result['output']);
    exit();
}

  return $result['code'];
}

function add_log_comment($uid, $log, $comment)
{
  $sql = "SELECT id from idp_account_actions where uid = '" . $uid . "' and action_performed = '" . $log . "' ORDER BY id desc";
  $result = db_fetch_rows($sql);
  $rows = $result['value'];
  $id = $rows[0]['id'];
  $sql = "UPDATE idp_account_actions SET comment='" . $comment ."' WHERE id=" . $id;
  $result = db_execute_statement($sql);
  if ($result['code'] != 0) {
    process_error("Couldn't add comment to log. Postgres database update failed");
    exit();
  }

  return $result['code'];
}
?>

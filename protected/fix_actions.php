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
// HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY
// WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE WORK OR THE USE OR OTHER DEALINGS
// IN THE WORK.
//----------------------------------------------------------------------
include_once('/etc/geni-ar/settings.php');
require_once('ldap_utils.php');
require_once('db_utils.php');
require_once('ar_constants.php');

$ldapconn = ldap_setup();
if ($ldapconn === -1) {
  print("LDAP Connection Failed");
  error_log("LDAP Connection Failed");
  exit();
}

$query_vars[] = 'first_name';
$query_vars[] = 'last_name';
$query_vars[] = 'email';
$query_vars[] = 'username_requested';
$query_vars[] = 'phone';
$query_vars[] = 'password_hash';
$query_vars[] = 'organization';
$query_vars[] = 'title';
$query_vars[] = 'reason';
$query_vars[] = 'request_state';

$filter = "(uid=*)";
$result = ldap_search($ldapconn, $base_dn, $filter);
$entry = ldap_first_entry($ldapconn,$result);

while( $entry ) {
  $dn = ldap_get_dn($ldapconn,$entry);
  $old_attrs = ldap_get_attributes( $ldapconn, $entry );
  $attrs = array();
  $attrs['objectClass'][] = "inetOrgPerson";
  $attrs['objectClass'][] = "eduPerson";
  $attrs['objectClass'][] = "posixAccount";
  $attrs['uid'] = $old_attrs['uid'][0];
  $attrs['sn'] = $old_attrs['sn'][0];
  $attrs['givenName'] = $old_attrs['givenName'][0];
  $attrs['cn'] = $old_attrs['cn'][0];
  $attrs['displayName'] = $old_attrs['displayName'][0];
  $attrs['userPassword'] = $old_attrs['userPassword'][0];
  $attrs['mail'] = $old_attrs['mail'][0];
  $attrs['eduPersonAffiliation'][] = "member";
  $attrs['eduPersonAffiliation'] []= "staff";
  $attrs['telephoneNumber'] = $old_attrs['telephoneNumber'][0];
  $attrs['o'] = $old_attrs['o'][0];
  $attrs['homeDirectory'] = "";

  $uid = $attrs['uid'];
  //if there is no associated account request, make an APPROVED account request 
  //and get the request id
  $sql = "SELECT id from idp_account_request where username_requested='" . $uid . "' order by id desc"; 
  $result = db_fetch_rows($sql);
  if ($result['code'] != 0) {
    print("Postgres database query failed");
    exit();
  }
  if (count($result['value']) != 0) {
    $row = $result['value'][0];
    $id = $row['id'];
  } else {
    $conn = db_conn();
    $values = array($attrs['givenName'],$attrs['sn'],$attrs['mail'],$uid,$attrs['telephoneNumber'],$attrs['userPassword'],$attrs['o'],"HISTORIC","HISTORIC","APPROVED");
    $query_vals = array();
    foreach ($values as $val) {
      $query_vals[] = $conn->quote($val,"text");
    }

    $sql = 'INSERT INTO idp_account_request (';
    
    $sql .= implode(',', $query_vars);
    $sql .= ') VALUES (';
    $sql .= implode (',', $query_vals);
    $sql .= ')';

    $result = db_execute_statement($sql, 'insert idp account request');
    if ($result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
      print("<p>Error: Could not create account request for " . $uid . "</p>");
      error_log("Error: Could not create account request for " . $uid);
      error_log(print_r($query_vals,true));
      error_log(print_r($result,true));
      exit();
    } else {
      print("<p>Added account request for user=" . $uid . "</p>");
    }
    //get request id
    $sql = "SELECT id from idp_account_request where username_requested='" . $uid . "' order by id desc";
    $result = db_fetch_rows($sql);
    if ($result['code'] != 0) {
      print("<p>Postgres database query failed</p>");
      exit();
    }
    $row = $result['value'][0];
    $id = $row['id'];
  }

  //if uidNumber is not in the account entry, replace with an updated entry
  if (!array_key_exists('uidNumber',$old_attrs)) {
    $attrs['uidNumber'] = $id;
    $attrs['gidNumber'] = $id;
    $ret = ldap_delete($ldapconn,$dn);
    if ($ret === false) {
      print ("<p>ERROR: Failed to delete old ldap account for " . $uid . "</p>");
      error_log ("ERROR: Failed to delete old ldap account for " . $uid );
      exit();
    }
    $ret = ldap_add($ldapconn,$dn,$attrs);
    if ($ret === false) {
      print ("<p>ERROR: Failed to create new ldap account for " . $uid . "</p>");
      error_log ("ERROR: Failed to create new ldap account for " . $uid);
      exit();
    } else {
      print("<p>Created updated account entry for user=" . $uid . "</p>");
    }
    }
  $entry = ldap_next_entry( $ldapconn, $entry );
}
print ("<br></br>");
print("Account updates complete.");

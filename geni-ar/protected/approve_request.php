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

require_once('ldap_utils.php');
require_once('db_utils.php');
require_once('ar_constants.php');

$mypath = '/usr/share/geni-ar/lib/php' . PATH_SEPARATOR . '/etc/geni-ar';
set_include_path($mypath . PATH_SEPARATOR . get_include_path());

//Add account to ldap database

$ldapconn = ldap_setup();

$base_dn = "dc=shib-idp2,dc=gpolab,dc=bbn,dc=com";

$uid = $_REQUEST['username'];
print $uid;
$sn =  $_REQUEST['lastname'];
$givenName =  $_REQUEST['firstname'];
$sn =  $_REQUEST['lastname'];
$mail =  $_REQUEST['email'];
$phone =  $_REQUEST['phone'];
$pw =  $_REQUEST['pw'];
$org =  $_REQUEST['org'];

$new_dn = "uid=" . $uid . ",ou=people,dc=shib-idp2,dc=gpolab,dc=bbn,dc=com";
$attrs['objectClass'][] = "inetOrgPerson";
$attrs['objectClass'][] = "eduPerson";
$attrs['uid'] = $uid;
$attrs['sn'] = $sn;
$attrs['givenName'] = $givenName;
$attrs['cn'] = $givenName . " " . $sn;
$attrs['displayName'] = $givenName . " " . $sn;
# default password is geni
$attrs['userPassword'] = $pw;
$attrs['mail'] = $mail;
$attrs['eduPersonAffiliation'][] = "member";
$attrs['eduPersonAffiliation'] []= "staff";
$attrs['telephoneNumber'] = $phone;
$attrs['o'] = $org;

$ret = ldap_add($ldapconn, $new_dn, $attrs);
//print $ret;
ldap_close($ldapconn);

// Now set created timestamp in postgres db
$sql = "UPDATE " . $AR_TABLENAME . ' SET created_ts=now() where username_requested =\'' . $uid . '\'';
print $sql;
$result = db_execute_statement($sql);

?>
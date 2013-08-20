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

include_once('/etc/geni-ar/settings.php');

function ldap_setup()
{
  global $ldap_host;
  global $ldap_port;
  global $ldaprdn;
  global $ldappass;

  // Note: try "ldap://" instead of host
  // connect to ldap server
  $ldapconn = ldap_connect($ldap_host, $ldap_port)
    or die("Could not connect to LDAP server.");

  if (ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3)) {
    echo "Using LDAPv3";
  } else {
    echo "Failed to set protocol version to 3";
  }

  // This is necessary -- without it, bind fails
  // XXX Check result
  ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);

  // binding to ldap server
  $ldapbind = ldap_bind($ldapconn, $ldaprdn, $ldappass);

  // verify binding
  if ($ldapbind) {
    echo "LDAP bind successful...";
  } else {
    echo "LDAP bind failed...";
  }
  return $ldapconn;
}

?>
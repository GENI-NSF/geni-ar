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

// Load from settings
$ldaprdn  = 'cn=admin,dc=shib-idp2,dc=gpolab,dc=bbn,dc=com';

// Load from settings
$ldappass = 'PASSWORD-HERE';  // associated password

// Load from settings
$ldap_host = 'localhost';
$ldap_port = 389;

// Note: try "ldap://" instead of host

// connect to ldap server
$ldapconn = ldap_connect($ldap_host, $ldap_port)
     or die("Could not connect to LDAP server.");

if (ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3)) {
    echo "Using LDAPv3";
} else {
    echo "Failed to set protocol version to 3";
}

// This is necessary -- without it, bind fails
// XXX Check result
ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);

if ($ldapconn) {
//    $r = ldap_bind($ldapconn);
//    if ($r) {
//      echo "Anonymous bind successful</br>";
//    } else {
//      echo "Error: " . ldap_error($r) . "</br>";
//      echo "Anonymous bind failed</br>";
//    }

    // Root node for search
    $dn = "dc=shib-idp2,dc=gpolab,dc=bbn,dc=com";
    // I don't understand filters...
    $filter='(&(objectClass=inetOrgPerson)(uid=*))';  // single filter
    // Fields to return in result
    $justthese = array("uid", "sn", "givenname", "mail");
    $sr=ldap_search($ldapconn, $dn, $filter, $justthese);
    if ($sr) {
        $info = ldap_get_entries($ldapconn, $sr);
        print_r($info);
        echo "</br></br>\n";
        echo $info["count"]." entries returned\n";
    } else {
      echo "No search result</br>\n";
    }

    // binding to ldap server
    $ldapbind = ldap_bind($ldapconn, $ldaprdn, $ldappass);

    // verify binding
    if ($ldapbind) {
        echo "LDAP bind successful...";
    } else {
        echo "LDAP bind failed...";
    }
}

?>

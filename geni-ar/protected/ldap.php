<?php

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

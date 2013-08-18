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

$mypath = '/usr/share/geni-ar/lib/php' . PATH_SEPARATOR . '/etc/geni-ar';
set_include_path($mypath . PATH_SEPARATOR . get_include_path());

print 'APPROVED ';

$username = $_REQUEST['username'];
print $username;
//$ldap_conn = ldap_connect("macomb.gpolab.bbn.com");
//error_log($ldap_conn);

//$result = shell_exec("ldapsearch -xLLL -b 'dc=shib-idp2,dc=gpolab,dc=bbn,dc=com' uid=* sn givenName cn");
$cmd = "ldapadd -D cn=" . $username . ",dc=gpolab, dc=bbn, dc=com -f ldap/users.gpolab.bbn.com.ldif";
$result = shell_exec($cmd);

print $result;
<?php
//----------------------------------------------------------------------
// Copyright (c) 2013-2016 Raytheon BBN Technologies
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

/*
 * The location of the database (DSN = "data source name").  This one
 * is user "scott" with password "tiger" connecting to database "portal"
 * on host "localhost".
 *
 * See http://pear.php.net/manual/en/package.database.mdb2.intro-dsn.php
 */
$db_dsn = 'pgsql://scott:tiger@localhost/accreq';

/*
 * People who should be notified that there are new accounts waiting
 * to be approved
 */
$idp_approval_email = 'approval@idp.net';

/*
 * People who should be notified when someone clicks a button like
 * "e-mail leads"
 */
$idp_leads_email = 'leads@idp.net'; 

/*
 * People who should be notified when an action has been taken
 */
$idp_audit_email = 'audit@idp.net';

/*
 * base url for acct mgmt tool
 */
$acct_manager_url = 'https://arsystem.idplab.idp.com/mainpage';

/*
 * LDAP variables
 */

$base_dn = "dc=arsystem,dc=testlab,dc=idp,dc=com"; //for searches
$user_dn = ",ou=people,dc=arsystem,dc=testlab,dc=idp,dc=com"; //append to uid
$ldaprdn  = 'cn=admin,dc=arsystem,dc=testlab,dc=idp,dc=com'; //for adding/modifying accounts
$ldappass = 'XyZaBc'; //for adding/modifying accounts

$ldap_host = 'localhost';
$ldap_port = 389;

?>

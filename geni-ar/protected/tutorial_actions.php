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
// HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY
// WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE WORK OR THE USE OR OTHER DEALINGS
// IN THE WORK.
//----------------------------------------------------------------------
include_once('/etc/geni-ar/settings.php');
require_once('ldap_utils.php');
require_once('db_utils.php');
require_once('ar_constants.php');
require_once('log_actions.php');

/**
 * A class to create a Salted SHA password hash.
 *
 * From http://ten-fingers-and-a-brain.com/2009/08/ssha-php/
 */
class SSHA
{

  public static function newSalt()
  {
    return chr(rand(0,255)).chr(rand(0,255)).chr(rand(0,255)).chr(rand(0,255));
  }

  public static function hash($pass,$salt)
  {
    return '{SSHA}'.base64_encode(sha1($pass.$salt,true).$salt);
  }

  public static function getSalt($hash)
  {
    return substr(base64_decode(substr($hash,-32)),-4);
  }

  public static function newHash($pass)
  {
    return self::hash($pass,self::newSalt());
  }

  public static function verifyPassword($pass,$hash)
  {
    return $hash == self::hash($pass,self::getSalt($hash));
  }

}


//First make sure we can connect to ldap
$ldapconn = ldap_setup();
if ($ldapconn === -1) {
  process_error("LDAP Connection Failed");
  exit();
}

//get request data
$num = $_REQUEST['num'];
$user_prefix = $_REQUEST['userprefix'];
$pw_prefix =  $_REQUEST['pwprefix'];
$org_email =  $_REQUEST['email'];                            
$org_phone =  $_REQUEST['phone'];
$desc = $_REQUEST['desc'];   

//check for valid username
if (strlen($user_prefix) > 6) {
  process_error("ERROR: username prefix cannot be longer than 6 characters.");
  exit();
  }
  if (!preg_match('/^[a-z0-9]{1,6}$/', $user_prefix)) {
    process_error("ERROR: username must consist of lowercase letters and numbers only.");
    exit();
  }

//now for each username, check that an account doesn't already exist
//then create an "APPROVED" request in db
for ($x=1; $x<=$num; $x++)
  {
    $usernum = strval($x);
    if (strlen($usernum) == 1)
      {
	$usernum = "0" . $usernum;
      }
    $uid = $user_prefix . $usernum;
    if (ldap_check_account($ldapconn,$uid)) {
      print ("ERROR: username " . $uid . " is already in use");
      exit();
    }
  }

//Ready to create requests and accounts, First log
$comment = "Created " . $num . "tutorial accounts for Tutorial: " . $desc . " for " . $org_email;
$ret = add_log_with_comment($user_prefix,"Created Tutorial Accounts",$comment);
if ($ret != 0) {
  process_error("ERROR: Logging failed.  Will not create tutorial requests or accounts.");
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

for ($x=1; $x<=intval($num); $x++)
  {
    $usernum = strval($x);
    if (strlen($usernum) == 1)
      {
	$usernum = "0" . $usernum;
      }
    $uid = $user_prefix . $usernum;

    //create the password hash
    $pw = $pw_prefix . $usernum;
    $pw_hash = SSHA::newHash($pw);
    $lastname = "User" . $usernum;
    $email = $uid . "@gpolab.bbn.com";

    $conn = db_conn();

    $query_vals = array();
    $query_vals[] = $conn->quote($desc,"text");
    $query_vals[] = $conn->quote($lastname,"text");
    $query_vals[] = $conn->quote($email,"text");
    $query_vals[] = $conn->quote($uid,"text");
    $query_vals[] = $conn->quote($org_phone,"text");
    $query_vals[] = $conn->quote($pw_hash,"text");
    $query_vals[] = $conn->quote("BBN","text");
    $query_vals[] = $conn->quote("Tutorial User","text");
    $query_vals[] = $conn->quote($desc,"text");
    $query_vals[] = $conn->quote("APPROVED","text");

    error_log(print_r($query_vars,true));
    error_log(print_r($query_vals,true));

    $sql = 'INSERT INTO idp_account_request (';

    $sql .= implode(',', $query_vars);
    $sql .= ') VALUES (';
    $sql .= implode (',', $query_vals);
    $sql .= ')';

    error_log($sql);

    $result = db_execute_statement($sql, 'insert idp account request');
    error_log(print_r($result,true));
	    
    if ($result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
      $msg = "Could not create request for " . $uid . ". Aborting process.  Accounts created for users with lower numbers.";
      process_error($msg);
      add_log_with_comment($uid,"Tutorial Account Creation Failure",$msg);
      exit();
    }

    //Now create ldap accounts
    $new_dn = get_userdn($uid);
    $attrs = array();
    $attrs['objectClass'][] = "inetOrgPerson";
    $attrs['objectClass'][] = "eduPerson";
    $attrs['uid'] = $uid;
    $attrs['sn'] = $lastname;
    $attrs['givenName'] = $desc;
    $fullname = $desc . " " . $lastname;
    $attrs['cn'] = $fullname;
    $attrs['displayName'] = $fullname;
    $attrs['userPassword'] = $pw_hash;
    $attrs['mail'] = $email;
    $attrs['eduPersonAffiliation'][] = "member";
    $attrs['eduPersonAffiliation'] []= "staff";
    $attrs['telephoneNumber'] = $org_phone;
    $attrs['o'] = "BBN";

    $ret = ldap_add($ldapconn, $new_dn, $attrs);
    if ($ret === false) {
      $msg = "Failed to create Tutorial Account for " . $uid . ". Accounts created for users with lower numbers.";
      process_error ($msg);
      add_log_with_comment($uid, "Tutorial Account Creation Failed", $msg);
      exit();
    }
    
    // Now set created timestamp in postgres db
    $sql = "UPDATE " . $AR_TABLENAME . ' SET created_ts=now() at time zone \'utc\' where username_requested =\'' . $uid . '\'';
    $result = db_execute_statement($sql);
  }

ldap_close($ldapconn);

//send email to organizer
$filename = "/etc/geni-ar/tutorial-email.txt";
$file = fopen( $filename, "r" );
if( $file == false )
  {
    process_error ( "Error in opening file " . $filename . " Did not send email.");
  }
$filesize = filesize( $filename );
$filetext = fread( $file, $filesize );
fclose( $file );

$filetext = str_replace("<description>",$desc,$filetext);
$filetext = str_replace("<username_prefix>",$user_prefix,$filetext);
$filetext = str_replace("<password_prefix>",$pw_prefix,$filetext);
$filetext = str_replace("<numaccounts>",$usernum,$filetext);

$subject = "Accounts for " . $desc;
mail($org_email,$subject,$filetext);

header("Location: " . $acct_manager_url);
    
function process_error($msg)
{
  global $acct_manager_url;

  print ($msg);
  print ('<br><br>');
  print ('<a href="' . $acct_manager_url . '">Cancel</a>'); 
}

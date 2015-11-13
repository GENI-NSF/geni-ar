<?php
//----------------------------------------------------------------------
// Copyright (c) 2015 Raytheon BBN Technologies
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

require_once('ldap_utils.php');
require_once('db_utils.php');
include_once('/etc/geni-ar/settings.php');

/**
 * Generate a random ID comprised of numbers and upper case characters.
 */
function random_id($width=6) {
    $result = '';
    for ($i=0; $i < $width; $i++) { 
        $result .= base_convert(strval(rand(0, 35)), 10, 36);
    }
    return strtoupper($result);
}

function create_newpasswd_link($base_path, $id1, $id2) {
    global $acct_manager_url;
    $base_url = parse_url($acct_manager_url);
    $path = dirname($base_path);
    $path .= "/newpasswd.php/$id1/$id2";
    $url = $base_url["scheme"] . "://" . $base_url["host"] . "$path";
    return $url;
}

function insert_passwd_reset($email, $nonce) {
    # insert into idp_passwd_reset (email, nonce)
    #     values ('tmitchel@bbn.com', 'DUH4Z0KT')
    #     returning id, created;

    # db_execute_statement($stmt, $msg = "", $rollback_on_error = false)
    $db_conn = db_conn();
    $sql = "insert into idp_passwd_reset (email, nonce) values (";
    $sql .= $db_conn->quote($email, 'text');
    $sql .= ', ';
    $sql .= $db_conn->quote($nonce, 'text');
    $sql .= ") returning id, created";

    $db_result = db_fetch_row($sql, "insert idp_passwd_reset");
    $result = false;
    if ($db_result[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) {
        $result = $db_result[RESPONSE_ARGUMENT::VALUE];
    } else {
        error_log("Error inserting password reset record: "
                  . $db_result[RESPONSE_ARGUMENT::OUTPUT]);
    }

    return $result;
}

function delete_expired_resets($hours) {
    $sql = "delete from idp_passwd_reset";
    $sql .= " where created <";
    $sql .= "  (now()  at time zone 'utc') - interval '$hours hours';";
    $result = db_execute_statement($sql);
    if ($result[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) {
        $rows = $result[RESPONSE_ARGUMENT::VALUE];
        error_log("Deleted $rows old records");
    } else {
        error_log("Error deleting old records: "
                  . $result[RESPONSE_ARGUMENT::OUTPUT]);
    }
    return $result == RESPONSE_ERROR::NONE;
}

#----------------------------------------------------------------------
# Delete things older than 10 minutes. Convert to 24 hours, or X hours.
# delete from idp_passwd_reset
#    where created < (now()  at time zone 'utc') - interval '10 minutes';
#----------------------------------------------------------------------

$errors = array();

$email = null;
$EMAIL_KEY = 'username';
if (array_key_exists($EMAIL_KEY, $_REQUEST)) {
    $email = $_REQUEST[$EMAIL_KEY];
}
$email = 'tmitchel@bbn.com';
if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Invalid email address";
} else {
    $ldapconn = ldap_setup();
    if ($ldapconn === -1) {
        $errors[] = "Internal Error";
    } else {
        $known_email = ldap_check_email($ldapconn, $email);
        if (! $known_email) {
            $errors[] = "Invalid email address";
        }
        // Enter a record in the database for this request
        // Generate a link to be clicked
        // Send an email with the link
        $nonce = random_id(8);
        $db_result = insert_passwd_reset($email, $nonce);
        $db_id = $db_result['id'];
        $change_url = create_newpasswd_link($_SERVER['PHP_SELF'],
                                            $db_id, $nonce);
        delete_expired_resets(1);
    }
}


//
// Use INSERT INTO .... RETURNING id; in PostgreSQL to
// discover the id of the last row inserted.
//

?>
<!DOCTYPE html>
<html>
<head>
<title>GENI: Reset Password</title>
<link type="text/css" href="kmtool.css" rel="Stylesheet"/>
<style type="text/css">
label.input {font-weight:bold;}
span.required {color:red;}
</style>
</head>
<body>
<pre>
<?php echo $change_url; ?>
</pre>
<hr/>
<pre>
<?php print_r($errors); ?>
</pre>
<hr/>
<pre>
<?php print_r($db_result); ?>
</pre>
</body>
</html>

?>

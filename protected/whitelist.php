<?php
//----------------------------------------------------------------------
// Copyright (c) 2016 Raytheon BBN Technologies
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

// Insert a new institution into the idp_whitlelist table
function insert_to_whitelist($institution) {
    $db_conn = db_conn();
    $sql = "insert into idp_whitelist (institution) values ("
         . $db_conn->quote($institution, 'text') . ")";

    $db_result = db_fetch_row($sql, "insert idp_whitelist");
    if ($db_result[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) {
        return true;
    } else {
        error_log("Error inserting password reset record: "
                  . $db_result[RESPONSE_ARGUMENT::OUTPUT]);
        return false;
    }
}

function print_whitelist() {
    $db_conn = db_conn();
    $sql = "SELECT * from idp_whitelist";
    $db_result = db_fetch_rows($sql, "insert idp_passwd_reset");

    if ($db_result[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) {
        $institutions = $db_result[RESPONSE_ARGUMENT::VALUE];
        print "<ul>";
        foreach ($institutions as $inst) {
            print "<li>{$inst['institution']}</li>";
        }
        print "</ul>";
    } else {
        error_log("Error inserting password reset record: "
                  . $db_result[RESPONSE_ARGUMENT::OUTPUT]);
        print "<p>Error. Couldn't print whitelist. Check the logs or just go to the db yourself</p>";
    }
}

?>

<!DOCTYPE html>
<html>
<head>
<title>GENI IDP Whitelist</title>
<link href="https://fonts.googleapis.com/css?family=Open+Sans:400,700" rel="stylesheet" type="text/css">
<link type="text/css" href="geni-ar.css" rel="Stylesheet"/>
</head>
<body>

<div id="content" class='card' style="width:500px; margin: 30px auto">
<h2>Whitelist page</h2>

<?php

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $message = '';
    if (array_key_exists('institution', $_REQUEST)) {
        $institution = $_REQUEST['institution'];
        if (insert_to_whitelist($institution)) {
            $message = "Successfully added $institution";
        } else {
            $message = "Failed to add $institution. Try again or check logs.";
        }
    } else {
        $message = "No institution given";
    }
    print_whitelist();
    print $message;
} else {
    print_whitelist();
}

?>

<form action="whitelist.php" method="POST">
    <input name="institution" size="50" placeholder='Institution name' required>
    <input type="submit" value='Add' />
</form>

</div>
</body>
</html>

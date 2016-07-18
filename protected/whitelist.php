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
require_once('response_format.php');

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

function delete_from_whitelist($institutions) {
    $to_delete = array();
    foreach($institutions as $inst) {
        $to_delete[] = $inst;
    }
    if (count($to_delete) > 0) {
        $sql = "DELETE FROM idp_whitelist WHERE id IN (" . implode(", ", $to_delete) . ")";
        $result = db_execute_statement($sql); 
        if ($result[RESPONSE_ARGUMENT::CODE] != RESPONSE_ERROR::NONE) {
            error_log("Error deleting old records: "
                      . $result[RESPONSE_ARGUMENT::OUTPUT]);
            return "Error deleting from whitelist";
        } else {
            return "Successfully deleted " . count($to_delete) . " institutions from whitelist";
        }
    } else {
        return "Nothing to delete.";
    }
}

function print_whitelist() {
    $db_conn = db_conn();
    $sql = "SELECT * from idp_whitelist ORDER BY institution";
    $db_result = db_fetch_rows($sql, "read idp_whitelist");

    if ($db_result[RESPONSE_ARGUMENT::CODE] == RESPONSE_ERROR::NONE) {
        $institutions = $db_result[RESPONSE_ARGUMENT::VALUE];
        if (count($institutions) > 0) {
	    print "<p>Listed institutions are whitelisted; users using a confirmed email address from this insitution may use GENI without manual approval.</p>";
            print "<p>Check a domain to select it for deletion, or add a new institution below.</p>";
            print "<form action='whitelist.php' method='POST'><ul style='list-style-type:none'>";
            $i = 0;
            foreach ($institutions as $inst) {
                print "<li><input class='instbox' type='checkbox' name='$i' value='{$inst['id']}'/>{$inst['institution']}</li>";
                $i++;
            }
            print "<input type='hidden' name='delete' value='delete'>";
            print "<input type='submit' value='Delete' id='deletebutton' style='display:none;' />";
            print "</ul></form>";
        } else {
            print "<p>No institutions are on the whitelist</p>";
        }
    } else {
        error_log("Error inserting password reset record: "
                  . $db_result[RESPONSE_ARGUMENT::OUTPUT]);
        print "<p>Error. Couldn't print whitelist. Check the logs or just go to the db yourself</p>";
    }
}

require_once("header.php");
show_header("GENI IdP Whitelisted Domain Management", array());
?>

<script type="text/javascript">
    $(document).ready(function(){
        $(".instbox").change(function(){
            if($(".instbox:checked").length > 0) {
                $("#deletebutton").show();
            } else {
                $("#deletebutton").hide();
            }
        });
    });
</script>

<h2  style='margin-top: 80px;' class='card'>Whitelisted Domains</h2>
<div id="content" class='card' style="width:500px; margin: 30px auto">

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
    } else if (array_key_exists('delete', $_REQUEST)) {
        unset($_REQUEST['delete']);
        $message = delete_from_whitelist($_REQUEST);
    } else {
        $message = "No post params given";
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

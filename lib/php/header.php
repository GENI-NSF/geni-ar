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

function show_header($title, $table_ids) {
  echo "<!DOCTYPE html>";
  echo "<html lang='en'>";
  echo "<head>";
  echo "<meta charset='utf-8'>";
  echo "<title>$title</title>";
  echo "<link href='https://fonts.googleapis.com/css?family=Open+Sans:400,700' rel='stylesheet' type='text/css'>";
  echo "<link rel='stylesheet' href='geni-ar.css'>";
  echo "<script type='text/javascript' charset='utf8' src='https://ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js'></script>";
  echo "<script type='text/javascript' charset='utf8' src='https://cdn.datatables.net/1.10.7/js/jquery.dataTables.js'></script>";
  echo "<script type='text/javascript' charset='utf8' src='cards.js'></script>";

  echo "<script type='text/javascript'>";
  echo "$(document).ready( function () {";
  foreach ($table_ids as $table_id) {
    echo "$('$table_id').DataTable({paging: false});";
  }
  echo "});";
  
  echo "</script>";
  echo "</head>";
  echo "<body>";

  echo "<ul id='header'>";
  echo "<li class='headerlink'><a href='tutorial_confirmation.php'>Create Tutorial Accounts</a></li>";
  echo "<li class='headerlink'><a href='action_log.php?uid=ALL'>Account Action Logs</a></li>";
  echo "<li class='headerlink'><a href='display_accounts.php'>Manage Accounts</a></li>";
  echo "<li class='headerlink'><a href='display_requests.php'>Manage Account Requests</a></li>";
  echo "</ul> ";

  echo "<div id='content-outer'>";
}




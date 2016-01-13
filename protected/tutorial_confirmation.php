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
include_once('/etc/geni-ar/settings.php');
require_once('header.php');

global $acct_manager_url;

show_header('Create Tutorial Accounts', array());


print '<h1 style="margin-top: 60px;">Tutorial Account Creation</h1>';

print '<div class="card">';
print '<h2>';
print '<p>You must open a ticket with infra and verify that it\'s approved before creating tutorial accounts</p>';
print '</h2>';
print '<h2>';
print '<p>Are you sure that you are ready to create tutorial accounts?</p>';
print '</h2>';

print '<form method="POST" action="tutorial_requests.php">';
print '<input type="submit" value="YES" style="width:100px;height:50px"/>';
print "</form>";

print '<form method="POST" action="index.html">';
print '<input type="submit" value="CANCEL" style="width:100px;height:50px"/>';
print "</form>";

print '</div></div></body></html>';

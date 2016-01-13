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

global $acct_manager_url;

print '<head><title>Create Tutorial Accounts</title></head>';
print '<a href="' . $acct_manager_url . '">Return to main page</a>';

print '<h1>';
print '<p>Tutorial Account Creation</p>';
print '</h1>';

print '<h3>';
print '<p>Reminder: Open a ticket with infra and verify that it\'s approved before creating tutorial accounts</p>';
print '</h3>';

print '<form method="POST" action="tutorial_actions.php">';
print '<p>Tutorial Description: <input type="text" name="desc" required></p>';
print '<p>User Prefix: <input type="text" name="userprefix" required></p>';
print '<p>Password Prefix: <input type="text" name="pwprefix" required></p>';
print '<p>Organizer Email: <input type="email" name="email" required></p>';
print '<p>Organizer Phone: <input type="text" name="phone" required></p>';
print '<p>Number of Accounts: <input type="number" name="num" required></p>';
print '<br><br>';
print '<input type="submit" value="CREATE ACCOUNTS"/>';
print "</form>";
print '<form method="POST" action="index.html">';
print '<input type="submit" value="CANCEL"/>';
print "</form>";

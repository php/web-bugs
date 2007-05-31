<?php

/**
 * Procedure for emailing a password reminder to a user
 *
 * This source file is subject to version 3.0 of the PHP license,
 * that is bundled with this package in the file LICENSE, and is
 * available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_0.txt.
 * If you did not receive a copy of the PHP license and are unable to
 * obtain it through the world-wide-web, please send a note to
 * license@php.net so we can mail you a copy immediately.
 *
 * @category  pearweb
 * @package   Bugs
 * @copyright Copyright (c) 1997-2005 The PHP Group
 * @license   http://www.php.net/license/3_0.txt  PHP License
 * @version   $Id$
 */

/**
 * Obtain common includes
 */
require_once './include/prepend.inc';

$errors  = array();
$success = '';

if (isset($_GET['bug_id'])) {

    // Clean up the bug id
    $bug_id = ereg_replace ("[^0-9]+", "", $_GET['bug_id']);

    if ($bug_id != "") {
        // Try to find the email and the password
        $query = "SELECT email, passwd FROM bugdb WHERE id = '" . $bug_id . "'";

        // Run the query
        $row = $dbh->getRow($query, null, DB_FETCHMODE_ASSOC);

        if (is_null($row)) {
            $errors[] = 'Invalid bug id provided: #' . $bug_id;
        } else {
            if (empty($row['passwd'])) {
                $errors[] = "No password found for #$bug_id bug report, sorry.";
            } else {
                $passwd = $row['passwd'];

                $resp = mail($row['email'],
                             'Password for '.$siteBig.' bug report #' . $bug_id,
                             'The password for '.$siteBig.' bug report #' . $bug_id
                             . ' is ' . $passwd . '.',
                             'From: noreply@php.net');

                if ($resp) {
                    $success = 'The password for bug report #' . $bug_id
                               . ' has been sent to '
                               . spam_protect($row['email'], 'text');
                } else {
                    $errors[] = 'Sorry. Mail can not be sent at this time.'
                                . 'Please try again later.';
                }
            }
        }
    } else {
        $errors[] = 'Invalid bug id provided: #' . $bug_id;
    }
}

response_header("Bug Report Password Finder");

echo '<h1>Bug Report Password Finder</h1>' . "\n";

display_bug_error($errors);

if ($success) {
    display_bug_success($success);
}

?>

<p>
If you need to modify a bug report that you submitted, but have
forgotten what password you used, this utility can help you.
</p>

<p>
Enter in the number of the bug report, press the Send button
and the password will be mailed to the email address specified
in the bug report.
</p>

<form method="get" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
<p><b>Bug Report ID:</b> #<input type="text" size="20" name="bug_id">
<input type="submit" value="Send"></p>
</form>

<?php
response_footer();

<?php

/**
 * Procedure for emailing a password reminder to a user
 */

// Obtain common includes
require_once '../include/prepend.inc';

$errors  = array();
$success = false;
$bug_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if (!empty($bug_id)) {
	// Try to find the email and the password
	$query = "SELECT email, passwd FROM bugdb WHERE id = '{$bug_id}'";

	// Run the query
	$row = $dbh->prepare($query)->execute()->fetchRow(MDB2_FETCHMODE_ASSOC);

	if (is_null($row)) {
		$errors[] = "Invalid bug id provided: #{$bug_id}";
	} else {
		if (empty($row['passwd'])) {
			$errors[] = "No password found for #$bug_id bug report, sorry.";
		} else {
			$resp = mail($row['email'],
						 "Password for {$siteBig} bug report #{$bug_id}",
						 "The password for {$siteBig} bug report #{$bug_id} is {$row['passwd']}",
						 'From: noreply@php.net');

			if ($resp) {
				$success = "The password for bug report #{$bug_id} has been sent to " . spam_protect($row['email'], 'text');
			} else {
				$errors[] = 'Sorry. Mail can not be sent at this time, please try again later.';
			}
		}
	}
} else {
	$errors[] = 'Invalid bug id provided.';
}

response_header('Bug Report Password Finder');

echo "<h1>Bug Report Password Finder</h1>\n";

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

<form method="get" action="bug-pwd-finder.php">
<p><b>Bug Report ID:</b> #<input type="text" size="20" name="id">
<input type="submit" value="Send"></p>
</form>

<?php response_footer();

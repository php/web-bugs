<?php

/* Procedure for emailing a password reminder to a user */

use App\Utils\Captcha;

// Obtain common includes
require_once '../include/prepend.php';

// Start session (for captcha!)
session_start();

$captcha = new Captcha();

$errors  = [];
$success = false;
$bug_id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
$bug_id = $bug_id ? $bug_id : '';

if (isset($_POST['captcha']) && $bug_id != '') {
	 // Check if session answer is set, then compare it with the post captcha value.
	 // If it's not the same, then it's an incorrect password.
	if (!isset($_SESSION['answer']) || $_POST['captcha'] != $_SESSION['answer']) {
		$errors[] = 'Incorrect Captcha';
	}

	// Try to find the email and the password
	if (empty($errors)) {
		$query = "SELECT email, passwd FROM bugdb WHERE id = '{$bug_id}'";

		// Run the query
		$row = $dbh->prepare($query)->execute()->fetch();

		if (is_null($row)) {
			$errors[] = "Invalid bug id provided: #{$bug_id}";
		} else {
			if (empty($row['passwd'])) {
				$errors[] = "No password found for #$bug_id bug report, sorry.";
			} else {
				$new_passwd = bugs_gen_passwd();

				$dbh->prepare(
				'UPDATE bugdb
				 SET passwd = ?
				 WHERE id = ?
				')->execute([bugs_get_hash($new_passwd), $bug_id]);

				$resp = bugs_mail($row['email'],
						 "Password for {$siteBig} bug report #{$bug_id}",
						 "The password for {$siteBig} bug report #{$bug_id} has been set to: {$new_passwd}",
						 'From: noreply@php.net');

				if ($resp) {
					$success = "The password for bug report #{$bug_id} has been sent to the address associated with this report.";
				} else {
					$errors[] = 'Sorry. Mail can not be sent at this time, please try again later.';
				}
			}
		}
	}
}

// Authenticate
bugs_authenticate($user, $pw, $logged_in, $user_flags);

response_header('Bug Report Password Finder');

echo "<h1>Bug Report Password Finder</h1>\n";

display_bug_error($errors);

if ($success) {
	echo '<div class="success">'.$success.'</div>';
}

$_SESSION['answer'] = $captcha->getAnswer();

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

<form method="post" action="bug-pwd-finder.php">
<p><b>Bug Report ID:</b> #<input type="text" size="20" name="id" value="<?php echo $bug_id; ?>">
<p><b>Solve the problem:<br><?php echo $captcha->getQuestion(); ?> <input type="text" name="captcha"></p>

<input type="submit" value="Send"></p>
</form>

<?php response_footer();

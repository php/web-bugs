<?php

use App\Repository\BugRepository;
use App\Repository\ReasonRepository;

session_start();

/* Admin interface for closing bug reports via direct link */

// Obtain common includes
require_once '../include/prepend.php';

$bug_id = (int) $_REQUEST['id'];

if (!$bug_id) {
	redirect('index.php');
}

// Authenticate
bugs_authenticate($user, $pw, $logged_in, $user_flags);

// fetch info about the bug into $bug
$bugRepository = new BugRepository($dbh);
$bug = $bugRepository->findOneById($bug_id);

if (!is_array($bug)) {
	response_header('No Such Bug');
	display_bug_error("No such bug #{$bug_id}");
	response_footer();
	exit;
}

// If bug exists, continue..
$RESOLVE_REASONS = $FIX_VARIATIONS = $errors = [];

if ($logged_in != 'developer') {
	$errors[] = 'The username or password you supplied was incorrect.';
}

$project = !empty($_GET['project']) ? $_GET['project'] : false;

$reasonRepository = new ReasonRepository($dbh);
list($RESOLVE_REASONS, $FIX_VARIATIONS) = $reasonRepository->findByProject($site);

// Handle reason / comments
$reason = filter_var($_REQUEST['r'], FILTER_SANITIZE_STRING);
$ncomment = isset($_POST['ncomment']) ? trim($_POST['ncomment']) : '';

if (!$reason || !isset($RESOLVE_REASONS[$reason])) {
	$errors[] = 'You have to use a valid reason to resolve this bug.';
}

if (isset($RESOLVE_REASONS[$reason]) && $RESOLVE_REASONS[$reason]['status'] == 'Not a bug' && $ncomment == '') {
	$errors[] = 'You must provide a comment when marking a bug \'Not a bug\'';
}

// Handle errors
if ($errors) {
	response_header('Error in resolving bug');
	display_bug_error($errors);
?>

<form method="post" action="fix.php">
	<input type="hidden" name="id" value="<?php echo $bug_id; ?>">

<?php // Note: same block is used also in bug.php!
if ($logged_in == 'developer') {
?>
	<div class="explain">
		Welcome back, <?php echo $user; ?>! (Not <?php echo $user; ?>?
		<a href="logout.php">Log out.</a>)
	</div>
<?php } else { ?>
	<div class="explain">
		Welcome! If you don't have a Git account, you can't do anything here.<br>
		You can <a href="bug.php?id=<?php echo $bug_id; ?>&amp;edit=3">add a comment by following this link</a>
		or if you reported this bug, you can <a href="bug.php?id=<?php echo $bug_id; ?>&amp;edit=2">edit this bug over here</a>.
		<div class="details">
			<label for="svnuser">php.net Username:</label>
			<input type="text" id="svnuser" name="user" value="<?php echo htmlspecialchars($user) ?>" size="10" maxlength="20">
			<label for="svnpw">php.net Password:</label>
			<input type="password" id="svnpw" name="pw" value="<?php echo htmlspecialchars($pw) ?>" size="10">
			<label for="save">Remember:</label><input style="vertical-align:middle;" type="checkbox" id="save" name="save" <?php echo !empty($_POST['save']) ? 'checked="checked"' : ''; ?>>
		</div>
	</div>
<?php } ?>
	<table>
		<tr>
			<th><a href="quick-fix-desc.php">Reason:</a></th>
			<td colspan="5">
				<select name="r">
					<?php echo show_reason_types($reason); ?>
				</select>
			</td>
		</tr>
		<tr>
			<th>Note:</th>
			<td colspan="5"><textarea cols="80" rows="8" name="ncomment" wrap="physical"><?php echo htmlspecialchars($ncomment); ?></textarea></td>
		</tr>
	</table>
	<input type="submit" value="Resolve">
</form>
<?php
	response_footer();
	exit;
}

// Update bug
$status = $RESOLVE_REASONS[$reason]['status'];
if (isset($FIX_VARIATIONS[$reason][$bug['bug_type']])) {
	$qftext = $FIX_VARIATIONS[$reason][$bug['bug_type']];
} else {
	$qftext = $RESOLVE_REASONS[$reason]['message'];
}
$ncomment = $qftext . (!empty($ncomment) ? "\n\n".$ncomment : "");

// If the report already has the status of the resolution, bounce over to the main bug form
// which shows the appropriate error message.
if ($status == $bug['status']) {
	redirect("bug.php?id={$bug_id}&edit=1&in[resolve]={$reason}");
}

// Standard items
$in = [
	'status' => $status,
	'bug_type' => $bug['bug_type'],
	'php_version' => $bug['php_version'],
	'php_os' => $bug['php_os'],
	'assign' => $bug['assign'],
];

// Assign automatically when closed
if ($status == 'Closed' && $in['assign'] == '') {
	$in['assign'] = $auth_user->handle;
}

try {
	// Update bug
	$dbh->prepare("
		UPDATE bugdb
		SET
			status = ?,
			assign = ?,
			ts2 = NOW()
		WHERE id = ?
	")->execute([
		$status,
		$in['assign'],
		$bug_id,
	]);

	// Add changelog entry
	$changed = bug_diff($bug, $in);
	if (!empty($changed)) {
		$log_comment = bug_diff_render_html($changed);
		if (!empty($log_comment)) {
			$result = bugs_add_comment($bug_id, $auth_user->email, $auth_user->name, $log_comment, 'log');
		}
	}

	// Add possible comment
	if (!empty($ncomment)) {
		$result = bugs_add_comment($bug_id, $auth_user->email, $auth_user->name, $ncomment, 'comment');
	}

	// Send emails
	mail_bug_updates($bug, $in, $auth_user->email, $ncomment);
	redirect("bug.php?id={$bug_id}&thanks=1");
} catch (\Exception $e) {
	// If we end up here, something went wrong.
	response_header('Resolve Bug: Problem');
	display_bug_error($e->getMessage());
	response_footer();
}

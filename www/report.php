<?php 

// Obtain common includes
require_once '../include/prepend.php';

// Start session 
session_start();

// Init variables
$errors = array();
$ok_to_submit_report = false;

$project = !empty($_GET['project']) ? $_GET['project'] : false;
$pseudo_pkgs = get_pseudo_packages($project, false); // false == no read-only packages included

// Authenticate
bugs_authenticate($user, $pw, $logged_in, $user_flags);

$is_trusted_developer = ($user_flags & BUGS_TRUSTED_DEV);
$is_security_developer = ($user_flags & BUGS_SECURITY_DEV);

require "{$ROOT_DIR}/include/php_versions.php";

// captcha is not necessary if the user is logged in
if (!$logged_in) {
	require_once 'Text/CAPTCHA/Numeral.php';
	$numeralCaptcha = new Text_CAPTCHA_Numeral();
}

// Handle input
if (isset($_POST['in'])) {

	$errors = incoming_details_are_valid($_POST['in'], 1, $logged_in);

	// Check if session answer is set, then compare it with the post captcha value.
	// If it's not the same, then it's an incorrect password.
	if (!$logged_in) {
		if (!isset($_SESSION['answer'])) {
			$errors[] = 'Please enable cookies so the Captcha system can work';
		} elseif ($_POST['captcha'] != $_SESSION['answer']) {
			$errors[] = 'Incorrect Captcha';
		}
		if (is_spam($_POST['in']['ldesc']) ||
			is_spam($_POST['in']['expres']) ||
			is_spam($_POST['in']['repcode'])) {
			$errors[] = 'Spam detected';
		}
	}

	// Set auto-generated password when not supplied or logged in
	if ($logged_in || $_POST['in']['passwd'] == '') {
		$_POST['in']['passwd'] = uniqid();
	}

	// try to verify the user
	$_POST['in']['email']  = $auth_user->email;

	$package_name = $_POST['in']['package_name'];

	if (!$errors) {
		// When user submits a report, do a search and display the results before allowing them to continue.
		if (!isset($_POST['preview']) && empty($_POST['in']['did_luser_search'])) {

			$_POST['in']['did_luser_search'] = 1;

			$where_clause = "WHERE package_name != 'Feature/Change Request'";
			
			if (!$is_security_developer) {
				$where_clause .= " AND private = 'N' ";
			}

			// search for a match using keywords from the subject
			list($sql_search, $ignored) = format_search_string($_POST['in']['sdesc']);

			$where_clause .= $sql_search;

			$query = "SELECT * from bugdb $where_clause LIMIT 5";

			$res = $dbh->prepare($query)->execute();

			if ($res->numRows() == 0) {
				$ok_to_submit_report = true;
			} else {
				response_header("Report - Confirm");
				if (count($_FILES)) {
					echo '<h1>WARNING: YOU MUST RE-UPLOAD YOUR PATCH, OR IT WILL BE IGNORED</h1>';
				}
?>
				<p>
					Are you sure that you searched before you submitted your bug report? We
					found the following bugs that seem to be similar to yours; please check
					them before submitting the report as they might contain the solution you
					are looking for.
				</p>

				<p>
					If you're sure that your report is a genuine bug that has not been reported
					before, you can scroll down and click the "Send Bug Report" button again to
					really enter the details into our database.
				</p>

				<div class="warnings">
					<table class="lusersearch">
						<tr>
							<th>Description</th>
							<th>Possible Solution</th>
						</tr>
<?php

				foreach ($res->fetchAll(MDB2_FETCHMODE_ASSOC) as $row) {
					$resolution = $dbh->prepare("
						SELECT comment 
						FROM bugdb_comments
						WHERE bug = ?
						ORDER BY id DESC
						LIMIT 1
					")->execute(array($row['id']))->fetchOne();

					$summary = $row['ldesc'];
					if (strlen($summary) > 256) {
						$summary = substr(trim($summary), 0, 256) . ' ...';
					}

					$bug_url = "bug.php?id={$row['id']}&amp;edit=2";

					$sdesc		= htmlspecialchars($row['sdesc']);
					$summary	= htmlspecialchars($summary);
					$resolution	= htmlspecialchars($resolution);

					echo <<< OUTPUT
						<tr>
							<td colspan='2'><strong>{$row['package_name']}</strong> : <a href='{$bug_url}'>Bug #{$row['id']}: {$sdesc}</a></td>
						</tr>
						<tr>
							<td><pre class='note'>{$summary}</pre></td>
							<td><pre class='note'>{$resolution}</pre></td>
						</tr>
OUTPUT;
				}

				echo "
					</table>
				</div>
				";
			}
		} else {
			// We displayed the luser search and they said it really was not already submitted, so let's allow them to submit.
			$ok_to_submit_report = true;
		}
		
		if (isset($_POST['edit_after_preview'])) {
			$ok_to_submit_report = false;
			response_header("Report - New");
		}

		if ($ok_to_submit_report) {
			$_POST['in']['reporter_name'] = $auth_user->name;
			$_POST['in']['handle'] = $auth_user->handle;

			// Put all text areas together.
			$fdesc = "Description:\n------------\n" . $_POST['in']['ldesc'] . "\n\n";
			if (!empty($_POST['in']['repcode'])) {
				$fdesc .= "Test script:\n---------------\n";
				$fdesc .= $_POST['in']['repcode'] . "\n\n";
			}
			if (!empty($_POST['in']['expres']) || $_POST['in']['expres'] === '0') {
				$fdesc .= "Expected result:\n----------------\n";
				$fdesc .= $_POST['in']['expres'] . "\n\n";
			}
			if (!empty($_POST['in']['actres']) || $_POST['in']['actres'] === '0') {
				$fdesc .= "Actual result:\n--------------\n";
				$fdesc .= $_POST['in']['actres'] . "\n";
			}
			
			// Bug type 'Security' marks automatically the report as private
			$_POST['in']['private'] = ($_POST['in']['bug_type'] == 'Security') ? 'Y' : 'N';
			$_POST['in']['block_user_comment'] = 'N';
			
			if (isset($_POST['preview'])) {
				$_POST['in']['status'] = 'Open';
				$_SESSION['bug_preview'] = $_POST['in'];
				$_SESSION['bug_preview']['ldesc_orig'] = $_POST['in']['ldesc'];
				$_SESSION['bug_preview']['ldesc'] = $fdesc;
				$_SESSION['captcha'] = $_POST['captcha'];
				redirect('bug.php?id=preview');
			}

			$res = $dbh->prepare('
				INSERT INTO bugdb (
					package_name,
					bug_type,
					email,
					sdesc,
					ldesc,
					php_version,
					php_os,
					passwd,
					reporter_name,
					status,
					ts1,
					private,
					visitor_ip
				) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "Open", NOW(), ?, INET_ATON(?))
			')->execute(array(
					$package_name,
					$_POST['in']['bug_type'],
					$_POST['in']['email'],
					$_POST['in']['sdesc'],
					$fdesc,
					$_POST['in']['php_version'],
					$_POST['in']['php_os'],
					bugs_get_hash($_POST['in']['passwd']),
					$_POST['in']['reporter_name'],
					$_POST['in']['private'],
					$_SERVER['REMOTE_ADDR']
				)
			);
			if (PEAR::isError($res)) {
				echo "<pre>";
				var_dump($_POST['in'], $fdesc, $package_name);
				die($res->getMessage());
			}
			$cid = $dbh->lastInsertId();

			$redirectToPatchAdd = false;
			if (!empty($_POST['in']['patchname']) && $_POST['in']['patchname']) {
				require_once "{$ROOT_DIR}/include/classes/bug_patchtracker.php";
				$tracker = new Bug_Patchtracker;
				PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
				$patchrevision = $tracker->attach($cid, 'patchfile', $_POST['in']['patchname'], $_POST['in']['handle'], array());
				PEAR::staticPopErrorHandling();
				if (PEAR::isError($patchrevision)) {
					$redirectToPatchAdd = true;
				}
			}
			
			if (empty($_POST['in']['handle'])) {
				$mailfrom = spam_protect($_POST['in']['email'], 'text');
			} else {
				$mailfrom = $_POST['in']['handle'];
			}

			$report = <<< REPORT
From:             {$mailfrom}
Operating system: {$_POST['in']['php_os']}
PHP version:      {$_POST['in']['php_version']}
Package:          {$package_name}
Bug Type:         {$_POST['in']['bug_type']}
Bug description:
REPORT;

			$ascii_report = "{$report}{$_POST['in']['sdesc']}\n\n" . wordwrap($fdesc, 72);
			$ascii_report.= "\n-- \nEdit bug report at ";
			$ascii_report.= "{$site_method}://{$site_url}{$basedir}/bug.php?id=$cid&edit=";

			list($mailto, $mailfrom, $bcc, $params) = get_package_mail($package_name, false, $_POST['in']['bug_type']);

			$protected_email = '"' . spam_protect($_POST['in']['email'], 'text') . '"' .  "<{$mailfrom}>";

			$extra_headers = "From: {$protected_email}\n";
			$extra_headers.= "X-PHP-BugTracker: {$siteBig}bug\n";
			$extra_headers.= "X-PHP-Bug: {$cid}\n";
			$extra_headers.= "X-PHP-Type: {$_POST['in']['bug_type']}\n";
			$extra_headers.= "X-PHP-Version: {$_POST['in']['php_version']}\n";
			$extra_headers.= "X-PHP-Category: {$package_name}\n";
			$extra_headers.= "X-PHP-OS: {$_POST['in']['php_os']}\n";
			$extra_headers.= "X-PHP-Status: Open\n";
			$extra_headers.= "Message-ID: <bug-{$cid}@{$site_url}>";

			if (isset($bug_types[$_POST['in']['bug_type']])) {
				$type = $bug_types[$_POST['in']['bug_type']];
			} else {
				$type = 'unknown';
			}
			
			$project = !empty($_GET['project']) ? $_GET['project'] : false;

			// provide shortcut URLS for "quick bug fixes"
			list($RESOLVE_REASONS, $FIX_VARIATIONS) = get_resolve_reasons($project);

			$dev_extra = '';
			$maxkeysize = 0;
			foreach ($RESOLVE_REASONS as $v) {
				if (!$v['webonly']) {
					$actkeysize = strlen($v['title']) + 1;
					$maxkeysize = (($maxkeysize < $actkeysize) ? $actkeysize : $maxkeysize);
				}
			}
			foreach ($RESOLVE_REASONS as $k => $v) {
				if (!$v['webonly']) {
					$dev_extra .= str_pad("{$v['title']}:", $maxkeysize) . " {$site_method}://{$site_url}/fix.php?id={$cid}&r={$k}\n";
				}
			}

			// mail to reporter
			bugs_mail(
				$_POST['in']['email'],
				"$type #$cid: {$_POST['in']['sdesc']}",
				"{$ascii_report}2\n",
				"From: $siteBig Bug Database <$mailfrom>\n" .
				"X-PHP-Bug: $cid\n" .
				"X-PHP-Site: {$siteBig}\n" .
				"Message-ID: <bug-$cid@{$site_url}>"
			);

			// mail to package mailing list
			bugs_mail(
				$mailto,
				"[$siteBig-BUG] $type #$cid [NEW]: {$_POST['in']['sdesc']}",
				$ascii_report . "1\n-- \n{$dev_extra}",
				$extra_headers,
				$params
			);

			if ($redirectToPatchAdd) {
				$patchname = urlencode($_POST['in']['patchname']);
				$patchemail= urlencode($_POST['in']['email']);
				redirect("patch-add.php?bug_id={$cid}&patchname={$patchname}&email={$patchemail}");
			}
			redirect("bug.php?id={$cid}&thanks=4");
		}
	} else {
		// had errors...
		response_header('Report - Problems');
		echo '<h1>Report new bug</h1>';
	}
} // end of if input

$package = !empty($_REQUEST['package']) ? $_REQUEST['package'] : (!empty($package_name) ? $package_name : (isset($_POST['in']) && $_POST['in'] && isset($_POST['in']['package_name']) ? $_POST['in']['package_name'] : ''));

if (!is_string($package)) {
	response_header('Report - Problems');
	echo '<h1>Report new bug</h1>';
	$errors[] = 'Invalid package name passed. Please fix it and try again.';
	display_bug_error($errors);
	response_footer();
	exit;
}

if (!isset($_POST['in'])) {

	$_POST['in'] = array(
			 'package_name' => isset($_GET['package_name']) ? clean($_GET['package_name']) : '',
			 'bug_type' => isset($_GET['bug_type']) ? clean($_GET['bug_type']) : '',
			 'email' => '',
			 'sdesc' => '',
			 'ldesc' => isset($_GET['manpage']) ? clean("\n---\nFrom manual page: http://www.php.net/" . ltrim($_GET['manpage'], '/') . "\n---\n") : '',
			 'repcode' => '',
			 'expres' => '',
			 'actres' => '',
			 'php_version' => '',
			 'php_os' => '',
			 'passwd' => '',
	);
	response_header('Report - New');
?>
	<h1>Report new bug</h1>

	<p class="warn">
		Failure to follow bug reporting instructions noted on the <a href="index.php">main page</a>
		may result in your bug simply being marked as <em>Not a bug</em>.
	</p>

	<p class="warn">
		If you feel this bug concerns a security issue, e.g. a buffer overflow, weak encryption, etc, email
		<?php echo make_mailto_link("{$site_data['security_email']}?subject=%5BSECURITY%5D+possible+new+bug%21", $site_data['security_email']); ?>
		or set <em>Security</em> bug type below.
	</p>

<?php

}

display_bug_error($errors);

?>
	<form method="post" action="report.php?package=<?php echo htmlspecialchars($package); ?>" enctype="multipart/form-data">
		<input type="hidden" name="in[did_luser_search]" value="<?php echo isset($_POST['in']['did_luser_search']) ? $_POST['in']['did_luser_search'] : 0; ?>">
		<table border="0" class="standard report-bug-form">
			<tr>
				<th><label for="in_sdesc" class="required">Bug title</label></th>
				<td>
					<input type="text" maxlength="79" name="in[sdesc]" id="in_sdesc" value="<?= esc($_POST['in']['sdesc']) ?>" required>
				</td>
			</tr>
<?php if ($logged_in): ?>
			<tr>
				<th>Logged as</th>
				<td class="static">
					<?php echo $auth_user->handle; ?>@php.net
					<input type="hidden" name="in[email]" value="<?php echo $auth_user->email; ?>">
				</td>
			</tr>
<?php else: ?>
			<tr>
				<th>
					<label for="in_email" class="required">Your email address</label>
					<small><strong>MUST BE VALID</strong></small>
				</th>
				<td>
					<input type="email" maxlength="40" name="in[email]" id="in_email" value="<?= esc($_POST['in']['email']) ?>" required>
				</td>
			</tr>

			<tr>
				<th>
					<label for="in_password" class="required">Password</label>
					<small>Set any password for this bug report so that you can alter it later.</small>
				</th>
				<td>
					<input type="password" maxlength="20" name="in[passwd]" id="in_password" value="<?= esc($_POST['in']['passwd']) ?>" required>
				</td>
			</tr>
<?php endif; ?>

			<tr>
				<th><label for="in_bug_type" class="required">Bug type</label></th>
				<td>
					<select name="in[bug_type]" id="in_bug_type" required>
						<?php show_type_options($_POST['in']['bug_type']); ?>
					</select>
				</td>
			</tr>

			<tr>
				<th><label for="in_php_version" class="required">PHP version</label></th>
				<td>
					<select name="in[php_version]" id="in_php_version" required>
						<?php show_version_options($_POST['in']['php_version']); ?>
					</select>
				</td>
			</tr>

			<tr>
				<th><label for="in_package_name" class="required">Package affected</label></th>
				<td>
					<select name="in[package_name]" id="in_package_name">
						<?php show_package_options($_POST['in']['package_name'], 0, htmlspecialchars($package)); ?>
					</select>
				</td>
			</tr>

			<tr>
				<th><label for="in_php_os">Operating system</label></th>
				<td>
					<input type="text" maxlength="32" name="in[php_os]" id="in_php_os" value="<?= esc($_POST['in']['php_os']) ?>">
				</td>
			</tr>

			<tr>
				<th>Note</th>
				<td>
					Please supply any information that may be helpful in fixing the bug:
					<ul>
						<li>The version number of the <?php echo $siteBig; ?> package or files you are using.</li>
						<li>A short script that reproduces the problem.</li>
						<li>The list of modules you compiled PHP with (your configure line).</li>
						<li>Any other information unique or specific to your setup.</li>
						<li>Any changes made in your php.ini compared to php.ini-dist or php.ini-recommended (<strong>not</strong> your whole php.ini!)</li>
						<li>A <a href="bugs-generating-backtrace.php">gdb backtrace</a>.</li>
					</ul>
				</td>
			</tr>

			<tr>
				<th>
					<label for="in_ldesc" class="required">Description</label>
					<small>Note that there are separate fields for test script and patches</small>
				</th>
				<td>
					<textarea rows="9" name="in[ldesc]" id="in_ldesc" required><?= esc($_POST['in']['ldesc']) ?></textarea>
				</td>
			</tr>
			<tr>
				<th>
					<label for="in_repcode">Test script</label>
					<small>
						A short test script you wrote that demonstrates the bug. If the code
						is longer than 20 lines, provide a URL to the code (e.g. using pastebin).
					</small>
				</th>
				<td>
					<textarea rows="9" name="in[repcode]" id="in_repcode"><?= esc($_POST['in']['repcode']) ?></textarea>
				</td>
			</tr>
<?php
	$patchname = isset($_POST['in']['patchname']) ? $_POST['in']['patchname'] : '';
	$patchfile = isset($_FILES['patchfile']['name']) ? $_FILES['patchfile']['name'] : '';
?>
			<tr>
				<th>
					<label for="in_patchfile">Patch file</label>
					<small>A patch file created using git diff</small>
				</th>
				<td><input type="file" name="patchfile" id="in_patchfile"></td>
			</tr>

			<tr>
				<th><label for="in_patchname">Patch name</label></th>
				<td><input type="text" maxlength="80" name="in[patchname]" id="in_patchname" value="<?php echo clean($patchname) ?>" placeholder="php-bugfix-001.patch"></td>
			</tr>

			<tr>
				<th>
					<label for="in_expres">Expected result</label>
					<small>What do you expect to happen or see when you run the test script above?</small>
				</th>
				<td>
					<textarea rows="9" name="in[expres]" id="in_expres"><?= esc($_POST['in']['expres']) ?></textarea>
				</td>
			</tr>

			<tr>
				<th>
					<label for="actres">Actual result<label>
					<small>
						This could be a <a href="bugs-generating-backtrace.php">backtrace</a> for example.
						Try to keep it as short as possible without leaving anything relevant out.
					</small>
				</th>
				<td>
					<textarea rows="9" name="in[actres]" id="in_actres"><?= esc($_POST['in']['actres']) ?></textarea>
				</td>
			</tr>

<?php if (!$logged_in) { 
	$captcha = $numeralCaptcha->getOperation();
	$_SESSION['answer'] = $numeralCaptcha->getAnswer();
	if (!empty($_POST['captcha']) && empty($ok_to_submit_report)) {
		$captcha_label = 'Human test (again)';
	} else {
		$captcha_label = 'Human test';
	}
?>
			<tr>
				<th>
					<label for="in_captcha" class="required"><?= $captcha_label ?></label>
				</th>
				<td>
					<span class="captcha-question"><?= esc($captcha) ?> = </span>
					<input type="text" name="captcha" autocomplete="off" required>
				</td>
			</tr>
<?php } ?>

			<tr>
				<th class="buttons" colspan="2">
					<input type="submit" value="Send bug report"> or 
					<input type="submit" value="Preview" name="preview">
				</td>
			</tr>
		</table>
	</form>
<?php

response_footer();

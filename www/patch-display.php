<?php

use App\Repository\BugRepository;
use App\Repository\ObsoletePatchRepository;
use App\Repository\PatchRepository;
use App\Utils\PatchTracker;
use App\Utils\Uploader;

session_start();

// Obtain common includes
require_once '../include/prepend.php';

$obsoletePatchRepository = new ObsoletePatchRepository($dbh);
$patchRepository = new PatchRepository($dbh);
$uploader = new Uploader();
$patchTracker = new PatchTracker($dbh, $uploader);

// Authenticate
bugs_authenticate($user, $pw, $logged_in, $user_flags);

if (!isset($_GET['bug_id']) && !isset($_GET['bug'])) {
	response_header('Error :: no bug selected');
	display_bug_error('No patch selected to view');
	response_footer();
	exit;
}

$canpatch = ($logged_in == 'developer');

$revision = isset($_GET['revision']) ? $_GET['revision'] : null;
$patch_name = isset($_GET['patch'])	? $_GET['patch'] : null;
if ($patch_name) {
	$patch_name_url = urlencode($patch_name);
}

$bug_id = !empty($_GET['bug']) ? (int) $_GET['bug'] : 0;
if (empty($bug_id)) {
	$bug_id = (int) $_GET['bug_id'];
}

$bugRepository = new BugRepository($dbh);

if (!($buginfo = $bugRepository->findOneById($bug_id))) {
	response_header('Error :: invalid bug selected');
	display_bug_error("Invalid bug #{$bug_id} selected");
	response_footer();
	exit;
}

if (!bugs_has_access($bug_id, $buginfo, $pw, $user_flags)) {
	response_header('Error :: No access to bug selected');
	display_bug_error("You have no access to bug #{$bug_id}");
	response_footer();
	exit;
}

if (isset($patch_name) && isset($revision)) {
	if ($revision == 'latest') {
		$revisions = $patchRepository->findRevisions($buginfo['id'], $patch_name);
		if (isset($revisions[0])) {
			$revision = $revisions[0]['revision'];
		}
	}

	$path = $patchTracker->getPatchFullpath($bug_id, $patch_name, $revision);
	if (!file_exists($path)) {
		response_header('Error :: no such patch/revision');
		display_bug_error('Invalid patch/revision specified');
		response_footer();
		exit;
	}

	if (isset($_GET['download'])) {
		header('Last-modified: ' . gmdate('l, d-M-y H:i:s \G\M\T', filemtime($path)));
		header('Content-type: application/octet-stream');
		header('Content-disposition: attachment; filename="' . $patch_name . '.patch.txt"');
		header('Content-length: '.filesize($path));
		readfile($path);
		exit;
	}

	try {
		$patchcontents = $patchRepository->getPatchContents($buginfo['id'], $patch_name, $revision);
	} catch (\Exception $e) {
		response_header('Error :: Cannot retrieve patch');
		display_bug_error('Internal error: Invalid patch/revision specified (is in database, but not in filesystem)');
		response_footer();
		exit;
	}

	$package_name = $buginfo['package_name'];
	$handle = $patchRepository->findDeveloper($bug_id, $patch_name, $revision);
	$obsoletedby = $obsoletePatchRepository->findObsoletingPatches($bug_id, $patch_name, $revision);
	$obsoletes = $obsoletePatchRepository->findObsoletePatches($bug_id, $patch_name, $revision);
	$patches = $patchRepository->findAllByBugId($bug_id);
	$revisions = $patchRepository->findRevisions($bug_id, $patch_name);

	response_header("Bug #{$bug_id} :: Patches");
	include "{$ROOT_DIR}/templates/listpatches.php";

	if (isset($_GET['diff']) && $_GET['diff'] && isset($_GET['old']) && is_numeric($_GET['old'])) {
		$old = $patchTracker->getPatchFullpath($bug_id, $patch_name, $_GET['old']);
		$new = $path;
		if (!realpath($old) || !realpath($new)) {
			response_header('Error :: Cannot retrieve patch');
			display_bug_error('Internal error: Invalid patch revision specified for diff');
			response_footer();
			exit;
		}

		require_once "{$ROOT_DIR}/include/classes/bug_diff_renderer.php";

		assert_options(ASSERT_WARNING, 0);
		$d	= new Text_Diff($orig = file($old), $now = file($new));
		$diff = new Bug_Diff_Renderer($d);
		include "{$ROOT_DIR}/templates/patchdiff.php";
		response_footer();
		exit;
	}
	include "{$ROOT_DIR}/templates/patchdisplay.php";
	response_footer();
	exit;
}

$patches = $patchTracker->listPatches($bug_id);
response_header("Bug #{$bug_id} :: Patches");
include "{$ROOT_DIR}/templates/listpatches.php";
response_footer();

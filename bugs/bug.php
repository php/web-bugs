<?php

/**
 * User interface for viewing and editing bug details
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

/*
 * NOTE: another require exists in the code below, so if changing
 * the include path, make sure to change it too.
 */

session_start();

/**
 * Obtain common includes
 */
require_once './include/prepend.inc';

/**
 * Get user's CVS password
 */
require_once './include/cvs-auth.inc';

/**
 * Obtain a list of the trusted developers
 */
require_once './include/trusted-devs.inc';

/**
 * Numeral Captcha Class
 */
require_once 'Text/CAPTCHA/Numeral.php';

/**
 * Instantiate the numeral captcha object.
 */
$numeralCaptcha = new Text_CAPTCHA_Numeral();

if (empty($_REQUEST['id']) || !(int)$_REQUEST['id']) {
    localRedirect('search.php');
    exit;
} else {
    $bug_id = (int) $_REQUEST['id'];
}

if (isset($_GET['unsubscribe'])) {
    $unsubcribe = (int) $_GET['unsubscribe'];

    $hash = isset($_GET['t']) ? $_GET['t'] : false;
    $site == 'pear' ? $redirect = 'pecl' : $redirect = 'pear';

    if (!$hash) {
        localRedirect("bug.php?id={$bug_id}");
    }

    unsubscribe($bug_id, $hash);
}

if (empty($_REQUEST['edit']) || !(int)$_REQUEST['edit']) {
    $edit = 0;
} else {
    $edit = (int)$_REQUEST['edit'];
}

// captcha is not necessary if the user is logged in
if (isset($auth_user) && $auth_user && $auth_user->registered) {
    if (isset($_SESSION['answer'])) {
        unset($_SESSION['answer']);
    }
}

if (isset($auth_user) && $auth_user && $auth_user->registered && isset($_GET['delete_comment'])) {
    $delete_comment = (int) $_GET['delete_comment'];
} else {
    $delete_comment = false;
}

$trytoforce = isset($_POST['trytoforce']) ? (int) $_POST['trytoforce'] : false;

// fetch info about the bug into $bug
if ($dbh->getOne('SELECT handle FROM bugdb WHERE id=?', array($bug_id))) {
    $query = 'SELECT b.id, b.package_name, b.bug_type, b.email, b.handle as bughandle, b.reporter_name,
        b.passwd, b.sdesc, b.ldesc, b.php_version, b.package_version, b.php_os,
        b.status, b.ts1, b.ts2, b.assign, UNIX_TIMESTAMP(b.ts1) AS submitted,
        users.registered,
        UNIX_TIMESTAMP(b.ts2) AS modified,
        COUNT(bug=b.id) AS votes,
        SUM(reproduced) AS reproduced,SUM(tried) AS tried,
        SUM(sameos) AS sameos, SUM(samever) AS samever,
        AVG(score)+3 AS average,STD(score) AS deviation,
        users.showemail, users.handle, p.package_type
        FROM bugdb b
        LEFT JOIN bugdb_votes ON b.id = bug
        LEFT JOIN users ON users.handle = b.handle
        LEFT JOIN packages p ON b.package_name = p.name
        WHERE b.id = ?
        GROUP BY bug';
} else {
    $query = 'SELECT b.id, b.package_name, b.bug_type, b.email, b.handle as bughandle, b.reporter_name,
        b.passwd, b.sdesc, b.ldesc, b.php_version, b.package_version, b.php_os,
        b.status, b.ts1, b.ts2, b.assign, UNIX_TIMESTAMP(b.ts1) AS submitted,
        1 as registered,
        UNIX_TIMESTAMP(b.ts2) AS modified,
        COUNT(bug=b.id) AS votes,
        SUM(reproduced) AS reproduced,SUM(tried) AS tried,
        SUM(sameos) AS sameos, SUM(samever) AS samever,
        AVG(score)+3 AS average,STD(score) AS deviation,
        users.showemail, users.handle, p.package_type
        FROM bugdb b
        LEFT JOIN bugdb_votes ON b.id = bug
        LEFT JOIN users ON users.email = b.email
        LEFT JOIN packages p ON b.package_name = p.name
        WHERE b.id = ?
        GROUP BY bug';
}

$bug =& $dbh->getRow($query, array($bug_id), DB_FETCHMODE_ASSOC);

// DB error
if (is_object($bug)) {
    response_header('DB error');
	display_bug_error($bug);
    response_footer();
    exit;
}

// Bug not found with passed id
if (!$bug) {
    response_header('No Such Bug');
    display_bug_error("No such bug #{$bug_id}");
    response_footer();
    exit;
}

if ($edit == 1) {
    if (isset($auth_user) && $auth_user) {
        if (auth_check('pear.bug') && !auth_check('pear.dev') &&
              $bug['bughandle'] != $auth_user->handle) {
            $edit = 3; // can't edit a bug you didn't create
        }
    } else {
        if (empty($bug['bughandle'])) {
            $edit = 2; // old bug, may be original author, try the old way
        }
    }
}

// 2 is not possible for newer bugs, problem encountered by spin
if ($edit == 2) {
    if (!empty($bug['bughandle'])) {
        $edit = 1;
    }
}

if ($edit == 1) {
    auth_require('pear.bug', 'pear.dev');
}

if (!empty($_POST['pw'])) {
    if (empty($_POST['user'])) {
        $user = '';
    } else {
        $user = htmlspecialchars(rinse($_POST['user']));
    }
    $pw = rinse($_POST['pw']);
} elseif (isset($auth_user) && $auth_user && $auth_user->handle && $edit == 1) {
    $user = rinse($auth_user->handle);
    $pw   = rinse($auth_user->password);
} elseif (isset($_COOKIE['MAGIC_COOKIE'])) {
    @list($user, $pw) = explode(':', base64_decode($_COOKIE['MAGIC_COOKIE']));
    $user = rinse($user);
    if ($pw === null) {
        $pw = '';
    }
} else {
    $user = '';
    $pw   = '';
}

// Subscription
if (isset($_POST['subscribe_to_bug'])) {
    $email = $_POST['subscribe_email'];
    if (!is_valid_email($email)) {
        $errors[] = 'You must provide a valid email address.';
    } else {
        $dbh->query("REPLACE INTO bugdb_subscribe SET bug_id='{$bug_id}', email='" . escapeSQL($email) . "'");
        localRedirect("bug.php?id={$bug_id}");
    }
}

// Unsubscribe
if (isset($_POST['unsubscribe_to_bug'])) {
    $email = $_POST['subscribe_email'];

    if (!is_valid_email($email)) {
        $errors[] = 'You must provide a valid email address.';
    } else {
        /* Generate the hash */
        unsubscribe_hash($bug_id, $email, $bug);
        localRedirect("bug.php?id={$bug_id}");
    }
}

// Redirect to correct site if package type is not same as current site
if (!empty($bug['package_type']) && $bug['package_type'] != $site) {
	$url = "{$site_data[$bug['package_type']]['url']}{$site_data[$bug['package_type']]['basedir']}";
    localRedirect("http://{$url}/bug.php?id={$bug_id}");
}

// if the user is not registered, this might be spam, don't display
if (!$bug['registered'] && !auth_check('pear.dev')) {
    response_header('User has not confirmed identity');
    display_bug_error('The user who submitted this report has not yet confirmed his/her email address.');
    $handle_out = urlencode($bug['bughandle']);
    echo <<< DATA
If you submitted this report, please check your email.
If you did not receive any message, <a href="resend-request-email.php?handle={$handle_out}">click here to re-send</a>
MANUAL CONFIRMATION IS NOT POSSIBLE.  All bugs/comments/patches associated with this email address will be deleted within 48 hours 
if the account request is not confirmed!
DATA;
    response_footer();
    exit;
}

$previous = $current = array();
// Delete comment
if ($edit == 1 && $delete_comment !== false) {
    $addon = '';

    if (in_array($user, $trusted_developers) && verify_password($user, $pw)) {
        delete_comment($bug_id, $delete_comment);
        $addon = '&thanks=1';
    }
    localRedirect('bug.php' . "?id=$bug_id&edit=1$addon");
}

// handle any updates, displaying errors if there were any
$errors = array();

/**
 * Init BugDataObject class
 */
Bug_DataObject::init();

if (isset($_POST['ncomment']) && !isset($_POST['preview']) && $edit == 3) {
    // Submission of additional comment by others

    /**
     * Check if session answer is set, then compare
     * it with the post captcha value. If it's not
     * the same, then it's an incorrect password.
     */
    if (isset($_SESSION['answer']) && strlen(trim($_SESSION['answer'])) > 0) {
        if ($_POST['captcha'] != $_SESSION['answer']) {
            $errors[] = 'Incorrect Captcha';
        }
    }

    // try to verify the user
    if (isset($auth_user) && $auth_user) {
        $_POST['in']['handle'] = $auth_user->handle;
    }

    $ncomment = trim($_POST['ncomment']);
    if (!$ncomment) {
        $errors[] = "You must provide a comment.";
    }

    if (!$errors) {
        do {
            if (!isset($auth_user) || !$auth_user) {
                // user doesn't exist yet
                require 'include/classes/bug_accountrequest.php';
                $buggie = new Bug_Accountrequest;
                if (!is_valid_email($_POST['in']['commentemail'])) {
                    $errors[] = "You must provide a valid email address.";
                    response_header('Add Comment - Problems');
                    break; // skip bug comment addition
                }
                $salt = $buggie->addRequest($_POST['in']['commentemail']);
                if (is_array($salt)) {
                    $errors = $salt;
                    response_header('Add Comment - Problems');
                    break; // skip bug comment addition
                }
                if (PEAR::isError($salt)) {
                    $errors[] = $salt;
                    response_header('Add Comment - Problems');
                    break;
                }
                if ($salt === false) {
                    $errors[] = 'Your account cannot be added to the queue.'
                         . ' Please write a mail message to the '
                         . ' <i>pear-dev</i> mailing list.';
                    response_header('Report - Problems');
                    break;
                }

                try {
                    $buggie->sendEmail();
                } catch (Exception $e) {
                    $errors[] = 'Critical internal error: could not send' .
                        ' email to your address ' . $_POST['in']['email'] .
                        ', please write a mail message to the <i>pear-dev</i>' .
                        'mailing list and report this problem with details.' .
                        '  We apologize for the problem, your report will help' .
                        ' us to fix it for future users: ' . $e->getMessage();
                    response_header('Add Comment - Problems');
                    break;
                }
                $_POST['in']['handle'] =
                $_POST['in']['name'] = $buggie->handle;
            } else {
                $_POST['in']['commentemail'] = $auth_user->email;
                $_POST['in']['handle'] = $auth_user->handle;
                $_POST['in']['name'] = $auth_user->name;
            }

            $query = 'INSERT INTO bugdb_comments (bug, email, handle, ts, comment, reporter_name)
                      VALUES (?, ?, ?, NOW(), ?, ?)
			';
            $dbh->query($query, array($bug_id, $_POST['in']['commentemail'], $_POST['in']['handle'], $ncomment, $_POST['in']['name']));
        } while (false);

        if (isset($auth_user) && $auth_user) {
            $from = $auth_user->email;
        } else {
            $from = '';
        }
    } else {
        $from = '';
    }
} elseif (isset($_POST['ncomment']) && isset($_POST['preview']) && $edit == 3) {
    $ncomment = trim($_POST['ncomment']);

} elseif (isset($_POST['in']) && !isset($_POST['preview']) && $edit == 2) {
    // Edits submitted by original reporter for old bugs

    if (!$bug['passwd'] || $bug['passwd'] != $pw) {
        $errors[] = 'The password you supplied was incorrect.';
    }

    $ncomment = trim($_POST['ncomment']);
    if (!$ncomment) {
        $errors[] = 'You must provide a comment.';
    }

    /* check that they aren't being bad and setting a status they
       aren't allowed to (oh, the horrors.) */
    if ($_POST['in']['status'] != $bug['status'] && $state_types[$_POST['in']['status']] != 2) {
        $errors[] = 'You aren\'t allowed to change a bug to that state.';
    }

    /* check that they aren't changing the mail to a php.net address
       (gosh, somebody might be fooled!) */
    if (preg_match('/^(.+)@php\.net/i', $_POST['in']['email'], $m)) {
        if ($user != $m[1] || !verify_password($user, $pass)) {
            $errors[] = 'You have to be logged in as a developer to use your php.net email address.';
            $errors[] = 'Tip: log in via another browser window then resubmit the form in this window.';
        }
    }

    if (!empty($_POST['in']['email']) &&
        $bug['email'] != $_POST['in']['email'])
    {
        $from = $_POST['in']['email'];
    } else {
        $from = $bug['email'];
    }

    if (!empty($_POST['in']['package_name']) &&
        $bug['package_name'] != $_POST['in']['package_name']) {
        // reset package version if we change package name
        $_POST['in']['package_version'] = '';
    }

    if (!$errors && !($errors = incoming_details_are_valid($_POST['in'], false, false))) {
        $dbh->query("
            UPDATE bugdb
            SET
            	sdesc = ?,
            	status = ?,
            	package_name = ?,
            	bug_type = ?,
            	package_version = ?,
            	php_version = ?,
            	php_os = ?,
            	email = ?,
            	ts2 = NOW()
            WHERE id={$bug_id}
		" , array(
        	$_POST['in']['sdesc'],
        	$_POST['in']['status'],
        	$_POST['in']['package_name'],
        	$_POST['in']['bug_type'],
        	$_POST['in']['package_version'],
        	$_POST['in']['php_version'],
        	$_POST['in']['php_os'],
        	$from,
        ));

        if (!empty($ncomment)) {
            $query = "INSERT INTO bugdb_comments (bug, email, ts, comment)
                      VALUES ('{$bug_id}', ?, NOW(), ?)";
            $dbh->query($query, array($from, $ncomment));
        }
    }
} elseif (isset($_POST['in']) && isset($_POST['preview']) && $edit == 2) {
    $ncomment = trim($_POST['ncomment']);
    $from = rinse($_POST['in']['commentemail']);
} elseif (isset($_POST['in'])  && !isset($_POST['preview']) && $edit == 1) {
    // Edits submitted by developer

    if (!verify_password($user, $pw)) {
        $errors[] = 'You have to login first in order to edit the bug report.';
        $errors[] = 'Tip: log in via another browser window then resubmit the form in this window.';
    }
    $comment_name = $auth_user->name;
    if (empty($_POST['ncomment'])) {
        $ncomment = '';
    } else {
        $ncomment = trim($_POST['ncomment']);
    }

    /* Fetch RESOLVE_REASONS array */
    $RESOLVE_REASONS = get_resolve_reasons();

    if (isset($_POST['in']) && is_array($_POST['in']) &&
          (($_POST['in']['status'] == 'Bogus' && $bug['status'] != 'Bogus') ||
          (isset($_POST['in']['resolve']) && isset($RESOLVE_REASONS[$_POST['in']['resolve']]) &&
           $RESOLVE_REASONS[$_POST['in']['resolve']]['status'] == 'Bogus')) &&
        strlen($ncomment) == 0)
    {
        $errors[] = "You must provide a comment when marking a bug 'Bogus'";
    } elseif (isset($_POST['in']) && is_array($_POST['in']) &&
          !empty($_POST['in']['resolve'])) {
        if (!$trytoforce && isset($RESOLVE_REASONS[$_POST['in']['resolve']]) &&
            $RESOLVE_REASONS[$_POST['in']['resolve']]['status'] == $bug['status'])
        {
            $errors[] = 'The bug is already marked "'.$bug['status'].'". (Submit again to ignore this.)';
        } elseif (!$errors)  {
            if ($_POST['in']['status'] == $bug['status']) {
                $_POST['in']['status'] = $RESOLVE_REASONS[$_POST['in']['resolve']]['status'];
            }
            if (isset($FIX_VARIATIONS) && isset($FIX_VARIATIONS[$in['resolve']][$bug['package_name']])) {
                $reason = $FIX_VARIATIONS[$in['resolve']][$bug['package_name']];
            } else {
                $reason = isset($RESOLVE_REASONS[$_POST['in']['resolve']]) ? $RESOLVE_REASONS[$_POST['in']['resolve']]['message'] : '';
            }

            // do a replacement on @cvs@ to the likely location of CVS for this package
            if ($_POST['in']['resolve'] == 'trycvs') {
                switch ($bug['package_name']) {
                    case 'Documentation' :
                    case 'Web Site' :
                    case 'Bug System' :
                    case 'PEPr' :
                        $errors[] = 'Cannot use "try cvs" with ' . $bug['package_name'];
                        break;
                    case 'PEAR' :
                        $reason = str_replace('@cvs@', 'pear-core', $reason);
                        $ncomment = "$reason\n\n$ncomment";
                        break;
                    default :
                        $reason = str_replace('@cvs@', 'pear/' . $bug['package_name'], $reason);
                        $ncomment = "$reason\n\n$ncomment";
                        break;
                }
            } else {
                $ncomment = "$reason\n\n$ncomment";
            }
        }
    }

    $from = $auth_user->email;

    if (!$errors && !($errors = incoming_details_are_valid($_POST['in']))) {
        $query = 'UPDATE bugdb SET';

        if ($bug['email'] != $_POST['in']['email'] &&
            !empty($_POST['in']['email']))
        {
            $query .= " email='{$_POST['in']['email']}',";
        }

        if (!auth_check('pear.dev')) {
            // don't reset assigned status
            $_POST['in']['assign'] = $bug['assign'];
        }
        if (!empty($_POST['in']['assign']) && $_POST['in']['status'] == 'Open') {
            $status = 'Assigned';
        } elseif (empty($_POST['in']['assign']) && $_POST['in']['status'] == 'Assigned') {
            $status = 'Open';
        } else {
            $status = $_POST['in']['status'];
        }

        if ($status == 'Closed' && $_POST['in']['assign'] == '') {
            $_POST['in']['assign'] = $auth_user->handle;
        }

        if (!empty($_POST['in']['package_name']) &&
            $bug['package_name'] != $_POST['in']['package_name']) {
            // reset package version if we change package name
            $_POST['in']['package_version'] = '';
        }

        $query .= " sdesc='" . escapeSQL($_POST['in']['sdesc']) . "'," .
                  " status='" . escapeSQL($status) . "'," .
                  " package_name='" . escapeSQL($_POST['in']['package_name']) . "'," .
                  " bug_type='" . escapeSQL($_POST['in']['bug_type']) . "'," .
                  " assign='" . escapeSQL($_POST['in']['assign']) . "'," .
                  " package_version='" . escapeSQL($_POST['in']['package_version']) . "'," .
                  " php_version='" . escapeSQL($_POST['in']['php_version']) . "'," .
                  " php_os='" . escapeSQL($_POST['in']['php_os']) . "'," .
                  " ts2=NOW() WHERE id=$bug_id";
        $dbh->query($query);
        $previous = $dbh->getAll('SELECT roadmap_version
            FROM bugdb_roadmap_link l, bugdb_roadmap b
            WHERE
                l.id=? AND b.id=l.roadmap_id', array($bug_id));
        if (auth_check('pear.dev')) {
            // don't change roadmap assignments for non-devs editing a bug
            $link = Bug_DataObject::bugDB('bugdb_roadmap_link');
            $link->id = $bug_id;
            $link->delete();
            if (isset($_POST['in']['fixed_versions'])) {
                foreach ($_POST['in']['fixed_versions'] as $rid) {
                    $link->id = $bug_id;
                    $link->roadmap_id = $rid;
                    $link->insert();
                }
            }
            $current = $dbh->getAll('SELECT roadmap_version
                FROM bugdb_roadmap_link l, bugdb_roadmap b
                WHERE
                    l.id=? AND b.id=l.roadmap_id', array($bug_id));
        } else {
            $current = $previous;
        }

        if (!empty($ncomment)) {
            $query = 'INSERT INTO bugdb_comments' .
                     ' (bug, email, ts, comment, reporter_name, handle) VALUES (?,?,NOW(),?,?,?)';
            $dbh->query($query, array($bug_id, $from, $ncomment, $comment_name, $auth_user->handle));
        }
    }
} elseif (isset($_POST['in']) && isset($_POST['preview']) && $edit == 1) {
    $ncomment = trim($_POST['ncomment']);
    $from = $auth_user->email;
} elseif (isset($_POST['in'])) {
    $errors[] = 'Invalid edit mode.';
    $ncomment = '';
} else {
    $ncomment = '';
}

if (isset($_POST['in']) && (!isset($_POST['preview']) && $ncomment ||
      $previous != $current)) {
    if (!$errors) {
        if (!isset($buggie)) {
            mail_bug_updates($bug, $_POST['in'], $from, $ncomment, $edit, $bug_id, $previous, $current);
        }
        localRedirect('bug.php' . "?id=$bug_id&thanks=$edit");
        exit;
    }
}

switch ($bug['bug_type'])
{
    case 'Bug':
    	$bug_type = 'Bug';
    	break;
    case 'Feature/Change Request':
    	$bug_type = 'Request';
    	break;
    case 'Documentation Problem':
    	$bug_type = 'Doc Bug';
    	break;
}
response_header(
	"{$bug_type} #{$bug_id} :: " . htmlspecialchars($bug['sdesc']),
	false,
    " <link rel='alternate' type='application/rdf+xml' title='RSS feed' href='http://{$site_url}{$basedir}/feeds/bug_{$bug_id}.rss' />"
);

// DISPLAY BUG
$thanks = (isset($_GET['thanks'])) ? (int) $_GET['thanks'] : 0;
switch ($thanks)
{
	case 1:
	case 2:
		display_bug_success('The bug was updated successfully.');
		break;
	case 3:
		display_bug_success('Your comment was added to the bug successfully.');
		break;
	case 4:
		display_bug_success("
			Thank you for your help!
			If the status of the bug report you submitted changes, you will be notified.
			You may return here and check the status or update your report at any time.
			The URL for your bug report is: <a href='/bugs/bug.php?id={$bug_id}'>http://{$site_url}{$basedir}/bug.php?id={$bug_id}</a>.
		");
		break;
	case 6:
		display_bug_success('Thanks for voting! Your vote should be reflected in the statistics below.');
		break;

	default:
		
		break;
}

display_bug_error($errors);

show_bugs_menu(txfield('package_name'));

?>

<div id="bugheader">
<table id="details">
  <tr id="title">

   <?php
       echo '<th class="details" id="number">' . $bug_type . '&nbsp;#' . $bug_id . '</th>';
   ?>

   <td id="summary" colspan="3"><?php echo clean($bug['sdesc']) ?></td>
  </tr>
  <tr id="submission">
   <th class="details">Submitted:</th>
<?php

if ($bug['modified']) {
    echo '<td style="white-space: nowrap;">' , format_date($bug['submitted']) , "</td>\n";
    echo '<th class="details">Modified:</th>' , "\n";
    echo '<td style="white-space: nowrap;">' , format_date($bug['modified']) , '</td>';
} else {
    echo '<td colspan="3">' , format_date($bug['submitted']) , '</td>';
}

?>

  </tr>
  <tr id="submitter">
   <th class="details">From:</th>
   <td>
   <?php
    if ($bug['bughandle']) {
        echo '<a href="/user/' . $bug['bughandle'] . '">' . $bug['bughandle'] . '</a>';
    } elseif ($bug['handle'] && $bug['showemail'] != '0') {
        echo '<a href="/user/' . $bug['handle'] . '">' . $bug['handle'] . '</a>';
    } else {
        echo spam_protect(htmlspecialchars($bug['email']));
    }
    ?></td>
   <th class="details">Assigned:</th>
   <td><?php echo htmlspecialchars($bug['assign']) ?></td>
  </tr>
  <tr id="categorization">
   <th class="details">Status:</th>
   <td><?php echo htmlspecialchars($bug['status']) ?></td>
   <th class="details">Package:</th>
   <td><?php echo htmlspecialchars($bug['package_name']); if ($bug['package_version']): ?> (version <?php echo htmlspecialchars($bug['package_version']);?>)<?php endif; ?></td>
  </tr>
  <tr id="situation">
   <th class="details">PHP Version:</th>
   <td><?php echo htmlspecialchars($bug['php_version']) ?></td>
   <th class="details">OS:</th>
   <td><?php echo htmlspecialchars($bug['php_os']) ?></td>
  </tr>
<?php
        $link = Bug_DataObject::bugDB('bugdb_roadmap_link');
        $link->id = $bug_id;
        $link->find(false);
        $links = array();
        while ($link->fetch()) {
            $links[$link->roadmap_id] = true;
        }
        $db = Bug_DataObject::bugDB('bugdb_roadmap');
        $db->package = $bug['package_name'];
        $db->orderBy('releasedate DESC');
        $assignedRoadmap = array();
        if ($db->find(false)) {
            while ($db->fetch()) {
                $released = $dbh->getOne('SELECT releases.id
                 FROM packages, releases, bugdb_roadmap b
                 WHERE
                    b.id=? AND
                    packages.name=b.package AND releases.package=packages.id AND
                    releases.version=b.roadmap_version',
                    array($db->id));
                if (isset($links[$db->id])) {
					$assignedRoadmap[] = '<a href="/bugs/roadmap.php?package=' .
				        $db->package . ($released ? '&showold=1' : '') .
				        '&roadmapdetail=' . $db->roadmap_version .
						'#a' . $db->roadmap_version . '">' . $db->roadmap_version .
						'</a>';
                }
            }
        }
        if (!count($assignedRoadmap)) {
            $assignedRoadmap[] = '(Not assigned)';
        }
        ?>
  <tr id="roadmap">
   <th class="details">Roadmaps: </th>

   <td>
   <?php
    echo implode(', ', $assignedRoadmap);
   ?>
   </td>
   <th>&nbsp;</th>
   <td>&nbsp;</td>
  </tr>

<?php if ($bug['votes']) {?>
  <tr id="votes">
   <th class="details">Votes:</th><td><?php echo $bug['votes'] ?></td>
   <th class="details">Avg. Score:</th><td><?php printf("%.1f &plusmn; %.1f", $bug['average'], $bug['deviation']) ?></td>
   <th class="details">Reproduced:</th><td><?php printf("%d of %d (%.1f%%)",$bug['reproduced'],$bug['tried'],$bug['tried']?($bug['reproduced']/$bug['tried'])*100:0) ?></td>
  </tr>
<?php if ($bug['reproduced']) {?>
  <tr id="reproduced">
   <td colspan="2"></td>
   <th class="details">Same Version:</th><td><?php printf("%d (%.1f%%)",$bug['samever'],($bug['samever']/$bug['reproduced'])*100) ?></td>
   <th class="details">Same OS:</th><td><?php printf("%d (%.1f%%)",$bug['sameos'],($bug['sameos']/$bug['reproduced'])*100) ?></td>
  </tr>
<?php } ?>
<?php } ?>
</table>
</div>

<div id="controls">
<?php
control(0, 'View');
if (!(isset($auth_user) && $auth_user && $auth_user->registered) && $edit != 2) {
    control(3, 'Add Comment');
}
control(1, 'Edit');
?>
</div>
<?php

if (isset($_POST['preview']) && !empty($ncomment)) {
    $preview = '<div class="comment">';
    $preview .= "<strong>[" . format_date(time()) . "] ";
    if (isset($auth_user) && $auth_user) {
        $preview .= '<a href="/user/' . $auth_user->handle . '">' .
            $auth_user->handle . '</a>';
    } else {
        $preview .= spam_protect(htmlspecialchars($from));
    }
    $preview .= "</strong>\n<pre class=\"note\">";
    $comment = wordwrap($ncomment, 72);
    $preview .= make_ticket_links(addlinks($comment));
    $preview .= "</pre>\n";
    $preview .= '</div>';
} else {
    $preview = '';
}

if ($edit == 1 || $edit == 2) {
    ?>

    <form id="update" action="bug.php?id=<?php echo $bug_id; ?>&amp;edit=<?php echo $edit; ?>" method="post">

    <?php

    if ($edit == 2) {
        if (!isset($_POST['in']) && $pw && $bug['passwd'] &&
            $pw == $bug['passwd']) {
            ?>
            <div class="explain">
             Welcome back! Since you opted to store your bug's password in a
             cookie, you can just go ahead and add more information to this
             bug or edit the other fields.
            </div>
<?php  } else { ?>
            <div class="explain">
            <?php if (!isset($_POST['in'])) { ?>
                Welcome back! If you're the original bug submitter, here's
                where you can edit the bug or add additional notes. If this
                is not your bug, you can <a href="bug.php?id=<?php echo $bug_id; ?>&amp;edit=3">add a comment by following this link</a>.
                If this is your bug, but you forgot your password, <a href="bug-pwd-finder.php">you can retrieve your password here</a>.
            <?php } ?>

             <table>
              <tr>
               <th class="details">Passw<span class="accesskey">o</span>rd:</th>
               <td>
                <input type="password" name="pw"
                 value="<?php echo htmlspecialchars($pw) ?>" size="10" maxlength="20"
                 accesskey="o" />
               </td>
               <th class="details">
                <label for="save">
                 Check to remember your password for next time:
                </label>
               </th>
               <td>
                <input type="checkbox" id="save" name="save" <?php echo (isset($_POST['save'])) ? ' checked="checked"' : ''; ?> />
               </td>
              </tr>
             </table>
            </div>
<?php  }
    } else {
        if ($user && $pw && verify_password($user, $pw)) {
            if (!isset($_POST['in']) || !is_array($_POST['in'])) {
?>
                <div class="explain">
                 Welcome back, <?php echo $user; ?>! (Not <?php echo $user; ?>?
                 <a href="?logout=1&amp;id=<?php echo $bug_id; ?>&amp;edit=1">Log out.</a>)
                </div>
<?php
			}
        } else {
?>

            <div class="explain">

<?php		if (!isset($_POST['in']) || !is_array($_POST['in'])) { ?>
                    Welcome! If you don't have a CVS account, you can't do anything here.
                    You can <a href="bug.php?id=<?php echo $bug_id; ?>&amp;edit=3">add a comment by following this link</a>
                    or if you reported this bug, you can <a href="bug.php?id=<?php echo $bug_id; ?>&amp;edit=2">edit this bug over here</a>.
<?php		} ?>
<!--
<table>
<tr>
  <th class="details">CVS Username:</th>
  <td><input type="text" name="user" value="<?php echo htmlspecialchars($user) ?>" size="10" maxlength="20" /></td>
  <th class="details">CVS Password:</th>
  <td><input type="password" name="pw" value="<?php echo htmlspecialchars($pw) ?>" size="10" maxlength="20" /></td>
  <th class="details">
   <label for="save">Remember:</label>
  </th>
  <td>
   <input type="checkbox" id="save" name="save"<?php if ($_POST['save']) echo ' checked="checked"'?> />
  </td>
</tr>
</table>
-->
            </div>

            <?php
        }
    }
    echo $preview;
    ?>

    <table>

    <?php

    if ($edit == 1 && auth_check('pear.dev')):
        // Developer Edit Form
        ?>

        <tr>
         <th class="details">
          <label for="in" accesskey="c">Qui<span class="accesskey">c</span>k Fix:</label>
         </th>
         <td colspan="3">
          <select name="in[resolve]" id="in">
           <?php show_reason_types((isset($_POST['in']) && isset($_POST['in']['resolve'])) ? $_POST['in']['resolve'] : -1, 1) ?>
          </select>

          <?php
          if (isset($_POST['in']) && !empty($_POST['in']['resolve'])) {
              ?>

              <input type="hidden" name="trytoforce" value="1" />

              <?php
          }
          ?>

          <small>(<a href="/bugs/quick-fix-desc.php">description</a>)</small>
         </td>
        </tr>
    <?php
    endif; // if ($edit == 1 && auth_check('pear.dev'))
    ?>

     <tr>
      <th class="details">Status:</th>
      <td <?php echo (($edit != 1) ? 'colspan="3"' : '' ) ?>>
       <select name="in[status]">
        <?php show_state_options(isset($_POST['in']) && isset($_POST['in']['status']) ?
            $_POST['in']['status'] : '', $edit, $bug['status']) ?>
       </select>

    <?php
    if ($edit == 1 && auth_check('pear.dev')) {
        ?>

        </td>
        <th class="details">Assign to:</th>
        <td>
         <input type="text" size="10" maxlength="16" name="in[assign]"
          value="<?php echo field('assign') ?>" />

        <?php
    }
    ?>

       <input type="hidden" name="id" value="<?php echo $bug_id ?>" />
       <input type="hidden" name="edit" value="<?php echo $edit ?>" />
       <input type="submit" value="Submit" />
      </td>
     </tr>
     <tr>
      <th class="details">Package:</th>
      <td colspan="3">
       <select name="in[package_name]">
        <?php show_types(isset($_POST['in']) && isset($_POST['in']['package_name']) ?
            $_POST['in']['package_name'] : '', 0, $bug['package_name']) ?>
       </select>
      </td>
     </tr>
     <tr>
      <th class="details">Bug Type:</th>
       <td colspan="3">
        <select name="in[bug_type]">
            <?php show_type_options($bug['bug_type']); ?>
        </select>
      </td>
     </tr>
     <tr>
      <th class="details">Summary:</th>
      <td colspan="3">
       <input type="text" size="60" maxlength="80" name="in[sdesc]"
        value="<?php echo field('sdesc') ?>" />
      </td>
     </tr>
     <tr>
      <th class="details">From:</th>
      <td colspan="3">
       <?php echo spam_protect(field('email')) ?>
      </td>
     </tr>
     <tr>
      <th class="details">New email:</th>
      <td colspan="3">
       <input type="text" size="40" maxlength="40" name="in[email]"
        value="<?php echo (isset($_POST['in']) && isset($_POST['in']['email']) ?
            $_POST['in']['email'] : '') ?>" />
      </td>
     </tr>
     <tr>
      <th class="details">PHP Version:</th>
      <td>
       <input type="text" size="20" maxlength="100" name="in[php_version]"
        value="<?php echo field('php_version') ?>" />
      </td>
      <th class="details">Package Version:</th>
      <td>
       <input type="text" size="20" maxlength="100" name="in[package_version]"
        value="<?php echo field('package_version') ?>" />
      </td>
      <th class="details">OS:</th>
      <td>
       <input type="text" size="20" maxlength="32" name="in[php_os]"
        value="<?php echo field('php_os') ?>" />
      </td>
     </tr>
     <?php if (auth_check('pear.dev')): ?>
     <tr>
      <th class="details">Assigned to <br />Roadmap Version(s):<br />
      (<span class="headerbottom">Already released</span>)</th>
      <td colspan="5"><?php
        $link = Bug_DataObject::bugDB('bugdb_roadmap_link');
        $link->id = $bug_id;
        $link->find(false);
        $links = array();
        while ($link->fetch()) {
            $links[$link->roadmap_id] = true;
        }
        $db = Bug_DataObject::bugDB('bugdb_roadmap');
        $db->package = $bug['package_name'];
        $db->orderBy('releasedate DESC');
        if ($db->find(false)) {
            while ($db->fetch()) {
                $released = $dbh->getOne('SELECT releases.id
                 FROM packages, releases, bugdb_roadmap b
                 WHERE
                    b.id=? AND
                    packages.name=b.package AND releases.package=packages.id AND
                    releases.version=b.roadmap_version',
                    array($db->id));
                if ($released) {
                    echo '<span class="headerbottom">';
                }
                ?><input type="checkbox" name="in[fixed_versions][]" value="<?php
                echo $db->id . '"';
                if (isset($links[$db->id])) {
                    echo ' checked="true"';
                }?>/> <?php echo $db->roadmap_version; '<br />';
                if ($released) {
                    echo '</span>';
                }
            }
        } else {
            ?>(No roadmap defined)<?php
        }
        ?>
      </td>
     </tr>
     <?php endif; //if (auth_check('pear.dev'))?>
     <tr>
    </table>
    <div class="explain">
     <h1><a href="/bugs/patch-add.php?bug=<?php echo $bug_id ?>">Click Here to Submit a Patch</a></h1>
    </div>
    <p style="margin-bottom: 0em">
    <label for="ncomment" accesskey="m"><b>New<?php if ($edit==1) echo "/Additional"?> Co<span class="accesskey">m</span>ment:</b></label>
    </p>

    <textarea cols="60" rows="8" name="ncomment" id="ncomment"
     wrap="physical"><?php echo clean($ncomment) ?></textarea>

    <p style="margin-top: 0em">
        <input type="submit" name="preview" value="Preview">&nbsp;<input type="submit" value="Submit" />
    </p>
    </form>

    <?php
} // if ($edit == 1 || $edit == 2)

if (isset($auth_user) && $auth_user && $auth_user->registered) {

?>
<div class="explain">

    <form name="subscribetobug" action="/bugs/bug.php?id=<?php echo $bug_id; ?>" method="post">
    <table>
      <tr>
       <th class="details" colspan="2">Subscribe to this entry?</th>
      </tr>
      <tr>
       <th class="details"><label for="subscribe_email">Your email:</label></th>
       <td><input type="text" id="subscribe_email" name="subscribe_email" value="" /></td>
      </tr>
      <tr>
       <td class="details"><input type="submit" name="subscribe_to_bug" value="Subscribe" /></td>
       <td class="details"><input type="submit" name="unsubscribe_to_bug" value="Unsubscribe" /></td>
      </tr>
    </table>
    </form>
</div>
<?php
}

if ($edit == 3) {
$action = htmlspecialchars($_SERVER['PHP_SELF']);
?>
    <form name="comment" id="comment" action="<?php echo $action ?>" method="post">
<?php if (isset($auth_user) && $auth_user): ?>
    <div class="explain">
     <h1><a href="/bugs/patch-add.php?bug=<?php echo $bug_id ?>">Click Here to Submit a Patch</a></h1>
    </div>

<?php endif; //if ($auth_user) ?>

    <?php
    if (!isset($_POST['in'])) {
        ?>

        <div class="explain">
         Anyone can comment on a bug. Have a simpler test case? Does it
         work for you on a different platform? Let us know! Just going to
         say 'Me too!'? Don't clutter the database with that please

         <?php
         if (canvote($thanks, $bug['status'])) {
             echo ' &mdash; but make sure to <a href="';
             echo htmlspecialchars($_SERVER['PHP_SELF']) . '?id=' . $bug_id . '">vote on the bug</a>';
         }
         ?>!

        </div>

        <?php
    }
    echo $preview;
    ?>


    <table>
     <?php if (!isset($auth_user) || !$auth_user): ?>
     <tr>
      <th class="details">Y<span class="accesskey">o</span>ur email address:<br />
      <strong>MUST BE VALID</strong></th>
      <td class="form-input">
       <input type="text" size="40" maxlength="40" name="in[commentemail]"
        value="<?php echo clean(isset($_POST['in']) && isset($_POST['in']['commentemail']) ?
            $_POST['in']['commentemail'] : '') ?>"
        accesskey="o" />
       <input type="hidden" name="id" value="<?php echo $bug_id ?>" />
       <input type="hidden" name="edit" value="<?php echo $edit?>" />
      </td>
     </tr>
     <tr>
      <th>Solve the problem : <?php print $numeralCaptcha->getOperation(); ?> = ?</th>
      <td class="form-input"><input type="text" name="captcha" /></td>
     </tr>
     <?php $_SESSION['answer'] = $numeralCaptcha->getAnswer(); ?>
     <?php endif; // if (!$auth_user): ?>
    </table>

    <div>
     <input type="hidden" name="id" value="<?php echo $bug_id ?>" />
     <input type="hidden" name="edit" value="<?php echo $edit ?>" />
     <textarea cols="60" rows="10" name="ncomment"
      wrap="physical"><?php echo clean($ncomment) ?></textarea>
     <br /><input type="submit" name="preview" value="Preview">&nbsp;<input type="submit" value="Submit" />
    </div>

    </form>

    <?php
} // if ($edit == 3)


if (!$edit && canvote($thanks, $bug['status']))
{
?>
  <form id="vote" method="post" action="vote.php">
  <div class="sect">
   <fieldset>
    <legend>Have you experienced this issue?</legend>
    <div>
     <input type="radio" id="rep-y" name="reproduced" value="1" onchange="show('canreproduce')" /> <label for="rep-y">yes</label>
     <input type="radio" id="rep-n" name="reproduced" value="0" onchange="hide('canreproduce')" /> <label for="rep-n">no</label>
     <input type="radio" id="rep-d" name="reproduced" value="2" onchange="hide('canreproduce')" checked="checked" /> <label for="rep-d">don't know</label>
    </div>
   </fieldset>
   <fieldset>
    <legend>Rate the importance of this bug to you:</legend>
    <div>
     <label for="score-5">high</label>
     <input type="radio" id="score-5" name="score" value="2" />
     <input type="radio" id="score-4" name="score" value="1" />
     <input type="radio" id="score-3" name="score" value="0" checked="checked" />
     <input type="radio" id="score-2" name="score" value="-1" />
     <input type="radio" id="score-1" name="score" value="-2" />
     <label for="score-1">low</label>
    </div>
   </fieldset>
  </div>
  <div id="canreproduce" class="sect" style="display: none">
   <fieldset>
    <legend>Are you using the same PHP version?</legend>
    <div>
     <input type="radio" id="ver-y" name="samever" value="1" /> <label for="ver-y">yes</label>
     <input type="radio" id="ver-n" name="samever" value="0" checked="checked" /> <label for="ver-n">no</label>
    </div>
   </fieldset>
   <fieldset>
    <legend>Are you using the same Package version?</legend>
    <div>
     <input type="radio" id="ver-y" name="samever" value="1" /> <label for="ver-y">yes</label>
     <input type="radio" id="ver-n" name="samever" value="0" checked="checked" /> <label for="ver-n">no</label>
    </div>
   </fieldset>
   <fieldset>
    <legend>Are you using the same operating system?</legend>
    <div>
     <input type="radio" id="os-y" name="sameos" value="1" /> <label for="os-y">yes</label>
     <input type="radio" id="os-n" name="sameos" value="0" checked="checked" /> <label for="os-n">no</label>
    </div>
   </fieldset>
  </div>
  <div id="submit" class="sect">
   <input type="hidden" name="id" value="<?php echo $bug_id?>" />
   <input type="submit" value="Vote" />
  </div>
  </form>
  <br clear="all" />
<?php
}

// Display original report
if ($bug['ldesc']) {
    output_note(0, $bug['submitted'], $bug['email'], $bug['ldesc'], $bug['showemail'], $bug['bughandle'], $bug['reporter_name'], 1);
}

// Display patches
require_once 'include/classes/patchtracker.php';
$patches = new Bugs_Patchtracker;
$p = $patches->listPatches($bug_id);
?>
<h2>Patches</h2>
<a href="patch-add.php?bug=<?php echo $bug_id; ?>">Add a Patch</a><br />
<?php
foreach ($p as $name => $revisions)
{
    $obsolete = $patches->getObsoletingPatches($bug['id'], $name, $revisions[0][0]);
    $style = !empty($obsolete) ? ' style="background-color: yellow; text-decoration: line-through;" ' : '';
    ?><a href="patch-display.php?bug=<?php echo $bug['id'] ?>&patch=<?php
        echo urlencode($name) ?>&revision=latest" <?php echo $style; ?>><?php echo clean($name) ?></a> (last revision <?php echo format_date($revisions[0][0]) ?> by <?php echo $revisions[0][1] ?>)<br /><?php
        echo "\n";
}
// Display comments
$query = 'SELECT c.id,c.email,c.comment,UNIX_TIMESTAMP(c.ts) AS added, c.reporter_name as comment_name, IF(c.handle <> "",u.registered,1) as registered,
    u.showemail, u.handle,c.handle as bughandle
    FROM bugdb_comments c
    LEFT JOIN users u ON u.handle = c.handle
    WHERE c.bug = ?
    GROUP BY c.id ORDER BY c.ts';
$res =& $dbh->query($query, array($bug_id));
if ($res) {
    ?><h2>Comments</h2><?php
    while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        output_note($row['id'], $row['added'], $row['email'], $row['comment'], $row['showemail'], $row['bughandle'] ? $row['bughandle'] : $row['handle'], $row['comment_name'], $row['registered']);
    }
}

response_footer();


function output_note($com_id, $ts, $email, $comment, $showemail = 1, $handle = NULL, $comment_name = NULL, $registered)
{
    global $edit, $bug_id, $trusted_developers, $user, $dbh;

    echo '<div class="comment">';
    echo '<a name="' , urlencode($ts) , '">&nbsp;</a>';
    echo "<strong>[" , format_date($ts) , "] ";
    if (!$registered) {
		$handle_out = urlencode($handle);
        echo <<< DATA
User who submitted this comment has not confirmed identity</strong>
<pre class="note">
If you submitted this note, please check your email.
If you did not receive any message, <a href="resend-request-email.php?handle={$handle_out}">click here to re-send</a>
MANUAL CONFIRMATION IS NOT POSSIBLE.  Write a message to <a href='mailto:pear-dev@lists.php.net'>pear-dev@lists.php.net</a>
to request the confirmation link.  All bugs/comments/patches associated with this email address will be deleted within 48 hours 
if the account request is not confirmed!</pre>
</div>
DATA;
        return;
    }
    if ($handle) {
        echo "<a href='/user/{$handle}'>{$handle}</a></strong>\n";
    } else {
        echo spam_protect(htmlspecialchars($email)) , "</strong>\n";
    }
    if ($comment_name) {
        echo '(' , htmlspecialchars($comment_name) , ')';
    }
    // Delete comment action only for trusted developers
    echo ($edit == 1 && $com_id !== 0 && in_array($user, $trusted_developers)) ? "<a href='bug.php?id={$bug_id}&amp;edit=1&amp;delete_comment={$com_id}'>[delete]</a>\n" : '';
    $comment = wordwrap($comment, 72);
    $comment = make_ticket_links(addlinks($comment));
    echo "<pre class='note'>\n{$comment}</pre>\n</div>\n";
}

function delete_comment($bug_id, $com_id)
{
    global $dbh;
    
    $res =& $dbh->query("DELETE FROM bugdb_comments WHERE bug='{$bug_id}' AND id='{$com_id}'");
}

function control($num, $desc)
{
	global $bug_id, $edit;
	
    echo "<span id='control_{$num}' class='control";
    if ($edit == $num) {
        echo ' active">';
        echo $desc;
    } else {
        echo "'><a href='bug.php?id={$bug_id}" , (($num) ? "&amp;edit={$num}" : '') , "'>{$desc}</a>";
    }
    echo "</span>\n";
}

function canvote($thanks, $status)
{
    return ($thanks != 4 && $thanks != 6 && $status != 'Closed' && $status != 'Bogus' && $status != 'Duplicate');
}

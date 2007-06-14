<?php

/**
 * Creates the $RESOLVE_REASONS array
 *
 * This source file is subject to version 3.0 of the PHP license,
 * that is bundled with this package in the file LICENSE, and is
 * available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_0.txt.
 * If you did not receive a copy of the PHP license and are unable to
 * obtain it through the world-wide-web, please send a note to
 * license@php.net so we can mail you a copy immediately.
 *
 * @category  peclweb
 * @package   Bugs
 * @copyright Copyright (c) 1997-2004 The PHP Group
 * @license   http://www.php.net/license/3_0.txt  PHP License
 * @version   $Id$
 */

$RESOLVE_REASONS = array(
/*
  'trysnapshot4' => array(
    'status' => 'Feedback',
    'desc' => 'Try a CVS snapshot (php4)',
    'message' =>
'Please try using this CVS snapshot:

  http://snaps.php.net/php4-STABLE-latest.tar.gz
 
For Windows:
 
  http://snaps.php.net/win32/php4-win32-STABLE-latest.zip',
  ),

  'trysnapshot5' => array(
    'status' => 'Feedback',
    'desc' => 'Try a CVS snapshot (php5)',
    'message' =>
'Please try using this CVS snapshot:

  http://snaps.php.net/php5-latest.tar.gz
 
For Windows:
 
  http://snaps.php.net/win32/php5-win32-latest.zip',
  ),
*/
  'fixedcvs' => array(
    'status' => 'Closed',
    'desc' => 'Fixed in CVS',
    'message' =>
'This bug has been fixed in CVS.

In case this was a documentation problem, the fix will show up at the
end of next Sunday (CET) on pecl.php.net.

In case this was a pecl.php.net website problem, the change will show
up on the website in short time.
 
Thank you for the report, and for helping us make PECL better.',
  ),
  'alreadyfixed' => array(
    'status' => 'Closed',
    'desc' => 'Fixed in release',
    'message' =>
'Thank you for your bug report. This issue has been fixed
in the latest released version of the package, which you can download at
http://pecl.php.net/get/' . @txfield('package_name'),
  ),
  'needtrace' => array(
    'status' => 'Feedback',
    'desc' => 'Need backtrace',
    'message' =>
'Thank you for this bug report. To properly diagnose the problem, we
need a backtrace to see what is happening behind the scenes. To
find out how to generate a backtrace, please read
http://bugs.php.net/bugs-generating-backtrace.php for *NIX and
http://bugs.php.net/bugs-generating-backtrace-win32.php for Win32

Once you have generated a backtrace, please submit it to this bug
report and change the status back to "Open". Thank you for helping
us make PHP better.',
  ),
  'oldversion' => array(
    'status' => 'Bogus',
    'desc' => 'Try newer package version',
    'message' =>
'Thank you for taking the time to report a problem with the package.
Unfortunately you are not using a current version of the package -- 
the problem might already be fixed. Please download a new
version from http://pecl.php.net/packages.php

If you are able to reproduce the bug with one of the latest
versions, please change the package version on this bug report
to the version you tested and change the status back to "Open".
Again, thank you for your continued support of PECL.',
  ),
  'oldphpversion' => array(
    'status' => 'Bogus',
    'desc' => 'Try newer PHP version',
    'message' =>
'Thank you for taking the time to report a problem with PHP.
Unfortunately you are not using a current version of PHP -- 
the problem might already be fixed. Please download a new
PHP version from http://www.php.net/downloads.php

If you are able to reproduce the bug with one of the latest
versions of PHP, please change the PHP version on this bug report
to the version you tested and change the status back to "Open".
Again, thank you for your continued support of PEAR.'
  ),
  'support' => array(
    'status' => 'Bogus',
    'desc' => 'Not developer issue',
    'message' =>
'Sorry, but your problem does not imply a bug in PECL itself.  For a
list of more appropriate places to ask for help using PECL, please
visit http://pecl.php.net/support/ as this bug system is not the
appropriate forum for asking support questions. 

Thank you for your interest in PECL.',
  ),
  'nofeedback' => array(
    'status' => 'No Feedback',
    'desc' => 'No feedback',
    'webonly' => 1,
    'message' =>
'No feedback was provided. The bug is being suspended because
we assume that you are no longer experiencing the problem.
If this is not the case and you are able to provide the
information that was requested earlier, please do so and
change the status of the bug back to "Open". Thank you.',
  ),
  'notwrong' => array(
    'status' => 'Bogus',
    'desc' => 'Expected behavior',
    'message' =>
'Thank you for taking the time to write to us, but this is not
a bug.'
  ),
  'notenoughinfo' => array(
    'status' => 'Feedback',
    'desc' => 'Not enough info',
    'message' =>
'Not enough information was provided for us to be able
to handle this bug. Please re-read the instructions at
http://bugs.php.net/how-to-report.php

If you can provide more information, feel free to add it
to this bug and change the status back to "Open".

Thank you for your interest in PECL.
',
  ),
  'submittedtwice' => array(
    'status' => 'Bogus',
    'desc' => 'Submitted twice',
    'message' =>
'Please do not submit the same bug more than once. An existing
bug report already describes this very problem. Even if you feel
that your issue is somewhat different, the resolution is likely
to be the same. Because of this, we hope you add your comments
to the existing bug instead.

Thank you for your interest in PECL.',
  )
);

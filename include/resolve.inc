<?php

$RESOLVE_REASONS = array(
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

  'fixedcvs' => array(
    'status' => 'Closed',
    'desc' => 'Fixed in CVS',
    'message' =>
'This bug has been fixed in CVS.

In case this was a PHP problem, snapshots of the sources are packaged
every three hours; this change will be in the next snapshot. You can
grab the snapshot at http://snaps.php.net/.
 
In case this was a documentation problem, the fix will show up soon at
http://www.php.net/manual/.

In case this was a PHP.net website problem, the change will show
up on the PHP.net site and on the mirror sites in short time.
 
Thank you for the report, and for helping us make PHP better.',
  ),
  'alreadyfixed' => array(
    'status' => 'Closed',
    'desc' => 'Fixed in release',
    'message' =>
'Thank you for your bug report. This issue has already been fixed
in the latest released version of PHP, which you can download at 
http://www.php.net/downloads.php',
  ),
  'needtrace' => array(
    'status' => 'Feedback',
    'desc' => 'Need backtrace',
    'message' =>
'Thank you for this bug report. To properly diagnose the problem, we
need a backtrace to see what is happening behind the scenes. To
find out how to generate a backtrace, please read
http://bugs.php.net/bugs-generating-backtrace.php

Once you have generated a backtrace, please submit it to this bug
report and change the status back to "Open". Thank you for helping
us make PHP better.',
  ),
  'oldversion' => array(
    'status' => 'Bogus',
    'desc' => 'Try newer version',
    'message' =>
'Thank you for taking the time to report a problem with PHP.
Unfortunately you are not using a current version of PHP -- 
the problem might already be fixed. Please download a new
PHP version from http://www.php.net/downloads.php

If you are able to reproduce the bug with one of the latest
versions of PHP, please change the PHP version on this bug report
to the version you tested and change the status back to "Open".
Again, thank you for your continued support of PHP.',
  ),
  'support' => array(
    'status' => 'Bogus',
    'desc' => 'Not developer issue',
    'message' =>
'Sorry, but your problem does not imply a bug in PHP itself.  For a
list of more appropriate places to ask for help using PHP, please
visit http://www.php.net/support.php as this bug system is not the
appropriate forum for asking support questions. 

Thank you for your interest in PHP.',
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
a bug. Please double-check the documentation available at
http://www.php.net/manual/ and the instructions on how to report
a bug at http://bugs.php.net/how-to-report.php',
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

Thank you for your interest in PHP.
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

Thank you for your interest in PHP.',
  ),
  'globals' => array(
    'status' => 'Bogus',
    'desc' => 'register_globals',
    'message' =>
'In PHP 4.2.0, the \'register_globals\' setting default changed to
\'off\'. See http://www.php.net/release_4_2_0.php for more info.
We are sorry about the inconvenience, but this change was a necessary
part of our efforts to make PHP scripting more secure and portable.',
  ),
  'php3' => array(
    'status' => 'Wont Fix',
    'desc' => 'PHP 3 support discontinued',
    'message' =>
'We are sorry, but can not support PHP 3 related problems anymore.
Momentum is gathering for PHP 5, and we think supporting PHP 3 will
lead to a waste of resources which we want to put into getting PHP 5
ready. Ofcourse PHP 4 will will continue to be supported for the
forseeable future.',
  ),
  'dst' => array(
    'status' => 'Bogus',
    'desc' => 'Daylight Savings',
    'message' =>
'We are happy to tell you that you just discovered Daylight Savings
Time. For more information see:
http://webexhibits.org/daylightsaving/b.html
Instead of using mktime/date consider using gmmktime and gmdate which do
not suffer from DST.',
  ),
    'isapi' => array(
    'status' => 'Bogus',
    'desc' => 'IIS Stability',
    'message' =>
'We are aware of PHP\'s problems with stability under IIS and are working 
to rectify the problem. Unfortunatly your bug report does not contain any
extra useful information and we already have enough bug reports open about
this issue. If you can provide more detailed information such as a 
reproducable crash or a backtrace please do so and reopen this bug. 
Otherwise please keep trying new releases as we are working to resolve 
the problems on this platform
 
Thanks for your interest in PHP.',
  ),
    'gnused' => array(
    'status' => 'Bogus',
    'desc' => 'Install GNU Sed',
    'message' =>
'Due to a bug in the installed sed on your system the build
fails. Install GNU sed and it should be okay.
 
Thank you for your interest in PHP.',
  ),

);

function show_reason_types($current="", $expanded=0)
{
	if ($expanded) echo "<option value=\"\"></option>\n";
	while (list($k,$v) = each($GLOBALS['RESOLVE_REASONS'])) {
		echo "<option value=\"$k\"", ($current==$k ? " selected" : ""),
		     ">$v[desc]",($expanded?" ($v[status])":""),"</option>\n";
	}
}

<?php

$RESOLVE_REASONS = array(
  'fixedcvs' => array(
    'status' => 'Closed',
    'desc' => 'Fixed in CVS',
    'message' =>
'This bug has been fixed in CVS.',
  ),
  'alreadyfixed' => array(
    'status' => 'Closed',
    'desc' => 'Fixed in release',
    'message' =>
'This bug has already been fixed in the latest released version of
PHP, which you can download at http://www.php.net/downloads.php',
  ),
  'needtrace' => array(
    'status' => 'Feedback',
    'desc' => 'Need backtrace',
    'message' =>
'To properly diagnose this bug, we need a backtrace to see what is
happening behind the scenes. To find out how to generate a backtrace,
please read http://bugs.php.net/bugs-generating-backtrace.php

Once you have generated a backtrace, please submit it to this bug
report and change the status back to "Open".',
  ),
  'oldversion' => array(
    'status' => 'Bogus',
    'desc' => 'Try newer version',
    'message' =>
'The version of PHP that this bug was reported in is too old. Please
try to reproduce this bug in the latest version of PHP (available
from http://www.php.net/downloads.php

If you are still able to reproduce the bug with one of the latest
versions of PHP, please change the PHP version on this bug report
to the version you tested and change the status back to "Open".',
  ),
  'support' => array(
    'status' => 'Bogus',
    'desc' => 'Not for support',
    'message' =>
'The bug system is not the appropriate forum for asking support
questions. For a list of a range of more appropriate places to ask
for help using PHP, please visit http://www.php.net/support.php',
  ),
  'nofeedback' => array(
    'status' => 'No Feedback',
    'desc' => 'No feedback',
    'webonly' => 1,
    'message' =>
'No feedback was provided for this bug, so it is being suspended.
If you are able to provide the information that was requested,
please do so and change the status of the bug back to "Open".',
  ),
  'notwrong' => array(
    'status' => 'Bogus',
    'desc' => 'Not wrong behavior',
    'message' =>
'This is not a bug. Please double-check the documentation available
at http://www.php.net/manual/ and the instructions on how to report
a bug at http://bugs.php.net/how-to-report.php',
  ),
  'notenoughinfo' => array(
    'status' => 'Bogus',
    'desc' => 'Not enough info',
    'message' =>
'Not enough information was provided for us to be able
to handle this bug. Please re-read the instructions at
http://bugs.php.net/how-to-report.php

If you can provide more information, feel free to add it
to this bug and change the status back to "Open".
',
  ),
);

function show_reason_types($current="")
{
	while (list($k,$v) = each($GLOBALS['RESOLVE_REASONS'])) {
		echo "<option value=\"$k\"", ($current==$k ? " selected" : ""),
		     ">$v[desc]</option>\n";
	}
}

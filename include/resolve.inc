<?php

$RESOLVE_REASONS = array(
  'trysnapshot44' => array(
    'status' => 'Feedback',
    'desc' => 'Try a CVS snapshot (PHP 4.4)',
    'message' =>
'Please try using this CVS snapshot:

  http://snaps.php.net/php4-STABLE-latest.tar.gz
 
For Windows:
 
  http://snaps.php.net/win32/php4-win32-STABLE-latest.zip',
  ),

  'trysnapshot52' => array(
    'status' => 'Feedback',
    'desc' => 'Try a CVS snapshot (PHP 5.2)',
    'message' =>
'Please try using this CVS snapshot:

  http://snaps.php.net/php5.2-latest.tar.gz
 
For Windows (zip):
 
  http://snaps.php.net/win32/php5.2-win32-latest.zip

For Windows (installer):

  http://snaps.php.net/win32/php5.2-win32-installer-latest.msi',
  ),

  'trysnapshot53' => array(
    'status' => 'Feedback',
    'desc' => 'Try a CVS snapshot (PHP 5.3)',
    'message' =>
'Please try using this CVS snapshot:

  http://snaps.php.net/php5.3-latest.tar.gz
 
For Windows (zip):
 
  http://snaps.php.net/win32/php5.3-win32-latest.zip

For Windows (installer):

  http://snaps.php.net/win32/php5.3-win32-installer-latest.msi',
  ),

  'trysnapshot60' => array(
    'status' => 'Feedback',
    'desc' => 'Try a CVS snapshot (PHP 6.0)',
    'message' =>
'Please try using this CVS snapshot:

  http://snaps.php.net/php6.0-latest.tar.gz
 
For Windows:
 
  http://snaps.php.net/win32/php6.0-win32-latest.zip',
  ),

  'fixedcvs' => array(
    'status' => 'Closed',
    'desc' => 'Fixed in CVS',
    'message' =>
'This bug has been fixed in CVS.

Snapshots of the sources are packaged every three hours; this change
will be in the next snapshot. You can grab the snapshot at
http://snaps.php.net/.
 
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
http://bugs.php.net/bugs-generating-backtrace.php for *NIX and
http://bugs.php.net/bugs-generating-backtrace-win32.php for Win32

Once you have generated a backtrace, please submit it to this bug
report and change the status back to "Open". Thank you for helping
us make PHP better.',
  ),
  'needscript' => array(
    'status' => 'Feedback',
    'desc' => 'Need Reproduce Script',
    'message' =>
'Thank you for this bug report. To properly diagnose the problem, we
need a short but complete example script to be able to reproduce
this bug ourselves. 

A proper reproducing script starts with <?php and ends with ?>,
is max. 10-20 lines long and does not require any external 
resources such as databases, etc. If the script requires a 
database to demonstrate the issue, please make sure it creates 
all necessary tables, stored procedures etc.

Please avoid embedding huge scripts into the report.',
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
appropriate forum for asking support questions.  Due to the volume
of reports we can not explain in detail here why your report is not
a bug.  The support channels will be able to provide an explanation
for you.

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
to be the same. 

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
'We are sorry, but we can not support PHP 3 related problems anymore.
Momentum is gathering for PHP 5, and we think supporting PHP 3 will
lead to a waste of resources which we want to put into getting PHP 5
ready. Of course PHP 4 will continue to be supported for the
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
    'float' => array(
    'status' => 'Bogus',
    'desc' => 'Floating point limitations',
    'message' => 
'Floating point values have a limited precision. Hence a value might 
not have the same string representation after any processing. That also
includes writing a floating point value in your script and directly 
printing it without any mathematical operations.

If you would like to know more about "floats" and what IEEE
754 is read this:
http://docs.sun.com/source/806-3568/ncg_goldberg.html
 
Thank you for your interest in PHP.',
  ),

	'nozend' => array(
	'status' => 'Bogus',
	'desc' => 'No Zend Extensions',
	'message' =>
'Do not file bugs when you have Zend extensions (zend_extension=)
loaded. Examples are Zend Optimizer, Zend Debugger, Turck MM Cache,
APC, Xdebug and ionCube loader.  These extensions often modify engine
behavior which is not related to PHP itself.',
  ),

	'mysqlcfg' => array(
	'status' => 'Bogus',
	'desc' => 'MySQL Configuration Error',
	'message' =>
'When using the mysqli extension together with the mysql extension
you have to use the same libraries and include files. mysqli
extension requires the location of mysql_config file, mysql
extension requires the path of your mysql installation.

If you installed MySQL 4.1 for example with prefix /usr/local/mysql-4.1
your configure settings should be
--with-mysql=/usr/local/mysql-4.1
--with-mysqli=/usr/local/mysql-4.1/bin/mysql_config',
  ),

);

$FIX_VARIATIONS = array(
  'fixedcvs' => array(
    
    'Website problem' =>
'This bug has been fixed in CVS. Since the websites are not directly
updated from the CVS server, the fix might need some time to spread
across the globe to all mirror sites, including PHP.net itself.

Thank you for the report, and for helping us make PHP.net better.',
    
    'Documentation problem' =>
'This bug has been fixed in the documentation\'s XML sources. Since the
online and downloadable versions of the documentation need some time
to get updated, we would like to ask you to be a bit patient.

Thank you for the report, and for helping us make our documentation better.',

	'Translation problem' => 
'This bug has been fixed in the documentation\'s XML sources. Since the
online and downloadable versions of the documentation need some time
to get updated, we would like to ask you to be a bit patient.

Thank you for the report, and for helping us make our documentation better.',

    'DocWeb problem' =>
'This bug has been fixed in CVS. Since the PHP Documentation Tools website
is updated from the CVS repository at a regular interval, the fix might need
some time to take effect.

Thank you for the report, and for helping us make DocWeb better.',
  )
);

function show_reason_types($current="", $expanded=0)
{
	if ($expanded) echo "<option value=\"\"></option>\n";
	while (list($k,$v) = each($GLOBALS['RESOLVE_REASONS'])) {
		echo "<option value=\"$k\"", ($current==$k ? " selected" : ""),
		     ">$v[desc]",($expanded?" ($v[status])":""),"</option>\n";
	}
}

<?php

namespace App\Repository;

/**
 * Repository class for returning "quick fix" reasons
 * as if they had come from a database.
 *
 * As of July 2019, these are no longer stored in bugsdb,
 * but are included as a hard-coded list.
 */
class ReasonRepository
{
    const REASONS = [
		[
            'id' => '4',
            'name' => 'fixed',
            'status' => 'Closed',
            'title' => 'Fix committed',
            'message' => 'The fix for this bug has been committed.
If you are still experiencing this bug, try to check out latest source from https://github.com/php/php-src and re-test.
Thank you for the report, and for helping us make PHP better.',
            'project' => 'php',
            'package_name' => '',
            'webonly' => '0',
        ], [
            'id' => '5',
            'name' => 'fixed',
            'status' => 'Closed',
            'title' => 'Fix committed',
            'message' => 'The fix for this bug has been committed. Since the websites are not directly
updated from the repository, the fix might need some time to spread
across the globe to all mirror sites, including PHP.net itself.

Thank you for the report, and for helping us make PHP.net better.',
            'project' => 'php',
            'package_name' => 'Website problem',
            'webonly' => '0',
        ], [
            'id' => '6',
            'name' => 'fixed',
            'status' => 'Closed',
            'title' => 'Fix committed',
            'message' => 'This bug has been fixed in the documentation\'s XML sources. Since the
online and downloadable versions of the documentation need some time
to get updated, we would like to ask you to be a bit patient.

Thank you for the report, and for helping us make our documentation better.',
            'project' => 'php',
            'package_name' => 'Documentation problem',
            'webonly' => '0',
        ], [
            'id' => '7',
            'name' => 'fixed',
            'status' => 'Closed',
            'title' => 'Fix committed',
            'message' => 'This bug has been fixed in the documentation\'s XML sources. Since the
online and downloadable versions of the documentation need some time
to get updated, we would like to ask you to be a bit patient.

Thank you for the report, and for helping us make our documentation better.',
            'project' => 'php',
            'package_name' => 'Translation problem',
            'webonly' => '0',
        ], [
            'id' => '8',
            'name' => 'fixed',
            'status' => 'Closed',
            'title' => 'Fix committed',
            'message' => 'The fix for this bug has been committed. Since the websites are not directly
updated from the repository, the fix might need some time to spread
across the globe to all mirror sites, including PHP.net itself.

Thank you for the report, and for helping us make PHP.net better.',
            'project' => 'php',
            'package_name' => 'Doc Build problem',
            'webonly' => '0',
        ], [
            'id' => '9',
            'name' => 'fixed',
            'status' => 'Closed',
            'title' => 'Fix committed',
            'message' => 'This bug has been fixed and the fix has been committed. It should show up online in an
hour or three.

Thank you for the report, and for helping us make PHP.net better.',
            'project' => 'php',
            'package_name' => 'Online Doc Editor problem',
            'webonly' => '0',
        ], [
            'id' => '10',
            'name' => 'fixed',
            'status' => 'Closed',
            'title' => 'Fix committed',
            'message' => 'The fix for this bug has been committed. Since the PHP Documentation Tools website
is updated from the repository at a regular interval, the fix might need
some time to take effect.

Thank you for the report, and for helping us make DocWeb better.',
            'project' => 'php',
            'package_name' => 'DocWeb problem',
            'webonly' => '0',
        ], [
            'id' => '12',
            'name' => 'alreadyfixed',
            'status' => 'Closed',
            'title' => 'Fixed in release',
            'message' => 'Thank you for your bug report. This issue has already been fixed
in the latest released version of PHP, which you can download at
http://www.php.net/downloads.php',
            'project' => 'php',
            'package_name' => '',
            'webonly' => '0',
        ], [
            'id' => '13',
            'name' => 'needtrace',
            'status' => 'Feedback',
            'title' => 'Need backtrace',
            'message' => 'Thank you for this bug report. To properly diagnose the problem, we
need a backtrace to see what is happening behind the scenes. To
find out how to generate a backtrace, please read
http://bugs.php.net/bugs-generating-backtrace.php for *NIX and
http://bugs.php.net/bugs-generating-backtrace-win32.php for Win32

Once you have generated a backtrace, please submit it to this bug
report and change the status back to "Open". Thank you for helping
us make PHP better.',
            'project' => 'php',
            'package_name' => '',
            'webonly' => '0',
        ], [
            'id' => '14',
            'name' => 'needscript',
            'status' => 'Feedback',
            'title' => 'Need Reproduce Script',
            'message' => 'Thank you for this bug report. To properly diagnose the problem, we
need a short but complete example script to be able to reproduce
this bug ourselves.

A proper reproducing script starts with <?php and ends with ?>,
is max. 10-20 lines long and does not require any external
resources such as databases, etc. If the script requires a
database to demonstrate the issue, please make sure it creates
all necessary tables, stored procedures etc.

Please avoid embedding huge scripts into the report.',
            'project' => 'php',
            'package_name' => '',
            'webonly' => '0',
        ], [
            'id' => '15',
            'name' => 'oldversion',
            'status' => 'Not a bug',
            'title' => 'Try newer version',
            'message' => 'Thank you for taking the time to report a problem with PHP.
Unfortunately you are not using a current version of PHP --
the problem might already be fixed. Please download a new
PHP version from http://www.php.net/downloads.php

If you are able to reproduce the bug with one of the latest
versions of PHP, please change the PHP version on this bug report
to the version you tested and change the status back to "Open".
Again, thank you for your continued support of PHP.',
            'project' => 'php',
            'package_name' => '',
            'webonly' => '0',
        ], [
            'id' => '16',
            'name' => 'support',
            'status' => 'Not a bug',
            'title' => 'Not developer issue',
            'message' => 'Sorry, but your problem does not imply a bug in PHP itself.  For a
list of more appropriate places to ask for help using PHP, please
visit http://www.php.net/support.php as this bug system is not the
appropriate forum for asking support questions.  Due to the volume
of reports we can not explain in detail here why your report is not
a bug.  The support channels will be able to provide an explanation
for you.

Thank you for your interest in PHP.',
            'project' => 'php',
            'package_name' => '',
            'webonly' => '0',
        ], [
            'id' => '17',
            'name' => 'nofeedback',
            'status' => 'No Feedback',
            'title' => 'No feedback',
            'message' => 'No feedback was provided. The bug is being suspended because
we assume that you are no longer experiencing the problem.
If this is not the case and you are able to provide the
information that was requested earlier, please do so and
change the status of the bug back to "Re-Opened". Thank you.',
            'project' => 'php',
            'package_name' => '',
            'webonly' => '1',
        ], [
            'id' => '18',
            'name' => 'notwrong',
            'status' => 'Not a bug',
            'title' => 'Expected behavior',
            'message' => 'Thank you for taking the time to write to us, but this is not
a bug. Please double-check the documentation available at
http://www.php.net/manual/ and the instructions on how to report
a bug at http://bugs.php.net/how-to-report.php',
            'project' => 'php',
            'package_name' => '',
            'webonly' => '0',
        ], [
            'id' => '19',
            'name' => 'notenoughinfo',
            'status' => 'Feedback',
            'title' => 'Not enough info',
            'message' => 'Not enough information was provided for us to be able
to handle this bug. Please re-read the instructions at
http://bugs.php.net/how-to-report.php

If you can provide more information, feel free to add it
to this bug and change the status back to "Open".

Thank you for your interest in PHP.',
            'project' => 'php',
            'package_name' => '',
            'webonly' => '0',
        ], [
            'id' => '20',
            'name' => 'submittedtwice',
            'status' => 'Not a bug',
            'title' => 'Submitted twice',
            'message' => 'Please do not submit the same bug more than once. An existing
bug report already describes this very problem. Even if you feel
that your issue is somewhat different, the resolution is likely
to be the same.

Thank you for your interest in PHP.',
            'project' => 'php',
            'package_name' => '',
            'webonly' => '0',
        ], [
            'id' => '21',
            'name' => 'globals',
            'status' => 'Not a bug',
            'title' => 'register_globals',
            'message' => 'In PHP 4.2.0, the \'register_globals\' setting default changed to
\'off\'. See http://www.php.net/release_4_2_0.php for more info.
We are sorry about the inconvenience, but this change was a necessary
part of our efforts to make PHP scripting more secure and portable.',
            'project' => 'php',
            'package_name' => '',
            'webonly' => '0',
        ], [
            'id' => '22',
            'name' => 'phptooold',
            'status' => 'Wont fix',
            'title' => 'PHP version support discontinued',
            'message' => 'The version of PHP you are reporting on is no longer supported.
				Please download a new PHP version from http://www.php.net/downloads.php

				If you are able to reproduce the bug with one of the latest
				versions of PHP, please change the PHP version on this bug report
				to the version you tested and change the status back to "Open".
				Again, thank you for your continued support of PHP.
',
            'project' => 'php',
            'package_name' => '',
            'webonly' => '0',
        ], [
            'id' => '23',
            'name' => 'dst',
            'status' => 'Not a bug',
            'title' => 'Daylight Savings',
            'message' => 'We are happy to tell you that you just discovered Daylight Savings
Time. For more information see:
http://webexhibits.org/daylightsaving/b.html
Instead of using mktime/date consider using gmmktime and gmdate which do
not suffer from DST.',
            'project' => 'php',
            'package_name' => '',
            'webonly' => '0',
        ], [
            'id' => '24',
            'name' => 'isapi',
            'status' => 'Not a bug',
            'title' => 'IIS Stability',
            'message' => 'We are aware of PHP\'s problems with stability under IIS and are working
to rectify the problem. Unfortunatly your bug report does not contain any
extra useful information and we already have enough bug reports open about
this issue. If you can provide more detailed information such as a
reproducable crash or a backtrace please do so and reopen this bug.
Otherwise please keep trying new releases as we are working to resolve
the problems on this platform

Thanks for your interest in PHP.',
            'project' => 'php',
            'package_name' => '',
            'webonly' => '0',
        ], [
            'id' => '25',
            'name' => 'gnused',
            'status' => 'Not a bug',
            'title' => 'Install GNU Sed',
            'message' => 'Due to a bug in the installed sed on your system the build
fails. Install GNU sed and it should be okay.

Thank you for your interest in PHP.',
            'project' => 'php',
            'package_name' => '',
            'webonly' => '0',
        ], [
            'id' => '26',
            'name' => 'float',
            'status' => 'Not a bug',
            'title' => 'Floating point limitations',
            'message' => 'Floating point values have a limited precision. Hence a value might
not have the same string representation after any processing. That also
includes writing a floating point value in your script and directly
printing it without any mathematical operations.

If you would like to know more about "floats" and what IEEE
754 is, read this:
http://www.floating-point-gui.de/

Thank you for your interest in PHP.',
            'project' => 'php',
            'package_name' => '',
            'webonly' => '0',
        ], [
            'id' => '27',
            'name' => 'nozend',
            'status' => 'Not a bug',
            'title' => 'No Zend Extensions',
            'message' => 'Do not file bugs when you have Zend extensions (zend_extension=)
loaded. Examples are Zend Optimizer, Zend Debugger, Turck MM Cache,
APC, Xdebug and ionCube loader.  These extensions often modify engine
behavior which is not related to PHP itself.',
            'project' => 'php',
            'package_name' => '',
            'webonly' => '0',
        ], [
            'id' => '28',
            'name' => 'mysqlcfg',
            'status' => 'Not a bug',
            'title' => 'MySQL Configuration Error',
            'message' => 'When using the mysqli extension together with the mysql extension
you have to use the same libraries and include files. mysqli
extension requires the location of mysql_config file, mysql
extension requires the path of your mysql installation.

If you installed MySQL 4.1 for example with prefix /usr/local/mysql-4.1
your configure settings should be
--with-mysql=/usr/local/mysql-4.1
--with-mysqli=/usr/local/mysql-4.1/bin/mysql_config',
            'project' => 'php',
            'package_name' => '',
            'webonly' => '0',
        ],
    ];

    /**
     * Class constructor
     */
    public function __construct(\PDO $dbh)
    {
    }

    /**
     * Fetch bug resolves.
     */
    public function findByProject(string $project = ''): array
    {
        $reasons = self::REASONS;
        if ($project !== '') {
            $reasons = array_filter(
                $reasons,
                function ($reason) use ($project) {
                    return ((($reason['project'] ?? '') === $project) ||
                            (($reason['project'] ?? '') === ''));
                });
        }

        $resolves = $variations = [];
        foreach ($reasons as $row) {
            if (!empty($row['package_name'])) {
                $variations[$row['name']][$row['package_name']] = $row['message'];
            } else {
                $resolves[$row['name']] = $row;
            }
        }

        return [$resolves, $variations];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function findAll(): array
    {
        return self::REASONS;
    }
}

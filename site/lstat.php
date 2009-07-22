<?php

/**
 * Provide the number of open bugs to the IRC PEARbot
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

$dbh->pushErrorHandling(PEAR_ERROR_RETURN);

$res =& $dbh->prepare("SELECT count(bugdb.id) AS count FROM bugdb
        LEFT JOIN packages ON packages.name = bugdb.package_name
        WHERE bugdb.status
         IN ('Open', 'Assigned', 'Analyzed', 'Critical', 'Verified')
         AND (bugdb.bug_type = 'Bug' OR bugdb.bug_type = 'Documentation Problem')
         AND packages.package_type = 'pear'")->execute()->fetchOne();

if (PEAR::isError($res)) {
    echo 0;
} else {
    echo $res;
}

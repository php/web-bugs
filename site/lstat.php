<?php

/**
 * Provide the number of open bugs to the IRC PEARbot
 */

$dbh->pushErrorHandling(PEAR_ERROR_RETURN);

$res = $dbh->prepare("SELECT count(bugdb.id) AS count FROM bugdb
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

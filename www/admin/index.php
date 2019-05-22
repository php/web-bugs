<?php

use App\Repository\DatabaseStatusRepository;
use App\Repository\PackageRepository;
use App\Repository\PhpInfoRepository;
use App\Repository\ReasonRepository;

// Application bootstrap
require_once '../../include/prepend.php';

// Authentication
require_once __DIR__.'/../../include/auth.php';

if (!$logged_in) {
    response_header("Bugs admin suite");
    response_footer("Please login");
    exit;
}

$action = 'list_lists';

if (isset($_GET['action'])) {
    $action = $_GET['action'];
}

switch ($action) {
    case 'phpinfo':
        echo $template->render('pages/admin/phpinfo.php', [
            'info' => $container->get(PhpInfoRepository::class)->getInfo(),
        ]);
        break;

    case 'list_responses':
        echo $template->render('pages/admin/quick_responses.php', [
            'responses' => $container->get(ReasonRepository::class)->findAll(),
        ]);
        break;

    case 'mysql':
        echo $template->render('pages/admin/database_status.php', [
            'mysqlVersion'         => $container->get(DatabaseStatusRepository::class)->getMysqlVersion(),
            'numberOfRowsPerTable' => $container->get(DatabaseStatusRepository::class)->getNumberOfRowsInTables(),
            'statusPerTable'       => $container->get(DatabaseStatusRepository::class)->getStatusOfTables(),
        ]);
        break;

    case 'list_lists':
    default:
        echo $template->render('pages/admin/mailing_lists.php', [
            'lists' => $container->get(PackageRepository::class)->findLists(),
        ]);
        break;
}

<?php

use App\Repository\BugRepository;

// Obtain common includes
require_once '../include/prepend.php';

// Authenticate
require_once __DIR__ . '/../include/auth.php';

$selectedBugType = $_GET['bug_type'] ?? 'All';
$sortBy          = $_GET['sort_by'] ?? 'Open';
$reverseSort     = (bool) ($_GET['rev'] ?? 1);

//$totalBugs  = 0;
$statisticsTally = [
    'All' => [
        'Total' => 0,
    ],
];

$statistics = [];

// massage the stats per package into a workable data structure for presentation on the page
foreach ($container->get(BugRepository::class)->findAllByBugType($selectedBugType) as $packageAndStatus) {
    if (!isset($statistics[$packageAndStatus['package_name']])) {
        $statistics[$packageAndStatus['package_name']] = [
            'Total' => 0,
        ];
    }

    $statistics[$packageAndStatus['package_name']][$packageAndStatus['status']] = $packageAndStatus['quant'];

    $statistics[$packageAndStatus['package_name']]['Total'] += $packageAndStatus['quant'];

    $statisticsTally['All']['Total'] += $packageAndStatus['quant'];

    if (!isset($statisticsTally['All'][$packageAndStatus['status']])) {
        $statisticsTally['All'][$packageAndStatus['status']] = 0;
    }

    $statisticsTally['All'][$packageAndStatus['status']] += $packageAndStatus['quant'];
}

uasort($statistics, function (array $a, array $b) use ($sortBy) {
    if (!isset($a[$sortBy])) {
        return -1;
    }

    if (!isset($b[$sortBy])) {
        return 1;
    }

    return $a[$sortBy] <=> $b[$sortBy];
});

if ($reverseSort) {
    arsort($statistics);
}

$statistics = array_merge($statisticsTally, $statistics);

$recentReports = [];

foreach ($container->get(BugRepository::class)->findPhpVersions($selectedBugType) as $recentBugs) {
    if (!isset($recentReports[$recentBugs['d']])) {
        $recentReports[$recentBugs['d']] = [];
    }

    $recentReports[$recentBugs['d']][] = [
        'version'  => $recentBugs['formatted_version'],
        'quantity' => $recentBugs['quant'],
    ];
}

echo $template->render('pages/statistics.php', [
    'selectedType'  => $selectedBugType,
    'sortBy'        => $sortBy,
    'reverseSort'   => $reverseSort,
    'statistics'    => $statistics,
    'recentReports' => $recentReports,
]);

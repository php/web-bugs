<?php
/*
    Hack note: This is for emailing the documentation team about commit/bugs emails, but could be expanded in the future.

    The API itself will probably be abandoned in the future, but here's the current URL:
    - https://bugs.php.net/api.php?type=docs&action=closed&interval=7
*/

use App\Repository\CommentRepository;

require_once '../include/prepend.php';

$type     = isset($_GET['type'])     ? $_GET['type']           : 'unknown';
$action   = isset($_GET['action'])   ? $_GET['action']         : 'unknown';
$interval = isset($_GET['interval']) ? (int) $_GET['interval'] : 7;

if ($type === 'docs' && $action === 'closed' && $interval) {
    $commentRepository = $container->get(CommentRepository::class);
    $rows = $commentRepository->findDocsComments($interval);

    //@todo add error handling
    if (!$rows) {
        echo 'The fail train has arrived.';
        exit;
    }

    echo serialize($rows);
}
// Allow fetching the max comment id
// http://127.0.0.1/api.php?type=max_comment_id
else if ($type === 'max_comment_id') {
    $commentRepository = $container->get(CommentRepository::class);
    $max_comment_id = $commentRepository->getMaxCommentId();
    $params = [
        'max_comment_id' => $max_comment_id
    ];
    echo json_encode($params);
}
// Allow fetching the a comments email and bug id
// http://127.0.0.1/api.php?type=comment_details&comment_id=2
// This is to allow faster spam monitoring.
else if ($type === 'comment_details') {
    $comment_id = intval($_GET['comment_id'] ?? 0);
    $commentRepository = $container->get(CommentRepository::class);
    $comment_details = $commentRepository->getCommentById($comment_id);

    echo json_encode($comment_details);
}

else {
    echo "Unknown action";
}

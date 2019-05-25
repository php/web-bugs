<?php

/**
 * Container initialization. Each service is created using Container::set()
 * method and a callable argument for convenience of future customizations or
 * adjustments beyond the scope of this container. See documentation for more
 * information.
 */

use App\Container\Container;

$container = new Container(include __DIR__.'/parameters.php');

$container->set(\PDO::class, function ($c) {
    return new \PDO(
        'mysql:host='.$c->get('db_host').';dbname='.$c->get('db_name').';charset=utf8',
        $c->get('db_user'),
        $c->get('db_password'),
        [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES   => false,
            \PDO::ATTR_STATEMENT_CLASS    => [App\Database\Statement::class],
        ]
    );
});

$container->set(App\Repository\BugRepository::class, function ($c) {
    return new App\Repository\BugRepository($c->get(\PDO::class));
});

$container->set(App\Repository\CommentRepository::class, function ($c) {
    return new App\Repository\CommentRepository($c->get(\PDO::class));
});

$container->set(App\Repository\DatabaseStatusRepository::class, function ($c) {
    return new App\Repository\DatabaseStatusRepository($c->get(\PDO::class));
});

$container->set(App\Repository\ObsoletePatchRepository::class, function ($c) {
    return new App\Repository\ObsoletePatchRepository($c->get(\PDO::class));
});

$container->set(App\Repository\PackageRepository::class, function ($c) {
    return new App\Repository\PackageRepository($c->get(\PDO::class));
});

$container->set(App\Repository\PatchRepository::class, function ($c) {
    return new App\Repository\PatchRepository($c->get(\PDO::class), $c->get('uploads_dir'));
});

$container->set(App\Repository\PhpInfoRepository::class, function ($c) {
    return new App\Repository\PhpInfoRepository();
});

$container->set(App\Repository\PullRequestRepository::class, function ($c) {
    return new App\Repository\PullRequestRepository($c->get(\PDO::class));
});

$container->set(App\Repository\ReasonRepository::class, function ($c) {
    return new App\Repository\ReasonRepository($c->get(\PDO::class));
});

$container->set(App\Repository\VoteRepository::class, function ($c) {
    return new App\Repository\VoteRepository($c->get(\PDO::class));
});

$container->set(App\Template\Engine::class, function ($c) {
    return new App\Template\Engine($c->get('templates_dir'));
});

$container->set(App\Utils\Captcha::class, function ($c) {
    return new App\Utils\Captcha();
});

$container->set(App\Utils\GitHub::class, function ($c) {
    return new App\Utils\GitHub($c->get(\PDO::class));
});

$container->set(App\Utils\PatchTracker::class, function ($c) {
    return new App\Utils\PatchTracker(
        $c->get(\PDO::class),
        $c->get(App\Utils\Uploader::class),
        $c->get('uploads_dir')
    );
});

$container->set(App\Utils\Uploader::class, function ($c) {
    return new App\Utils\Uploader();
});

return $container;

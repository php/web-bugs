# Dependency injection container

The PHP bug tracker application ships with a simplistic dependency injection
container which can create services and retrieves configuration values.

Services are one of the more frequently used objects everywhere across the
application. For example, service for database access, utility service for
uploading files, data generators, API clients, and similar.

## Dependency injection

Dependencies between classes are injected using either constructor, or via a
method call such as setter.

```php
class Repository
{
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getData(): array
    {
        return $this->pdo->query("SELECT * FROM table")->fetchAll();
    }
}

$pdo = new \PDO(
    'mysql:host=localhost;dbname=bugs;charset=utf8', 'nobody', 'password',
    [
        \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        \PDO::ATTR_EMULATE_PREPARES   => false,
    ]
);

$repository = new Repository($pdo);
$data = $repository->getData();
```

The `$pdo` object in the example is a dependency which is injected via the
constructor.

Dependency injection container further provides a more efficient creation of
such dependencies and services:

```php
$container = require_once __DIR__.'/../config/container.php';

$data = $container->get(Repository::class)->getData();
```

## Configuration

Configuration parameters include infrastructure configuration (database
credentials...) and application level configuration (directories locations...).

```php
// config/parameters.php

return [
    'parameter_key' => 'value',

    // ...
];
```

Which can be retrieved by the container:

```php
$value = $container->get('parameter_key');
```

## Container definitions

Each service class is manually defined:

```php
// config/container.php

// New container initialization with configuration parameters defined in a file.
$container = new Container(include __DIR__.'/parameters.php');

// Services are then defined using callables with a container argument $c.

// Service with constructor arguments
$container->set(App\Foo::class, function ($c) {
    return new App\Foo($c->get(App\Dependency::class));
});

// Service with a setter method
$container->set(App\Foo\Bar::class, function ($c) {
    $object = new App\Foo\Bar($c->get(App\Dependency::class));
    $object->setFoobar('argument');

    return $object;
});

// Dependencies can be service classes or configuration parameters
$container->set(App\Foo\Bar::class, function ($c) {
    return new App\Foo\Bar(
        // Configuration parameter
        $c->get('parameter_key'));

        // Calling method from another service
        $c->get(App\Dependency::class)->methodName();
    );
});

// Service with no dependencies
$container->set(App\Dependency::class, function ($c) {
    return new App\Dependency();
});

return $container;
```

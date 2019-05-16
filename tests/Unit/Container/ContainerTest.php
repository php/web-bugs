<?php

namespace App\Tests\Unit\Container;

use App\Container\Container;
use App\Container\Exception\ContainerException;
use App\Container\Exception\EntryNotFoundException;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    public function testContainer()
    {
        // Create container
        $container = new Container();

        // Service definitions
        $container->set(MockService::class, function ($c) {
            $service = new MockService($c->get(MockDependency::class), 'foo');
            $service->setProperty('group.param');

            return $service;
        });

        $container->set(MockDependency::class, function ($c) {
            return new MockDependency('group.param');
        });

        // Check retrieval of service
        $service = $container->get(MockService::class);
        $this->assertInstanceOf(MockService::class, $service);

        // Check retrieval of dependency
        $dependency = $container->get(MockDependency::class);
        $this->assertInstanceOf(MockDependency::class, $dependency);

        // Check that the dependency has been reused
        $this->assertSame($dependency, $service->getDependency());

        // Check the service calls have initialized
        $this->assertEquals('group.param', $service->getProperty());
    }

    public function testHas()
    {
        $container = new Container();

        $this->assertFalse($container->has(MockDependency::class));

        $container->set(MockDependency::class, function ($c) {
            return new MockDependency('group.param');
        });

        $this->assertFalse($container->has(MockService::class));
        $this->assertTrue($container->has(MockDependency::class));
    }

    public function testServiceNotFound()
    {
        $container = new Container();

        $this->expectException(EntryNotFoundException::class);

        $container->get('foo');
    }

    public function testBadServiceEntry()
    {
        $container = new Container();
        $container->set(\stdClass::class, '');

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('entry must be callable');

        $container->get(\stdClass::class);
    }

    public function testCircularReference()
    {
        $container = new Container();

        $container->set(MockService::class, function ($c) {
            return new MockService($c->get(MockService::class));
        });

        $container->set(MockService::class, function ($c) {
            return new MockService($c->get(MockService::class));
        });

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('circular reference');

        $container->get(MockService::class);
    }

    public function testParametersAndServices()
    {
        $container = new Container([
            'foo' => 'bar',
            'baz' => function ($c) {
                return $c->get('foo');
            },
        ]);

        $this->assertTrue($container->has('foo'));
        $this->assertTrue($container->has('baz'));
        $this->assertEquals('bar', $container->get('foo'));
        $this->assertEquals('bar', $container->get('baz'));
    }
}

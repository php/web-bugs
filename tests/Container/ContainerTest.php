<?php

namespace App\Tests\Container;

use App\Container\Container;
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

    /**
     * @expectedException App\Container\Exception\EntryNotFoundException
     */
    public function testServiceNotFound()
    {
        $container = new Container();
        $container->get('foo');
    }

    /**
     * @expectedException        App\Container\Exception\ContainerException
     * @expectedExceptionMessage entry must be callable
     */
    public function testBadServiceEntry()
    {
        $container = new Container();
        $container->set(\stdClass::class, '');
        $container->get(\stdClass::class);
    }

    /**
     * @expectedException        App\Container\Exception\ContainerException
     * @expectedExceptionMessage circular reference
     */
    public function testCircularReference()
    {
        $container = new Container();

        $container->set(MockService::class, function ($c) {
            return new MockService($c->get(MockService::class));
        });

        $container->set(MockService::class, function ($c) {
            return new MockService($c->get(MockService::class));
        });

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

<?php

namespace App\Tests\Unit\Container;

/**
 * Mock service class for testing container.
 */
class MockService
{
    private $dependency;
    private $property;

    public function __construct(MockDependency $dependency)
    {
        $this->dependency = $dependency;
    }

    public function getDependency()
    {
        return $this->dependency;
    }

    public function setProperty($value)
    {
        $this->property = $value;
    }

    public function getProperty()
    {
        return $this->property;
    }
}

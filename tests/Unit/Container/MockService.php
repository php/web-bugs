<?php declare(strict_types=1);

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

    public function getDependency(): MockDependency
    {
        return $this->dependency;
    }

    public function setProperty(string $value): void
    {
        $this->property = $value;
    }

    public function getProperty(): string
    {
        return $this->property;
    }
}

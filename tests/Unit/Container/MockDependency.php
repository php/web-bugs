<?php declare(strict_types=1);

namespace App\Tests\Unit\Container;

/**
 * Mock service dependency class for testing container.
 */
class MockDependency
{
    private $parameter;

    public function __construct(string $parameter)
    {
        $this->parameter = $parameter;
    }

    public function getParameter(): string
    {
        return $this->parameter;
    }
}

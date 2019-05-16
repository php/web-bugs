<?php

namespace App\Tests\Unit\Container;

/**
 * Mock service dependency class for testing container.
 */
class MockDependency
{
    private $parameter;

    public function __construct($parameter)
    {
        $this->parameter = $parameter;
    }

    public function getParameter()
    {
        return $this->parameter;
    }
}

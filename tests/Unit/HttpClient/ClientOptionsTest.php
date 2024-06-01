<?php declare(strict_types=1);

namespace App\Tests\Unit\HttpClient;

use App\HttpClient\ClientOptions;
use PHPUnit\Framework\TestCase;

class ClientOptionsTest extends TestCase
{
    public function testGetTimeoutUsesDefaultValue(): void
    {
        $this->assertSame(3, (new ClientOptions())->getTimeout());
    }

    public function testSetTimeoutSetsValue(): void
    {
        $this->assertSame(10, (new ClientOptions())->setTimeout(10)->getTimeout());
    }
}

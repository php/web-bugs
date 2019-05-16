<?php declare(strict_types=1);

namespace App\Tests\Unit\Utils\Versions;

use PHPUnit\Framework\TestCase;
use App\Utils\Versions\Client;

class ClientTest extends TestCase
{
    /** @var Client */
    private $client;

    public function setUp(): void
    {
        $this->client = new Client();

        $reflection = new \ReflectionClass($this->client);

        $devVersionsUrl = $reflection->getProperty('devVersionsUrl');
        $devVersionsUrl->setAccessible(true);
        $devVersionsUrl->setValue($this->client, TEST_MOCKS_DIRECTORY . '/responses/dev-body.txt');

        $stableVersionsUrl = $reflection->getProperty('stableVersionsUrl');
        $stableVersionsUrl->setAccessible(true);
        $stableVersionsUrl->setValue($this->client, TEST_MOCKS_DIRECTORY . '/responses/stable-body.txt');
    }

    public function testFetchDevVersions(): void
    {
        $this->assertIsArray($this->client->fetchDevVersions());
    }

    public function testFetchStableVersions(): void
    {
        $this->assertIsArray($this->client->fetchStableVersions());
    }
}

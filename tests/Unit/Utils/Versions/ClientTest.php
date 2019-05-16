<?php

namespace App\Tests\Unit\Utils\Versions;

use PHPUnit\Framework\TestCase;
use App\Utils\Versions\Client;

class ClientTest extends TestCase
{
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

    public function testFetchDevVersions()
    {
        $this->assertIsArray($this->client->fetchDevVersions());
    }

    public function testFetchStableVersions()
    {
        $this->assertIsArray($this->client->fetchStableVersions());
    }
}

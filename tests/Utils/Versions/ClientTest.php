<?php

namespace App\Tests\Utils\Versions;

use PHPUnit\Framework\TestCase;
use App\Utils\Versions\Client;

class ClientTest extends TestCase
{
    private $client;

    public function setUp()
    {
        $this->client = new Client();

        $reflection = new \ReflectionClass($this->client);

        $devVersionsUrl = $reflection->getProperty('devVersionsUrl');
        $devVersionsUrl->setAccessible(true);
        $devVersionsUrl->setValue($this->client, __DIR__.'/../../mock/responses/dev-body.txt');

        $stableVersionsUrl = $reflection->getProperty('stableVersionsUrl');
        $stableVersionsUrl->setAccessible(true);
        $stableVersionsUrl->setValue($this->client, __DIR__.'/../../mock/responses/stable-body.txt');
    }

    public function testFetchDevVersions()
    {
        $this->assertInternalType('array', $this->client->fetchDevVersions());
    }

    public function testFetchStableVersions()
    {
        $this->assertInternalType('array', $this->client->fetchStableVersions());
    }
}

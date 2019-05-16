<?php declare(strict_types=1);

namespace App\Tests\Unit\Utils\Versions;

use PHPUnit\Framework\TestCase;
use App\Utils\Versions\Generator;
use App\Utils\Versions\Client;
use App\Utils\Cache;

class GeneratorTest extends TestCase
{
    /** @var string */
    private $cacheDir = TEST_VAR_DIRECTORY . '/cache/test';

    /** @var Cache */
    private $cache;

    /** @var Client */
    private $client;

    /** @var Generator */
    private $generator;

    public function setUp(): void
    {
        $this->cache = new Cache($this->cacheDir);
        $this->cache->clear();

        // The results returned by the client depend on the remote URLs so we
        // mock the returned results.
        $this->client = $this->getMockBuilder(Client::class)
            ->setMethods(['fetchDevVersions', 'fetchStableVersions'])
            ->getMock();

        $this->client->expects($this->once())
            ->method('fetchDevVersions')
            ->will($this->returnValue(json_decode(file_get_contents(TEST_MOCKS_DIRECTORY . '/responses/dev-body.txt', true))));

        $this->client->expects($this->once())
            ->method('fetchStableVersions')
            ->will($this->returnValue(json_decode(file_get_contents(TEST_MOCKS_DIRECTORY . '/responses/stable-body.txt'), true)));

        $this->generator = $this->getMockBuilder(Generator::class)
            ->setConstructorArgs([$this->client, $this->cache])
            ->setMethods(['getAffixes'])
            ->getMock();

        // The extra versions are always date dependant so we mock it to include
        // static date done on the tests day.
        $date = '2018-12-26';
        $this->generator->expects($this->any())
            ->method('getAffixes')
            ->will($this->returnValue(['Git-' . $date . ' (snap)', 'Git-' . $date . ' (Git)',]));
    }

    public function tearDown(): void
    {
        $this->cache->clear();
        rmdir($this->cacheDir);
    }

    public function testVersions(): void
    {
        $versions = $this->generator->getVersions();

        $this->assertIsArray($versions);
        $this->assertGreaterThan(5, count($versions));

        $fixture = require TEST_FIXTURES_DIRECTORY . '/versions/versions.php';
        $cached = require $this->cacheDir . '/versions.php';

        $this->assertEquals($fixture[1], $cached[1]);
        $this->assertContains('Next Major Version', $versions);
        $this->assertContains('Irrelevant', $versions);
        $this->assertContains('7.2.14RC1', $versions);
    }
}

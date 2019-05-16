<?php declare(strict_types=1);

namespace App\Tests\Unit\Utils;

use App\Utils\Cache;
use PHPUnit\Framework\TestCase;

class CacheTest extends TestCase
{
    /** @var string */
    private $cacheDir = TEST_VAR_DIRECTORY . '/cache/test';

    /** @var Cache */
    private $cache;

    public function setUp(): void
    {
        $this->cache = new Cache($this->cacheDir);
        $this->cache->clear();
    }

    public function tearDown(): void
    {
        $this->cache->clear();
        rmdir($this->cacheDir);
    }

    public function testHas(): void
    {
        $this->assertFalse($this->cache->has('foo'));

        $this->cache->set('foo', [1, 2, 3]);
        $this->assertTrue($this->cache->has('foo'));
    }

    public function testDelete(): void
    {
        $this->cache->set('bar', [1, 2, 3]);
        $this->assertFileExists($this->cacheDir.'/bar.php');

        $this->cache->delete('bar');
        $this->assertFalse(file_exists($this->cacheDir.'/bar.php'));
    }
}

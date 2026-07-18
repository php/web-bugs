<?php declare(strict_types=1);

namespace App\Tests\Unit\HttpClient;

use App\HttpClient\Exception\InvalidHeaderKey;
use App\HttpClient\Exception\InvalidHeaderValue;
use App\HttpClient\Header;
use PHPUnit\Framework\TestCase;

class HeaderTest extends TestCase
{
    public function testCreateFromStringCorrectlyParsesAHeaderString(): void
    {
        $header = Header::createFromString('Foo: Bar');

        $this->assertSame('Foo', $header->getKey());
        $this->assertSame('Bar', $header->getValue());
    }

    public function testKeyMaintainsOriginalCasing(): void
    {
        $this->assertSame('fOo', (new Header('fOo', 'bar'))->getKey());
    }

    public function testKeyGetsNormalized(): void
    {
        $this->assertSame('foo-foo', (new Header('fOo-FOO', 'bar'))->getNormalizedKey());
    }

    public function testGetValue(): void
    {
        $this->assertSame('bar', (new Header('foo', 'bar'))->getValue());
    }

    public function testHeaderInjectionIsPreventedOnTheKey(): void
    {
        $this->expectException(InvalidHeaderKey::class);

        new Header("foo\r\nbar", 'bar');
    }

    public function testHeaderInjectionIsPreventedOnTheValue(): void
    {
        $this->expectException(InvalidHeaderValue::class);

        new Header('Foo', "foo\r\nbar");
    }

    public function testToString(): void
    {
        $this->assertSame("Foo: Bar\r\n", (new Header('Foo', 'Bar'))->toString());
    }
}

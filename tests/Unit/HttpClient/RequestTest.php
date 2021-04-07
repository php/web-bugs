<?php declare(strict_types=1);

namespace App\Tests\Unit\HttpClient;

use App\HttpClient\Header;
use App\HttpClient\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    public function testUriIsCorrectlySet(): void
    {
        $this->assertSame('https://example.com', (new Request('https://example.com'))->getUri());
    }

    public function testDefaultMethodIsSetWhenNoMethodIsSupplied(): void
    {
        $this->assertSame('GET', (new Request('https://example.com'))->getMethod());
    }

    public function testMethodIsSet(): void
    {
        $this->assertSame('POST', (new Request('https://example.com', 'POST'))->getMethod());
    }

    public function testDefaultUserAgentIsSet(): void
    {
        $header = (new Request('https://example.com'))->getHeaders()['user-agent'];

        $this->assertSame('PHP.net HTTP client', $header->getValue());
        $this->assertSame('User-Agent', $header->getKey());
    }

    public function testDefaultContentTypeIsSetForPostRequests(): void
    {
        $header = (new Request('https://example.com', 'POST'))->getHeaders()['content-type'];

        $this->assertSame('application/x-www-form-urlencoded', $header->getValue());
        $this->assertSame('Content-Type', $header->getKey());
    }

    public function testAddingSingleHeader(): void
    {
        $request = (new Request('https://example.com'))
            ->addHeaders(new Header('Foo', 'Bar'))
        ;

        $this->assertArrayHasKey('foo', $request->getHeaders());
    }

    public function testAddingMultipleHeader(): void
    {
        $request = (new Request('https://example.com'))
            ->addHeaders(
                new Header('Foo', 'Bar'),
                new Header('Baz', 'Qux')
            )
        ;

        $this->assertArrayHasKey('foo', $request->getHeaders());
        $this->assertArrayHasKey('baz', $request->getHeaders());
    }

    public function testAddHeadersOverwritesExistingHeader(): void
    {
        $request = (new Request('https://example.com'))
            ->addHeaders(new Header('User-Agent', 'User agent override'))
        ;

        $this->assertSame('User agent override', $request->getHeaders()['user-agent']->getValue());
    }

    public function testGetHeaders(): void
    {
        $request = (new Request('https://example.com'))
            ->addHeaders(
                new Header('Foo', 'Bar'),
                new Header('Baz', 'Qux')
            )
        ;

        $this->assertCount(3, $request->getHeaders());
        $this->assertInstanceOf(Header::class, $request->getHeaders()['user-agent']);
        $this->assertInstanceOf(Header::class, $request->getHeaders()['foo']);
    }

    public function testGetHeadersAsString(): void
    {
        $request = (new Request('https://example.com'))
            ->addHeaders(
                new Header('Foo', 'Bar'),
                new Header('Baz', 'Qux')
            )
        ;

        $expectedHeadersString = '';

        $expectedHeadersString .= "User-Agent: PHP.net HTTP client\r\n";
        $expectedHeadersString .= "Foo: Bar\r\n";
        $expectedHeadersString .= "Baz: Qux\r\n";

        $this->assertSame($expectedHeadersString, $request->getHeadersAsString());
    }

    public function testGetBodyReturnsEmptyStringWhenBodyIsNotSet(): void
    {
        $this->assertSame('', (new Request('https://example.com'))->getBody());
    }

    public function testGetBodyReturnsTheBodySet(): void
    {
        $this->assertSame(
            'The request body',
            (new Request('https://example.com'))->setBody('The request body')->getBody()
        );
    }
}

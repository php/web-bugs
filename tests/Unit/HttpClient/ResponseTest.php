<?php declare(strict_types=1);

namespace App\Tests\Unit\HttpClient;

use App\HttpClient\Header;
use App\HttpClient\Response;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    public function testGetBody(): void
    {
        $this->assertSame('The response body', (new Response('The response body', 'HTTP/1.1 200 OK'))->getBody());
    }

    public function testParsingOfProtocolVersion(): void
    {
        $this->assertSame('1.1', (new Response('', 'HTTP/1.1 200 OK'))->getProtocolVersion());
    }

    public function testParsingOfStatusCode(): void
    {
        $this->assertSame(200, (new Response('', 'HTTP/1.1 200 OK'))->getStatusCode());
    }

    public function testParsingOfMissingReasonPhrase(): void
    {
        $this->assertNull((new Response('', 'HTTP/1.1 200'))->getReasonPhrase());
    }

    public function testParsingOfReasonPhrase(): void
    {
        $this->assertSame('OK', (new Response('', 'HTTP/1.1 200 OK'))->getReasonPhrase());
    }

    public function testParsingOfHeaders(): void
    {
        $response = new Response('', 'HTTP/1.1 200 OK', 'Header1Key: Header1Value', 'Header2Key: Header2Value');

        $this->assertCount(2, $response->getHeaders());
        $this->assertInstanceOf(Header::class, $response->getHeaders()[0]);
        $this->assertInstanceOf(Header::class, $response->getHeaders()[1]);
        $this->assertSame('Header1Key', $response->getHeaders()[0]->getKey());
        $this->assertSame('Header1Value', $response->getHeaders()[0]->getValue());
        $this->assertSame('Header2Key', $response->getHeaders()[1]->getKey());
        $this->assertSame('Header2Value', $response->getHeaders()[1]->getValue());
    }
}

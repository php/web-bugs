<?php declare(strict_types=1);

namespace App\Tests\Unit\HttpClient;

use App\HttpClient\ClientOptions;
use App\HttpClient\Exception\ConnectionError;
use App\HttpClient\Header;
use App\HttpClient\NativeClient;
use App\HttpClient\Request;
use PHPUnit\Framework\TestCase;

class NativeClientTest extends TestCase
{
    public function testRequestUsesCorrectMethod(): void
    {
        $response = (new NativeClient())->request(new Request('https://httpbin.org/post', 'POST'));

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testRequestSendsHeaders(): void
    {
        $request = (new Request('https://httpbin.org/headers'))
            ->addHeaders(new Header('Foo', 'Bar'))
        ;

        $response = (new NativeClient())->request($request);

        $headersSent = json_decode($response->getBody(), true)['headers'];

        $this->assertSame(200, $response->getStatusCode());
        $this->assertArrayHasKey('Foo', $headersSent);
        $this->assertSame('Bar', $headersSent['Foo']);
        $this->assertArrayHasKey('User-Agent', $headersSent);
        $this->assertSame('PHP.net HTTP client', $headersSent['User-Agent']);
    }

    public function testRequestThrowsExceptionOnConnectionErrors(): void
    {
        $this->expectException(ConnectionError::class);

        (new NativeClient())->request(new Request('https://httpbin.org/delay/4'));
    }

    public function testRequestUsesDefaultClientOptions(): void
    {
        (new NativeClient())->request(new Request('https://httpbin.org/delay/2'));

        $this->expectException(ConnectionError::class);

        (new NativeClient())->request(new Request('https://httpbin.org/delay/4'));
    }

    public function testRequestUsesClientOptions(): void
    {
        $this->expectException(ConnectionError::class);

        $clientOptions = (new ClientOptions())->setTimeout(1);

        (new NativeClient($clientOptions))->request(new Request('https://httpbin.org/delay/2'));
    }

    public function testRequestUsesClientOptionsFromRequest(): void
    {
        $this->expectException(ConnectionError::class);

        $clientOptions = (new ClientOptions())->setTimeout(1);

        (new NativeClient())->request(new Request('https://httpbin.org/delay/2'), $clientOptions);
    }
}

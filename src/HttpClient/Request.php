<?php

namespace App\HttpClient;

class Request
{
    private const DEFAULT_USER_AGENT = 'PHP.net HTTP client';

    private const DEFAULT_POST_CONTENT_TYPE = 'application/x-www-form-urlencoded';

    private $uri;

    private $method;

    private $headers = [];

    private $body;

    public function __construct(string $uri, string $method = 'GET')
    {
        $this->uri    = $uri;
        $this->method = $method;

        $this->headers['user-agent'] = new Header('User-Agent', self::DEFAULT_USER_AGENT);

        if ($method === 'POST') {
            $this->headers['content-type'] = new Header('Content-Type', self::DEFAULT_POST_CONTENT_TYPE);
        }
    }

    public function addHeaders(Header ...$headers): self
    {
        foreach ($headers as $header) {
            $this->headers[$header->getNormalizedKey()] = $header;
        }

        return $this;
    }

    public function setBody(string $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @return Header[]
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeadersAsString(): string
    {
        return array_reduce($this->headers, function (string $headers, Header $header) {
            $headers .= sprintf("%s: %s\r\n", $header->getKey(), $header->getValue());

            return $headers;
        }, '');
    }

    public function getBody(): string
    {
        if ($this->body === null) {
            return '';
        }

        return $this->body;
    }
}

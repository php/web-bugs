<?php

namespace App\HttpClient;

class Response
{
    private const STATUS_LINE_PATTERN = '~^HTTP/(?P<protocolVersion>\d+\.\d+) (?P<statusCode>\d{3})(?: (?P<reasonPhrase>.+))?~';

    private $protocolVersion;

    private $statusCode;

    private $reasonPhrase;

    private $headers = [];

    private $body;

    public function __construct(string $body, string ...$headers)
    {
        $this->body = $body;

        $this->parseStatusLine(array_shift($headers));

        foreach ($headers as $header) {
            $this->headers[] = Header::createFromString($header);
        }
    }

    private function parseStatusLine(string $statusLine):void
    {
        preg_match(self::STATUS_LINE_PATTERN, $statusLine, $matches);

        $this->protocolVersion = $matches['protocolVersion'];
        $this->statusCode      = $matches['statusCode'];
        $this->reasonPhrase    = $matches['reasonPhrase'] ?? null;
    }

    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getReasonPhrase(): ?string
    {
        return $this->reasonPhrase;
    }

    /**
     * @return Header[]
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getBody(): string
    {
        return $this->body;
    }
}

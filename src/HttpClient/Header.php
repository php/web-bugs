<?php

namespace App\HttpClient;

use App\HttpClient\Exception\InvalidHeaderKey;
use App\HttpClient\Exception\InvalidHeaderValue;

class Header
{
    private $key;

    private $normalizedKey;

    private $value;

    /**
     * @throws InvalidHeaderKey   When a the key contains invalid characters
     * @throws InvalidHeaderValue When a the value contains invalid characters
     */
    public function __construct(string $key, string $value)
    {
        if (strpos($key, "\r\n") !== false || strpos($key, ':') !== false) {
            throw new InvalidHeaderKey();
        }

        if (strpos($value, "\r\n") !== false) {
            throw new InvalidHeaderValue();
        }

        $this->key           = $key;
        $this->normalizedKey = strtolower($key);
        $this->value         = $value;
    }

    public static function createFromString(string $header): self
    {
        $headerParts = explode(': ', $header);

        return new self($headerParts[0], $headerParts[1]);
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getNormalizedKey(): string
    {
        return $this->normalizedKey;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function toString(): string
    {
        return sprintf("%s: %s\r\n", $this->key, $this->value);
    }
}

<?php

namespace App\HttpClient;

use App\HttpClient\Exception\ConnectionError;

class NativeClient implements Client
{
    private $options;

    public function __construct(?ClientOptions $options = null)
    {
        $this->options = $options ?? new ClientOptions();
    }

    public function request(Request $request, ?ClientOptions $options = null): Response
    {
        $options = $options ?? $this->options;

        $streamContext = stream_context_create([
            'http' => [
                'method'        => $request->getMethod(),
                'header'        => $request->getHeadersAsString(),
                'content'       => $request->getBody(),
                'ignore_errors' => true,
                'timeout'       => $options->getTimeout(),
            ],
        ]);

        $responseBody = @file_get_contents($request->getUri(), false, $streamContext);

        if ($responseBody === false) {
            throw new ConnectionError(error_get_last()['message']);
        }

        return new Response($responseBody, ...$http_response_header);
    }
}

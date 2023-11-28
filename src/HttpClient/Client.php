<?php

namespace App\HttpClient;

use App\HttpClient\Exception\ConnectionError;

interface Client
{
    /**
     * @throws ConnectionError When a connection could not be established
     */
    public function request(Request $request, ?ClientOptions $options = null): Response;
}

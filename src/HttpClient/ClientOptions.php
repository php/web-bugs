<?php

namespace App\HttpClient;

class ClientOptions
{
    private $timeoutInSeconds = 3;

    public function setTimeout(int $timeoutInSeconds): self
    {
        $this->timeoutInSeconds = $timeoutInSeconds;

        return $this;
    }

    public function getTimeout(): int
    {
        return $this->timeoutInSeconds;
    }
}

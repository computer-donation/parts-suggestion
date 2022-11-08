<?php

namespace App\Graph;

use Laudis\Neo4j\ClientBuilder;
use Laudis\Neo4j\Contracts\ClientInterface;

class ClientFactory
{
    public function createClient(string $url): ClientInterface
    {
        return ClientBuilder::create()
            ->withDriver('default', $url)
            ->withDefaultDriver('default')
            ->build();
    }
}

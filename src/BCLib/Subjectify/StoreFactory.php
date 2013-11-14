<?php

namespace BCLib\Subjectify;

use Everyman\Neo4j\Client;

class StoreFactory
{
    public static function createNeo4JStore()
    {
        $client = new Client();
        $neo4jstore = new Neo4JStore($client, $client->makeLabel('topic'), $client->makeLabel('range'));
        return $neo4jstore;
    }
} 
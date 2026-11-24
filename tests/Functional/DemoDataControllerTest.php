<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DemoDataControllerTest extends WebTestCase
{
    public function testProductsFixtureIsServedAsJson(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/demo/products', [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $payload = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertNotEmpty($payload['data']);
        $this->assertSame('Bamboo Watch', $payload['data'][0]['name']);
    }

    public function testUnknownFixtureIsNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/demo/unknown', [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertResponseStatusCodeSame(404);
    }
}

<?php

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PersonneControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();
        $client->request('POST', '/');
        $this->assertResponseIsSuccessful();
    }

    public function testNew()
    {
        $client = static::createClient();
        $client->request('POST', '/new');
        $this->assertResponseIsSuccessful();
    }

    public function testEdit()
    {
        $client = static::createClient();
        $client->request('POST', '/edit/{id}');
        $this->assertResponseIsSuccessful();
    }

}

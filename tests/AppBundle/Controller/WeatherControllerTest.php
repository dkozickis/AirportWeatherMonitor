<?php
/**
 * Created by PhpStorm.
 * User: Denis
 * Date: 10/10/16
 * Time: 16:58
 */

namespace Tests\AppBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class WeatherControllerTest extends WebTestCase
{

    public function testJsonActual()
    {
        $client = static::createClient();

        $client->request('GET', '/weather/get/json/actual/1');
        $response = $client->getResponse();
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));

    }

    public function testJsonOld()
    {
        $client = static::createClient();

        $client->request('GET', '/weather/get/json/old');
        $response = $client->getResponse();
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));

    }

}

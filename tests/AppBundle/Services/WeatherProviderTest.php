<?php
/**
 * Created by PhpStorm.
 * User: Denis
 * Date: 10/10/16
 * Time: 15:16
 */

namespace Tests\AppBundle\Services;

use AppBundle\Services\WeatherProvider;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class WeatherProviderTest extends KernelTestCase
{

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        self::bootKernel();
    }


    public function testGet()
    {
        $weatherProvider = new WeatherProvider("http://server.wxmonitor.aero");
        $weather = $weatherProvider->getWeather(array('OMAA'));

        $this->assertArrayHasKey('OMAA', $weather);
    }

}

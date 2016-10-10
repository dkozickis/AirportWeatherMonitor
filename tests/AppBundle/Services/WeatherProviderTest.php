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
     * @var Logger
     */
    private $weatherLogger;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        self::bootKernel();

        $this->weatherLogger = static::$kernel->getContainer()
            ->get('monolog.logger.weather');
    }


    public function testGet()
    {
        $weatherProvider = new WeatherProvider($this->weatherLogger);
        $weather = $weatherProvider->getWeather(array('OMAA'),'metar');

        $this->assertArrayHasKey('OMAA', $weather);
    }

}

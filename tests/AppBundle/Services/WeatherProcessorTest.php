<?php

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class WeatherProcessorTest extends KernelTestCase
{

    /**
     * @var \Symfony\Bridge\Monolog\Logger
     */
    private $weatherLogger;

    /**
     * @var \AppBundle\Helpers\WeatherHelper
     */
    private $weatherHelper;

    /**
     * @var \AppBundle\Services\WeatherProvider
     */
    private $weatherProvider;


    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        self::bootKernel();

        $this->em = static::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->weatherLogger = static::$kernel->getContainer()
            ->get('monolog.logger.weather');

        $this->weatherHelper = static::$kernel->getContainer()
            ->get('weather_helper');

        $this->weatherProvider = static::$kernel->getContainer()
            ->get('weather_provider');
    }

    /**
     * @dataProvider dataProvider
     */
    public function testGetGeoJson(
        $name,
        $highWind,
        $midWind,
        $highCeil,
        $midCeil,
        $highVis,
        $midVis,
        $wxReturn,
        $colorizedMetar,
        $metarStatus,
        $colorizedTaf,
        $tafStatus
    ) {
        $airport = new \AppBundle\Entity\MonitoredAirport();
        $airportMasterData = new \AppBundle\Entity\AirportsMasterData();

        $airportMasterData->setAirportIcao($name)
            ->setLat(0)
            ->setLon(0);

        $airport->setAirportData($airportMasterData)
            ->setHighWarningWind($highWind)
            ->setMidWarningWind($midWind)
            ->setHighWarningCeiling($highCeil)
            ->setMidWarningCeiling($midCeil)
            ->setHighWarningVis($highVis)
            ->setMidWarningVis($midVis);

        $entityManagerMock = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->setMethods(array('persist', 'flush'))
            ->disableOriginalConstructor()
            ->getMock();

        $weatherProviderMock = $this->getMockBuilder('AppBundle\Services\WeatherProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $weatherProviderMock->method('getWeather')
            ->will($this->returnValueMap($wxReturn));

        $airports = array('HEGN' => $airport);

        $weatherProcessor = new \AppBundle\Services\WeatherProcessor(
            $entityManagerMock,
            $this->weatherLogger,
            $this->weatherHelper,
            $weatherProviderMock
        );

        /* @var \GeoJson\Feature\FeatureCollection */
        $return = $weatherProcessor->getGeoJsonWeather($airports);

        $this->assertInstanceOf('GeoJson\Feature\FeatureCollection', $return);
        $this->assertEquals($colorizedMetar, $return->getFeatures()[0]->getProperties()['colorizedMetar']);
        $this->assertEquals($metarStatus, $return->getFeatures()[0]->getProperties()['metarStatus']);
        $this->assertEquals($colorizedTaf, $return->getFeatures()[0]->getProperties()['colorizedTaf']);
        $this->assertEquals($tafStatus, $return->getFeatures()[0]->getProperties()['tafStatus']);
    }

    public static function dataProvider()
    {
        return array(
            array(
                'name' => 'HEGN',
                'highWind' => '30',
                'midWind' => '20',
                'highCeil' => '500',
                'midCeil' => '1000',
                'highVis' => '500',
                'midVis' => '1000',
                'wxReturn' => array(
                    array(
                        array('HEGN'),
                        'metar',
                        array(
                            'HEGN' => array(
                                'rawWeather' => 'HEGN 040500Z 35024KT 2000 SA NSC 25/18 Q1011 NOSIG',
                                'rawWeatherTime' => new \DateTime("now"),
                            )
                        )
                    ),
                    array(
                        array('HEGN'),
                        'taf',
                        array(
                            'HEGN' => array(
                                'rawWeather' => 'TAF KJFK 201410Z 2014/2212 03017G28KT P6SM '.
                                    'VCFGRA BKN020 OVC080 TX22/2014Z TN14/2204Z '.
                                    'BECMG 0810/0812 27030KT BKN006',
                                'rawWeatherTime' => new \DateTime("now"),
                            )
                        )
                    ),
                ),
                'colorizedMetar' => 'HEGN 040500Z 35024KT 2000 <span class="red">SA</span> NSC 25/18 Q1011 NOSIG',
                'metarStatus' => 3,
                'colorizedTaf' => 'TAF KJFK 201410Z 2014/2212 03017G28KT P6SM VCFGRA BKN020 OVC080 TX22/2014Z TN14/2204Z <br/>&nbsp;&nbsp;BECMG 0810/0812 27030KT <span class="yellow">BKN006</span>',
                'tafStatus' => 2,
            ),
        );
    }

}
<?php

namespace Tests\AppBundle\Services;

use AppBundle\Entity\AirportsMasterData;
use AppBundle\Entity\MonitoredAirport;
use AppBundle\Services\WeatherValidator\TafValidator;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use TafDecoder\TafDecoder;

class TafValidatorTest extends KernelTestCase
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

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

        $this->em = static::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->weatherLogger = static::$kernel->getContainer()
            ->get('monolog.logger.weather');
    }

    /**
     * @dataProvider airportMetarDataProvider
     */
    public function testValidate(
        $name,
        $highWind,
        $midWind,
        $highCeil,
        $midCeil,
        $highVis,
        $midVis,
        $raw,
        $status,
        $warnings
    ) {
        $tafDecoder = new TafDecoder();
        $airport = new MonitoredAirport();

        $airport->setRawTaf($raw);
        $decodedTaf = $tafDecoder->parse($airport->getRawTaf());
        $airport->setDecodedTaf($decodedTaf);

        $airportMasterData = new AirportsMasterData();
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

        $tafValidator = new TafValidator($this->weatherLogger);

        $validatedTaf = $tafValidator->validate($airport);
        $airport->setValidatedTaf($validatedTaf);

        $this->assertEquals($status, $validatedTaf->getWeatherStatus());

        $i = 0;
        foreach ($warnings as $warning) {
            $this->assertEquals($warning['chunk'], $validatedTaf->getWeatherWarnings()[$i]->getChunk());
            $this->assertEquals($warning['level'], $validatedTaf->getWeatherWarnings()[$i]->getWarningLevel());
            ++$i;
        }
    }

    /**
     * @return array
     */
    public static function airportMetarDataProvider()
    {
        return array(
            array(
                'name' => 'KJFK',
                'highWind' => '30',
                'midWind' => '20',
                'highCeil' => '500',
                'midCeil' => '1000',
                'highVis' => '500',
                'midVis' => '1000',
                'raw' => 'TAF KJFK 201410Z 2014/2212 03017G28KT P6SM VCFGRA BKN020 OVC080 TX22/2014Z TN14/2204Z '.
                    'BECMG 0810/0812 27031KT',
                'status' => '3',
                'warning' => array(
                    array(
                        'chunk' => '27031KT',
                        'level' => 3
                    )
                ),
            ),
            array(
                'name' => 'KJFK',
                'highWind' => '30',
                'midWind' => '20',
                'highCeil' => '500',
                'midCeil' => '1000',
                'highVis' => '500',
                'midVis' => '1000',
                'raw' => 'TAF KJFK 201410Z 2014/2212 /////KT P6SM VCFGRA BKN020 OVC080 TX22/2014Z TN14/2204Z '.
                    'BECMG 0810/0812 27031KT',
                'status' => '0',
                'warning' => array(

                ),
            ),
            array(
                'name' => 'KJFK',
                'highWind' => '30',
                'midWind' => '20',
                'highCeil' => '500',
                'midCeil' => '1000',
                'highVis' => '500',
                'midVis' => '1000',
                'raw' => 'TAF KJFK 201410Z 2014/2212 03017G28KT P6SM VCFGRA BKN020 OVC080 TX22/2014Z TN14/2204Z '.
                    'BECMG 0810/0812 27030KT BKN004',
                'status' => '3',
                'warning' => array(
                    array(
                        'chunk' => 'BKN004',
                        'level' => 3
                    )
                ),
            ),
            array(
                'name' => 'KJFK',
                'highWind' => '30',
                'midWind' => '20',
                'highCeil' => '500',
                'midCeil' => '1000',
                'highVis' => '500',
                'midVis' => '1000',
                'raw' => 'TAF KJFK 201410Z 2014/2212 03017G28KT P6SM VCFGRA BKN020 OVC080 TX22/2014Z TN14/2204Z '.
                    'BECMG 0810/0812 27030KT BKN006',
                'status' => '2',
                'warning' => array(
                    array(
                        'chunk' => 'BKN006',
                        'level' => 2
                    )
                ),
            ),
            array(
                'name' => 'KJFK',
                'highWind' => '30',
                'midWind' => '20',
                'highCeil' => '500',
                'midCeil' => '1000',
                'highVis' => '500',
                'midVis' => '1000',
                'raw' => 'TAF KJFK 201410Z 2014/2212 03017G28KT P6SM VCFGRA BKN020 OVC080 TX22/2014Z TN14/2204Z '.
                    'BECMG 0810/0812 27030KT BKN006 BECMG 0810/0812 27030KT BKN004',
                'status' => '3',
                'warning' => array(
                    array(
                        'chunk' => 'BKN006',
                        'level' => 2
                    ),
                    array(
                        'chunk' => 'BKN004',
                        'level' => 3
                    )
                ),
            ),
        );
    }
}
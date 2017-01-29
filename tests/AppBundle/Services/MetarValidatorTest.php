<?php
namespace Tests\AppBundle\Services;

use AppBundle\Entity\AirportsMasterData;
use AppBundle\Entity\MonitoredAirport;
use AppBundle\Services\WeatherValidator\MetarValidator;
use MetarDecoder\MetarDecoder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bridge\Monolog\Logger;

class MetarValidatorTest extends KernelTestCase
{
    public $airportOne;
    public $airportTwo;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var Logger
     */
    private $weatherLogger;

    /**
     * @return array
     */
    public static function airportMetarDataProvider()
    {
        return array(
            array(
                'name' => 'BIKF',
                'alternate' => 0,
                'highWind' => '30',
                'midWind' => '20',
                'highCeil' => '500',
                'midCeil' => '1000',
                'highVis' => '500',
                'midVis' => '1000',
                'highWindAlt' => '30',
                'midWindAlt' => '20',
                'highCeilAlt' => '500',
                'midCeilAlt' => '1000',
                'highVisAlt' => '500',
                'midVisAlt' => '1000',
                'raw' => 'BIKF 281000Z 10023KT 9999 FEW035 SCT042 BKN120 02/M01 Q1011',
                'status' => '2',
                'warning' => array(
                    array(
                        'chunk' => '10023KT',
                        'level' => 2,
                    ),
                ),
            ),
            array(
                'name' => 'BIKF',
                'alternate' => 0,
                'highWind' => '30',
                'midWind' => '20',
                'highCeil' => '500',
                'midCeil' => '1000',
                'highVis' => '500',
                'midVis' => '6000',
                'highWindAlt' => '30',
                'midWindAlt' => '20',
                'highCeilAlt' => '500',
                'midCeilAlt' => '1000',
                'highVisAlt' => '500',
                'midVisAlt' => '1000',
                'raw' => 'BIKF 281000Z 10023KT 5000 BKN120 02/M01 Q1011',
                'status' => '2',
                'warning' => array(
                    array(
                        'chunk' => '10023KT',
                        'level' => 2,
                    ),
                    array(
                        'chunk' => '5000',
                        'level' => 2,
                    ),
                ),
            ),
            array(
                'name' => 'BIKF',
                'alternate' => 0,
                'highWind' => '30',
                'midWind' => '20',
                'highCeil' => '500',
                'midCeil' => '1000',
                'highVis' => '6000',
                'midVis' => '7000',
                'highWindAlt' => '30',
                'midWindAlt' => '20',
                'highCeilAlt' => '500',
                'midCeilAlt' => '1000',
                'highVisAlt' => '500',
                'midVisAlt' => '1000',
                'raw' => 'BIKF 281000Z 10023KT 5000 BKN120 02/M01 Q1011',
                'status' => '3',
                'warning' => array(
                    array(
                        'chunk' => '10023KT',
                        'level' => 2,
                    ),
                    array(
                        'chunk' => '5000',
                        'level' => 3,
                    ),
                ),
            ),
            array(
                'name' => 'BIKF',
                'alternate' => 0,
                'highWind' => '30',
                'midWind' => '20',
                'highCeil' => '500',
                'midCeil' => '1100',
                'highVis' => '500',
                'midVis' => '6000',
                'highWindAlt' => '30',
                'midWindAlt' => '20',
                'highCeilAlt' => '500',
                'midCeilAlt' => '1000',
                'highVisAlt' => '500',
                'midVisAlt' => '1000',
                'raw' => 'BIKF 281000Z 10023KT 5000 BKN010 02/M01 Q1011',
                'status' => '2',
                'warning' => array(
                    array(
                        'chunk' => '10023KT',
                        'level' => 2,
                    ),
                    array(
                        'chunk' => 'BKN010',
                        'level' => 2,
                    ),
                ),
            ),
            array(
                'name' => 'BIKF',
                'alternate' => 0,
                'highWind' => '30',
                'midWind' => '20',
                'highCeil' => '1100',
                'midCeil' => '1200',
                'highVis' => '500',
                'midVis' => '6000',
                'highWindAlt' => '30',
                'midWindAlt' => '20',
                'highCeilAlt' => '500',
                'midCeilAlt' => '1000',
                'highVisAlt' => '500',
                'midVisAlt' => '1000',
                'raw' => 'BIKF 281000Z 10023KT 5000 BKN010 02/M01 Q1011',
                'status' => '3',
                'warning' => array(
                    array(
                        'chunk' => '10023KT',
                        'level' => 2,
                    ),
                    array(
                        'chunk' => 'BKN010',
                        'level' => 3,
                    ),
                ),
            ),
            array(
                'name' => 'BIKF',
                'alternate' => 0,
                'highWind' => '29',
                'midWind' => '40',
                'highCeil' => '500',
                'midCeil' => '1100',
                'highVis' => '500',
                'midVis' => '6000',
                'highWindAlt' => '30',
                'midWindAlt' => '20',
                'highCeilAlt' => '500',
                'midCeilAlt' => '1000',
                'highVisAlt' => '500',
                'midVisAlt' => '1000',
                'raw' => 'BIKF 281000Z 10030KT 5000 BKN020 02/M01 Q1011',
                'status' => '3',
                'warning' => array(
                    array(
                        'chunk' => '10030KT',
                        'level' => 3,
                    ),
                ),
            ),
            array(
                'name' => 'BIKF',
                'alternate' => 0,
                'highWind' => '30',
                'midWind' => '20',
                'highCeil' => '500',
                'midCeil' => '600',
                'highVis' => '500',
                'midVis' => '1000',
                'highWindAlt' => '30',
                'midWindAlt' => '20',
                'highCeilAlt' => '500',
                'midCeilAlt' => '1000',
                'highVisAlt' => '500',
                'midVisAlt' => '1000',
                'raw' => 'BIKF 281000Z 10031KT 9999 BKN005 02/M01 Q1011',
                'status' => '3',
                'warning' => array(
                    array(
                        'chunk' => '10031KT',
                        'level' => 3,
                    ),
                    array(
                        'chunk' => 'BKN005',
                        'level' => 2,
                    ),
                ),
            ),
            array(
                'name' => 'BIKF',
                'alternate' => 0,
                'highWind' => '30',
                'midWind' => '20',
                'highCeil' => '500',
                'midCeil' => '600',
                'highVis' => '500',
                'midVis' => '1000',
                'highWindAlt' => '30',
                'midWindAlt' => '20',
                'highCeilAlt' => '500',
                'midCeilAlt' => '1000',
                'highVisAlt' => '500',
                'midVisAlt' => '1000',
                'raw' => '',
                'status' => '0',
                'warning' => array(),
            ),
            array(
                'name' => 'BIKF',
                'alternate' => 0,
                'highWind' => '30',
                'midWind' => '20',
                'highCeil' => '500',
                'midCeil' => '600',
                'highVis' => '500',
                'midVis' => '1000',
                'highWindAlt' => '30',
                'midWindAlt' => '20',
                'highCeilAlt' => '500',
                'midCeilAlt' => '1000',
                'highVisAlt' => '500',
                'midVisAlt' => '1000',
                'raw' => 'BIKF 281000Z /////KT 9999 BKN005 02/M01 Q1011',
                'status' => '0',
                'warning' => array(),
            ),
            array(
                'name' => 'BIKF',
                'alternate' => 0,
                'highWind' => '30',
                'midWind' => '20',
                'highCeil' => '500',
                'midCeil' => '1000',
                'highVis' => '500',
                'midVis' => '600',
                'highWindAlt' => '30',
                'midWindAlt' => '20',
                'highCeilAlt' => '500',
                'midCeilAlt' => '1000',
                'highVisAlt' => '500',
                'midVisAlt' => '1000',
                'raw' => 'BIKF 281000Z 10023KT 5000 TSRA BKN120 02/M01 Q1011',
                'status' => '2',
                'warning' => array(
                    array(
                        'chunk' => 'TSRA',
                        'level' => 2,
                    ),
                ),
            ),
            array(
                'name' => 'BIKF',
                'alternate' => 0,
                'highWind' => '30',
                'midWind' => '20',
                'highCeil' => '500',
                'midCeil' => '1000',
                'highVis' => '500',
                'midVis' => '600',
                'highWindAlt' => '30',
                'midWindAlt' => '20',
                'highCeilAlt' => '500',
                'midCeilAlt' => '1000',
                'highVisAlt' => '500',
                'midVisAlt' => '1000',
                'raw' => 'BIKF 281000Z 10023KT 5000 FZDZ BKN120 02/M01 Q1011',
                'status' => '3',
                'warning' => array(
                    array(
                        'chunk' => 'FZDZ',
                        'level' => 3,
                    ),
                ),
            ),
            array(
                'name' => 'BIKF',
                'alternate' => 0,
                'highWind' => '30',
                'midWind' => '20',
                'highCeil' => '500',
                'midCeil' => '600',
                'highVis' => '500',
                'midVis' => '1000',
                'highWindAlt' => '30',
                'midWindAlt' => '20',
                'highCeilAlt' => '500',
                'midCeilAlt' => '1000',
                'highVisAlt' => '500',
                'midVisAlt' => '1000',
                'raw' => 'BIKF 281000Z 10031G40KT 9999 BKN005 02/M01 Q1011',
                'status' => '3',
                'warning' => array(
                    array(
                        'chunk' => '10031G40KT',
                        'level' => 3,
                    ),
                    array(
                        'chunk' => 'BKN005',
                        'level' => 2,
                    ),
                ),
            ),
            array(
                'name' => 'BIKF',
                'alternate' => 1,
                'highWind' => '30',
                'midWind' => '20',
                'highCeil' => '500',
                'midCeil' => '600',
                'highVis' => '500',
                'midVis' => '1000',
                'highWindAlt' => null,
                'midWindAlt' => null,
                'highCeilAlt' => null,
                'midCeilAlt' => null,
                'highVisAlt' => null,
                'midVisAlt' => null,
                'raw' => 'BIKF 281000Z 10031G40KT 9999 BKN005 02/M01 Q1011',
                'status' => '3',
                'warning' => array(
                    array(
                        'chunk' => '10031G40KT',
                        'level' => 3,
                    ),
                    array(
                        'chunk' => 'BKN005',
                        'level' => 2,
                    ),
                ),
            ),
            array(
                'name' => 'BIKF',
                'alternate' => 1,
                'highWind' => '30',
                'midWind' => '20',
                'highCeil' => '500',
                'midCeil' => '600',
                'highVis' => '500',
                'midVis' => '1000',
                'highWindAlt' => '45',
                'midWindAlt' => null,
                'highCeilAlt' => null,
                'midCeilAlt' => null,
                'highVisAlt' => null,
                'midVisAlt' => null,
                'raw' => 'BIKF 281000Z 10031G40KT 9999 BKN005 02/M01 Q1011',
                'status' => '2',
                'warning' => array(
                    array(
                        'chunk' => '10031G40KT',
                        'level' => 2,
                    ),
                    array(
                        'chunk' => 'BKN005',
                        'level' => 2,
                    ),
                ),
            ),
            array(
                'name' => 'BIKF',
                'alternate' => 1,
                'highWind' => '30',
                'midWind' => '20',
                'highCeil' => '500',
                'midCeil' => '600',
                'highVis' => '500',
                'midVis' => '1000',
                'highWindAlt' => '45',
                'midWindAlt' => null,
                'highCeilAlt' => null,
                'midCeilAlt' => null,
                'highVisAlt' => '500',
                'midVisAlt' => '6000',
                'raw' => 'BIKF 281000Z 10031G40KT 5000 BKN005 02/M01 Q1011',
                'status' => '2',
                'warning' => array(
                    array(
                        'chunk' => '10031G40KT',
                        'level' => 2,
                    ),
                    array(
                        'chunk' => 'BKN005',
                        'level' => 2,
                    ),
                    array(
                        'chunk' => '5000',
                        'level' => 2,
                    ),
                ),
            ),
        );
    }

    /**
     * @dataProvider airportMetarDataProvider
     */
    public function testValidate(
        $name,
        $alternate,
        $highWind,
        $midWind,
        $highCeil,
        $midCeil,
        $highVis,
        $midVis,
        $highWindAlt,
        $midWindAlt,
        $highCeilAlt,
        $midCeilAlt,
        $highVisAlt,
        $midVisAlt,
        $raw,
        $status,
        $warnings
    ) {
        $metarDecoder = new MetarDecoder();
        $airport = new MonitoredAirport();

        $phenomenons = array(
            'mid' => array('TSRA'),
            'high' => array('FZDZ')
        );

        $airport->setRawMetar($raw);
        $decodedMetar = $metarDecoder->parse($airport->getRawMetar());
        $airport->setDecodedMetar($decodedMetar);

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
            ->setMidWarningVis($midVis)
            ->setHighWarningWindAlt($highWindAlt)
            ->setMidWarningWindAlt($midWindAlt)
            ->setHighWarningCeilingAlt($highCeilAlt)
            ->setMidWarningCeilingAlt($midCeilAlt)
            ->setHighWarningVisAlt($highVisAlt)
            ->setMidWarningVisAlt($midVisAlt);

        $metarValidator = new MetarValidator($this->weatherLogger, $phenomenons);

        $validatedMetar = $metarValidator->validate($airport, $alternate);
        $airport->setValidatedMetar($validatedMetar);

        $this->assertEquals($status, $validatedMetar->getWeatherStatus());

        $i = 0;
        foreach ($warnings as $warning) {
            $this->assertEquals($warning['chunk'], $validatedMetar->getWeatherWarnings()[$i]->getChunk());
            $this->assertEquals($warning['level'], $validatedMetar->getWeatherWarnings()[$i]->getWarningLevel());
            ++$i;
        }
    }

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
}

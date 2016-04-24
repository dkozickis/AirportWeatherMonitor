<?php
/**
 * Created by PhpStorm.
 * User: Denis
 * Date: 24/04/16
 * Time: 13:59
 */

namespace AppBundle\Tests\Services;

use AppBundle\Entity\AirportsMasterData;
use AppBundle\Entity\MonitoredAirports;
use AppBundle\Services\MetarValidator;
use AppBundle\Services\WeatherForView;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bridge\Monolog\Logger;

class WeatherForViewTest extends KernelTestCase
{

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

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

        $this->entityManager = static::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->weatherLogger = static::$kernel->getContainer()
            ->get('monolog.logger.weather');
    }

    /**
     * @dataProvider airportMetarDataProvider
     */
    public function placeholderTestGetJsonWeather(
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
        $weatherForView = new WeatherForView($this->entityManager, $this->weatherLogger);

        $airportMasterData = new AirportsMasterData();
        $airportMasterData->setAirportIcao($name)
            ->setLat(0)
            ->setLon(0);

        $this->entityManager->persist($airportMasterData);

        $airports = [];
        $airports[$name] = new MonitoredAirports();
        $airports[$name]->setAirportData($airportMasterData)
            ->setHighWarningWind($highWind)
            ->setMidWarningWind($midWind)
            ->setHighWarningCeiling($highCeil)
            ->setMidWarningCeiling($midCeil)
            ->setHighWarningVis($highVis)
            ->setMidWarningVis($midVis);

        $jsonWeather = $weatherForView->getGeoJsonWeather($airports);

        $this->assertEquals("FeatureCollection", $jsonWeather->getType());

        $this->entityManager->remove($airports['BIKF']);
        $this->entityManager->remove($airportMasterData);
        $this->entityManager->flush();

    }

    /**
     * @return array
     */
    public static function airportMetarDataProvider()
    {
        return array(
            array(
                'name' => 'YSSY',
                'highWind' => '30',
                'midWind' => '20',
                'highCeil' => '500',
                'midCeil' => '1000',
                'highVis' => '500',
                'midVis' => '1000',
                'raw' => 'BIKF 281000Z 10023KT 9999 FEW035 SCT042 BKN120 02/M01 Q1011',
                'status' => '1',
                'warning' => array(),
            ),
           /* array(
                'name' => 'BIKF',
                'highWind' => '30',
                'midWind' => '20',
                'highCeil' => '500',
                'midCeil' => '1000',
                'highVis' => '500',
                'midVis' => '6000',
                'raw' => 'BIKF 281000Z 10023KT 5000 BKN120 02/M01 Q1011',
                'status' => '2',
                'warning' => array(
                    array(
                        'chunk' => '5000',
                        'level' => 2
                    )
                ),
            ),
            array(
                'name' => 'BIKF',
                'highWind' => '30',
                'midWind' => '20',
                'highCeil' => '500',
                'midCeil' => '1000',
                'highVis' => '6000',
                'midVis' => '7000',
                'raw' => 'BIKF 281000Z 10023KT 5000 BKN120 02/M01 Q1011',
                'status' => '3',
                'warning' => array(
                    array(
                        'chunk' => '5000',
                        'level' => 3
                    )
                ),
            ),
            array(
                'name' => 'BIKF',
                'highWind' => '30',
                'midWind' => '20',
                'highCeil' => '500',
                'midCeil' => '1100',
                'highVis' => '500',
                'midVis' => '6000',
                'raw' => 'BIKF 281000Z 10023KT 5000 BKN010 02/M01 Q1011',
                'status' => '2',
                'warning' => array(
                    array(
                        'chunk' => 'BKN010',
                        'level' => 2
                    )
                ),
            ),
            array(
                'name' => 'BIKF',
                'highWind' => '30',
                'midWind' => '20',
                'highCeil' => '1100',
                'midCeil' => '1200',
                'highVis' => '500',
                'midVis' => '6000',
                'raw' => 'BIKF 281000Z 10023KT 5000 BKN010 02/M01 Q1011',
                'status' => '3',
                'warning' => array(
                    array(
                        'chunk' => 'BKN010',
                        'level' => 3
                    )
                ),
            ),
            array(
                'name' => 'BIKF',
                'highWind' => '29',
                'midWind' => '40',
                'highCeil' => '500',
                'midCeil' => '1100',
                'highVis' => '500',
                'midVis' => '6000',
                'raw' => 'BIKF 281000Z 10030KT 5000 BKN020 02/M01 Q1011',
                'status' => '3',
                'warning' => array(
                    array(
                        'chunk' => '10030KT',
                        'level' => 3
                    )
                ),
            ),
            array(
                'name' => 'BIKF',
                'highWind' => '30',
                'midWind' => '20',
                'highCeil' => '500',
                'midCeil' => '600',
                'highVis' => '500',
                'midVis' => '1000',
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
                'highWind' => '30',
                'midWind' => '20',
                'highCeil' => '500',
                'midCeil' => '600',
                'highVis' => '500',
                'midVis' => '1000',
                'raw' => '',
                'status' => '0',
                'warning' => array(),
            ),
            array(
                'name' => 'BIKF',
                'highWind' => '30',
                'midWind' => '20',
                'highCeil' => '500',
                'midCeil' => '600',
                'highVis' => '500',
                'midVis' => '1000',
                'raw' => 'BIKF 281000Z /////KT 9999 BKN005 02/M01 Q1011',
                'status' => '0',
                'warning' => array(),
            ),*/
        );
    }

}
<?php
/**
 * Created by PhpStorm.
 * User: Denis
 * Date: 28/02/16
 * Time: 14:31.
 */
namespace AppBundle\Tests\Services;

use AppBundle\Entity\MonitoredAirports;
use AppBundle\Services\MetarValidator;
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
        $metarDecoder = new MetarDecoder();
        $airport = new MonitoredAirports();

        $airport->setRawMetar($raw);
        $decodedMetar = $metarDecoder->parse($airport->getRawMetar());
        $airport->setDecodedMetar($decodedMetar);

        $airportMasterData = $this->em->getRepository('AppBundle:AirportsMasterData')->findOneBy(array(
            'airportIcao' => $name,
        ));

        $airport->setAirportData($airportMasterData)
            ->setHighWarningWind($highWind)
            ->setMidWarningWind($midWind)
            ->setHighWarningCeiling($highCeil)
            ->setMidWarningCeiling($midCeil)
            ->setHighWarningVis($highVis)
            ->setMidWarningVis($midVis);

        $metarValidator = new MetarValidator($this->weatherLogger);

        $validatedMetar = $metarValidator->validate($airport);
        $airport->setValidatedMetar($validatedMetar);

        $this->assertEquals($status, $validatedMetar->getWeatherStatus());

        $i = 0;
        dump($validatedMetar->getWeatherWarnings());
        foreach ($warnings as $warning) {
            $this->assertEquals($warning['chunk'], $validatedMetar->getWeatherWarnings()[$i]->getChunk());
            $this->assertEquals($warning['level'], $validatedMetar->getWeatherWarnings()[$i]->getWarningLevel());
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
                'name' => 'BIKF',
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
        );
    }
}

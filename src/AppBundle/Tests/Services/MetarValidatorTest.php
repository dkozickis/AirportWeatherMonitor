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

class MetarValidatorTest extends \PHPUnit_Framework_TestCase
{
    public $airportOne;
    public $airportTwo;

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
        $md = new MetarDecoder();
        $airport = new MonitoredAirports();

        $airport->setAirportIcao($name)
            ->setHighWarningWind($highWind)
            ->setMidWarningWind($midWind)
            ->setHighWarningCeiling($highCeil)
            ->setMidWarningCeiling($midCeil)
            ->setHighWarningVis($highVis)
            ->setMidWarningVis($midVis);

        $mv = new MetarValidator($airport, $md->parse($raw));
        $validated = $mv->validate();

        $this->assertEquals($status, $validated->getWeatherStatus());
        $i = 0;
        foreach ($warnings as $warning) {
            $this->assertEquals($warning['chunk'], $validated->getWeatherWarnings()[$i]->getChunk());
            $this->assertEquals($warning['level'], $validated->getWeatherWarnings()[$i]->getWarningLevel());
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
                'highWind' => '30',
                'midWind' => '20',
                'highCeil' => '500',
                'midCeil' => '1000',
                'highVis' => '500',
                'midVis' => '1000',
                'raw' => 'BIKF 281000Z 10031KT 9999 FEW035 SCT042 BKN120 02/M01 Q1011',
                'status' => '3',
                'warning' => array(
                    array(
                        'chunk' => '10031KT',
                        'level' => 3,
                    ),
                ),
            ),
        );
    }
}

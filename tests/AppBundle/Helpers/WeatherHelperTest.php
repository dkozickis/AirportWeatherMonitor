<?php

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class WeatherHelperTest extends KernelTestCase
{
    /**
     * @var \AppBundle\Helpers\WeatherHelper
     */
    public $weatherHelper;

    public function setUp()
    {
        $this->weatherHelper = new \AppBundle\Helpers\WeatherHelper();
    }

    /**
     * @dataProvider dateProvider
     */
    public function testSeason(DateTime $dateTime, $season, $dateTimeMinusThirty)
    {
        $retrievedSeason = $this->weatherHelper->getDateSeason($dateTime);
        $this->assertEquals($season, $retrievedSeason);
    }

    /**
     * @dataProvider dateProvider
     */
    public function testReferenceTime(DateTime $dateTime, $season, DateTime $dateTimeMinusThirty)
    {
        $retrievedReferenceTime = $this->weatherHelper->getReferenceTime(30, $dateTime);

        $this->assertEquals($dateTimeMinusThirty, $retrievedReferenceTime);
    }

    public function testEmptySeason()
    {

        $retrievedSeason = $this->weatherHelper->getDateSeason();

        $this->assertInternalType("int", $retrievedSeason);
    }

    public function testAirportModelToArray()
    {
        $masterAirport = new \AppBundle\Entity\AirportsMasterData();
        $masterAirport->setAirportIcao('OMAA');
        $monitoredAirport = new \AppBundle\Entity\MonitoredAirport();
        $monitoredAirport->setAirportData($masterAirport);

        $testAirport = $this->weatherHelper->airportsObjectToArray(array($monitoredAirport));

        $this->assertArrayHasKey('OMAA', $testAirport);
    }

    public static function dateProvider()
    {
        return array(
            array(
                'dateTime' => new \DateTime('2016-04-01 00:00:00.000', new \DateTimeZone('Europe/Berlin')),
                'season' => 1,
                'dateTimeMinusThirty' => new \DateTime('2016-03-31 23:30:00.000', new \DateTimeZone('Europe/Berlin')),
            ),
            array(
                'dateTime' => new \DateTime('2016-03-01 00:00:00.000', new \DateTimeZone('Europe/Berlin')),
                'season' => 0,
                'dateTimeMinusThirty' => new \DateTime('2016-02-29 23:30:00.000', new \DateTimeZone('Europe/Berlin')),
            ),
        );
    }
}

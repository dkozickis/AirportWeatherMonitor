<?php

namespace Test\AppBundle\Repository;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MonitoredAirportRepositoryTest extends WebTestCase
{

    /**
     * @var \AppBundle\Helpers\WeatherHelper
     */
    public $weatherHelper;
    private $em;

    public static function dataProvider()
    {
        return array(
            array('season' => 1, 'dql' => "SELECT a FROM AppBundle\Entity\MonitoredAirport a WHERE (a.rawMetarDateTime < :datetime OR a.rawMetarDateTime IS NULL) AND a.activeSummer = 1 AND a.alternateSummer = 0" ),
            array('season' => 0, 'dql' => "SELECT a FROM AppBundle\Entity\MonitoredAirport a WHERE (a.rawMetarDateTime < :datetime OR a.rawMetarDateTime IS NULL) AND a.activeWinter = 1 AND a.alternateWinter = 0")
        );
    }

    public function setUp()
    {
        self::bootKernel();

        $this->weatherHelper = new \AppBundle\Helpers\WeatherHelper();

        $this->em = static::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();
    }

    /**
     * @dataProvider dataProvider
     */
    public function testOldMetar($season, $dql)
    {

        /**
         * @var $query \Doctrine\ORM\Query
         */
        $query = $this->em->getRepository('AppBundle:MonitoredAirport')->getAirportsWithOldMetar(
            $season,
            $this->weatherHelper->getReferenceTime()
        );

        $this->assertEquals(
            $dql, $query->getDQL()
        );

    }
}

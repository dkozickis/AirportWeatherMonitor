<?php
/**
 * Created by PhpStorm.
 * User: Denis
 * Date: 01/02/16
 * Time: 15:05.
 */
namespace AppBundle\Services;

use Symfony\Bridge\Monolog\Logger;
use AppBundle\Entity\MonitoredAirports;

class WeatherProvider
{
    /** @var Logger $weatherLogger */
    private $weatherLogger;

    /** @var  MonitoredAirports[] array */
    private $airports;

    public function __construct(Logger $logger)
    {
        $this->weatherLogger = $logger;
    }

    /**
     * Retrieves METAR/TAF XML from aviationweather.gov, returns SimpleXMLElement.
     *
     * @param array  $airports array of airports
     * @param string $type     `metars` or `tafs`
     *
     * @return \SimpleXMLElement
     */
    public function getWeatherXML($airports, $type)
    {
        $this->airports = $airports;

        $this->filterAirportsByTime();

        $airportsString = implode(' ', array_keys($this->airports));
        $fullUrl = 'http://aviationweather.gov/adds/dataserver_current/httpparam'.
            '?dataSource='.$type.'&requestType=retrieve&format=xml&compression=gzip&mostRecentForEachStation=true&'.
            'hoursBeforeNow=12&stationString='.$airportsString;

        $this->weatherLogger->info('Requested '.strtoupper($type).' for airports - '.$airportsString);

        $xml = new \SimpleXMLElement("compress.zlib://$fullUrl", 0, 1);

        return $xml;
    }

    private function filterAirportsByTime()
    {
        foreach ($this->airports as $key => $airport) {
            $metarDateTime = $airport->getRawMetarDateTime();
            $now = new \DateTime('now', new \DateTimeZone('UTC'));

            if ($metarDateTime) {
                $diff = $now->diff($metarDateTime, 1)->format('%i');
                if ($diff < 20) {
                    unset($this->airports[$key]);
                }
            }
        }
    }
}

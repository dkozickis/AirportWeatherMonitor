<?php
/**
 * Created by PhpStorm.
 * User: Denis
 * Date: 01/02/16
 * Time: 15:05.
 */
namespace AppBundle\Services;

use Symfony\Bridge\Monolog\Logger;

class WeatherProvider
{
    /** @var Logger $weatherLogger */
    private $weatherLogger;

    public function __construct(Logger $logger)
    {
        $this->weatherLogger = $logger;
    }

    /**
     * @param $airports
     * @param $type
     *
     * @return array
     */
    public function getWeather($airports, $type)
    {
        $upperCaseType = strtoupper($type);
        $weatherData = [];

        $freshWeather = $this->getWeatherXML($airports, $type);

        foreach ($freshWeather->data->$upperCaseType as $weather) {
            $stationID = (string) $weather->station_id;
            $rawWeather = (string) $weather->raw_text;
            $rawWeatherTime = new \DateTime($weather->observation_time, new \DateTimeZone('UTC'));

            $weatherData[$stationID] = array(
                'rawWeather' => $rawWeather,
                'rawWeatherTime' => $rawWeatherTime,
            );
        }

        return $weatherData;
    }

    /**
     * Retrieves METAR/TAF XML from aviationweather.gov, returns SimpleXMLElement.
     *
     * @param string $type `metars` or `tafs`
     *
     * @return \SimpleXMLElement
     */
    private function getWeatherXML($airports, $type, $gzip = true)
    {
        $type = $type.'s';
        $airportsString = implode(',', array_values($airports));

        $fullUrl = 'http://aviationweather.gov/adds/dataserver_current/httpparam'.
            '?dataSource='.$type.'&requestType=retrieve&format=xml&mostRecentForEachStation=true&'.
            'hoursBeforeNow=3&stationString='.$airportsString;

        if ($gzip) {
            $fullUrl = $fullUrl.'&compression=gzip';
            $xml = new \SimpleXMLElement('compress.zlib://'.$fullUrl, 0, 1);
        } else {
            $xml = new \SimpleXMLElement($fullUrl, 0, 1);
        }

        $this->weatherLogger->info('Requested '.strtoupper($type).' for airports - '.$airportsString);

        return $xml;
    }
}

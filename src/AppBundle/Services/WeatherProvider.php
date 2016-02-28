<?php
/**
 * Created by PhpStorm.
 * User: Denis
 * Date: 01/02/16
 * Time: 15:05
 */

namespace AppBundle\Services;


class WeatherProvider
{

    /**
     * Retrieves METAR/TAF XML from aviationweather.gov, returns SimpleXMLElement
     *
     * @param array $airports array of airports
     * @param string $type `metars` or `tafs`
     * @return \SimpleXMLElement
     */
    function getWeatherXML($airports, $type = 'metars')
    {

        $fullUrl = 'http://aviationweather.gov/adds/dataserver_current/httpparam'.
            '?dataSource='.$type.'&requestType=retrieve&format=xml&mostRecentForEachStation=true&'.
            'hoursBeforeNow=3&stationString='.implode(" ", array_keys($airports));

        $xml = new \SimpleXMLElement($fullUrl, 0, 1);

        return $xml;

    }


}
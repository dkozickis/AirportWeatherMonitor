<?php

namespace AppBundle\Services;

use Doctrine\ORM\EntityManager;
use AppBundle\Entity\Airports;
use MetarDecoder\MetarDecoder;
use MetarDecoder\Entity\SurfaceWind;

class WeatherParser
{

    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getAndParseMetars()
    {
        $airports = $this->parserInit();

        foreach ($airports as $airport) {
            $this->parseMetarWind($airport);
        }

        return $airports;
    }

    /**
     * @return \AppBundle\Entity\Airports[]
     */
    private function parserInit()
    {

        $metarDecoder = new MetarDecoder();
        $airports = $this->entityManager->getRepository('AppBundle:Airports')->getAirportsData();

        /** @var \SimpleXMLElement $metars */
        $metars = $this->getWeatherXml($airports, 'metars');

        foreach ($metars->data->METAR as $metar) {
            $stationID = (string)$metar->station_id;
            $airports[$stationID]->setTextMetar((string)$metar->raw_text);
            $airports[$stationID]->setDecodedMetar($metarDecoder->parse($metar->raw_text));
            //$decodedMetars[$stationID]['observation_time'] = strtotime($metar->observation_time);
        }

        return $airports;

    }

    private function getWeatherXml($airports, $type = 'metars')
    {

        $weatherProdivider = new WeatherProvider();
        $airportsArray = $this->airportICAOCodeArray($airports);

        return $weatherProdivider->getWeatherXML($airportsArray, $type);
    }

    private function airportICAOCodeArray($airports)
    {

        $airportsArray = [];
        /** @var Airports[] $airport */
        foreach ($airports as $airport) {
            $airportsArray[] = $airport->getAirportIcao();
        }

        return $airportsArray;

    }

    private function parseMetarWind(Airports $airport)
    {
        $metarCondition = 1;

        $midWarning = $airport->getMidWarningWind();
        $highWarning = $airport->getHighWarningWind();

        // TODO: Check against SurfaceWinds equals NULL
        /** @var SurfaceWind $surfaceWind */
        $surfaceWind = $airport->getDecodedMetar()->getSurfaceWind();
        $surfaceWindChunk = $surfaceWind->getChunk();
        $knots = $surfaceWind->getMeanSpeed()->getValue();
        $gustKnotsValue = $surfaceWind->getSpeedVariations();

        if (isset($gustKnotsValue)) {
            $knots = $gustKnotsValue->getValue();
        }

        if ($knots >= $midWarning && $knots < $highWarning) {
            $metarCondition = 2;
            $airport->setTextMetar(
                $this->colorize($surfaceWindChunk, 'yellow', $airport->getTextMetar())
            );
        } elseif ($knots >= $highWarning) {
            $metarCondition = 3;
            $airport->setTextMetar(
                $this->colorize($surfaceWindChunk, 'red', $airport->getTextMetar())
            );
        }

        $airport->setMetarCondition($metarCondition);

        return array(
            'metarCondition' => $metarCondition,
            'metarText' => $airport->getTextMetar()
        );

    }

    private function colorize($partToColorize, $color, $oldTextMetar)
    {

        return str_replace(
            $partToColorize,
            '<span style="color:'.$color.';font-weight:bold;">'.$partToColorize.'</span>',
            $oldTextMetar
        );

    }

}
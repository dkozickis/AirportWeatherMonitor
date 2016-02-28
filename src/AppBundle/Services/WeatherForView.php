<?php

namespace AppBundle\Services;

use Doctrine\ORM\EntityManager;
use MetarDecoder\MetarDecoder;

class WeatherForView
{

    /**
     * @var \AppBundle\Entity\Airports[]
     */
    private $airports;

    private $entityManager;

    public function __construct(array $airports, EntityManager $entityManager)
    {
        $this->airports = $airports;
        $this->entityManager = $entityManager;
    }

    public function prepareForView()
    {
        $wp = new WeatherProvider();
        $freshMetars = $wp->getWeatherXML($this->airports);
        $this->populateAirportsWithWeather($freshMetars);

        $this->decodeAndValidateMetars();

        return $this->airports;
    }

    private function populateAirportsWithWeather($freshMetars)
    {
        foreach ($freshMetars->data->METAR as $metar) {
            $stationID = (string)$metar->station_id;
            $rawMetar = (string)$metar->raw_text;
            $rawMetarTime = new \DateTime($metar->observation_time, new \DateTimeZone('UTC'));

            $this->airports[$stationID]->setRawMetar($rawMetar);
            $this->airports[$stationID]->setRawMetarDateTime($rawMetarTime);
            $this->entityManager->persist($this->airports[$stationID]);
        }

        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    private function decodeAndValidateMetars()
    {
        $md = new MetarDecoder();

        foreach ($this->airports as $airport)
        {
            $decodedMetar = $md->parse($airport->getRawMetar());
            $airport->setDecodedMetar($decodedMetar);

            $wv = new MetarValidator($airport, $decodedMetar);
            $validatedMetar = $wv->validate();

            $airport->setValidatedWeather($validatedMetar);
        }
    }


}
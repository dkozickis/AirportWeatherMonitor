<?php

namespace AppBundle\Services;

use Doctrine\ORM\EntityManager;
use GeoJson\Feature\Feature;
use GeoJson\Feature\FeatureCollection;
use GeoJson\Geometry\Point;
use MetarDecoder\MetarDecoder;
use Symfony\Bridge\Monolog\Logger;
use AppBundle\Entity\MonitoredAirports;

class WeatherForView
{
    /**
     * @var \AppBundle\Entity\MonitoredAirports[]
     */
    private $airports;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var Logger
     */
    private $weatherLogger;

    /**
     * WeatherForView constructor.
     *
     * @param EntityManager $entityManager
     * @param Logger        $weatherLogger
     */
    public function __construct(EntityManager $entityManager, Logger $weatherLogger)
    {
        $this->entityManager = $entityManager;
        $this->weatherLogger = $weatherLogger;
    }

    /**
     * @param MonitoredAirports[] $airports
     *
     * @return FeatureCollection
     */
    public function getJsonWeather($airports)
    {
        $this->setAirports($airports);
        $this->fillAirportsWithData();

        $features = array();

        foreach ($this->airports as $airport) {
            $data = $airport->getAirportData();
            $features[] = new Feature(new Point(array($data->getLon(), $data->getLat())), array(
                'name' => $airport->getAirportData()->getAirportIcao(),
                'colorizedMetar' => $airport->getColorizedMetar(),
                'metarStatus' => $airport->getValidatedMetar()->getWeatherStatus(),
            ));
        }

        $featureCollection = new FeatureCollection($features);

        return $featureCollection;
    }

    private function fillAirportsWithData()
    {
        $weatherProvider = new WeatherProvider($this->weatherLogger);

        $freshMetars = $weatherProvider->getWeatherXML($this->airports, 'metars');
        $this->populateAirportsWithWeather($freshMetars, 'metar');

        $freshTafs = $weatherProvider->getWeatherXML($this->airports, 'tafs');
        $this->populateAirportsWithWeather($freshTafs, 'taf');

        $this->decodeValidateColorizeWather();
    }

    private function setAirports($airports)
    {
        $this->airports = $airports;
    }

    private function populateAirportsWithWeather($freshWeather, $type)
    {
        $upperCaseType = strtoupper($type);
        $firstLetterUpperType = ucfirst($type);
        $weatherSet = 'setRaw'.$firstLetterUpperType;
        $dateSet = $weatherSet.'DateTime';

        foreach ($freshWeather->data->$upperCaseType as $weather) {
            $stationID = (string) $weather->station_id;
            $rawWeather = (string) $weather->raw_text;
            $rawWeatherTime = new \DateTime($weather->observation_time, new \DateTimeZone('UTC'));

            $this->airports[$stationID]->$weatherSet($rawWeather);
            $this->airports[$stationID]->$dateSet($rawWeatherTime);
            $this->entityManager->persist($this->airports[$stationID]);
        }

        $this->entityManager->flush();
    }

    private function decodeValidateColorizeWather()
    {
        $metarDecoder = new MetarDecoder();
        $metarValidator = new MetarValidator($this->weatherLogger);

        foreach ($this->airports as $airport) {
            $decodedMetar = $metarDecoder->parse($airport->getRawMetar());
            $airport->setDecodedMetar($decodedMetar);

            $validatedMetar = $metarValidator->validate($airport);
            $airport->setValidatedMetar($validatedMetar);

            $airport->setColorizedMetar($airport->getRawMetar());

            if ($validatedMetar->getWeatherStatus() > 1) {
                foreach ($validatedMetar->getWeatherWarnings() as $warning) {
                    $airport->setColorizedMetar(
                        $this->colorize($warning->getChunk(), 'yellow', $airport->getColorizedMetar())
                    );
                }
            }
        }
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

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
     * @var WeatherProvider
     */
    private $weatherProvider;

    /**
     * WeatherForView constructor.
     *
     * @param EntityManager $entityManager
     * @param Logger $weatherLogger
     */
    public function __construct(EntityManager $entityManager, Logger $weatherLogger)
    {
        $this->entityManager = $entityManager;
        $this->weatherLogger = $weatherLogger;
        $this->weatherProvider = new WeatherProvider($this->weatherLogger);
    }

    /**
     * @param $airports
     * @return FeatureCollection
     */
    public function getGeoJsonWeather($airports)
    {
        $this->setAirports($airports);
        $this->fillAirportsWithData();

        $features = array();

        foreach ($this->airports as $airport) {
            $data = $airport->getAirportData();
            $features[] = new Feature(
                new Point(array($data->getLon(), $data->getLat())), array(
                    'name' => $airport->getAirportData()->getAirportIcao(),
                    'colorizedMetar' => $airport->getColorizedMetar(),
                    'metarStatus' => $airport->getValidatedMetar()->getWeatherStatus(),
                )
            );
        }

        $featureCollection = new FeatureCollection($features);

        return $featureCollection;
    }

    private function setAirports($airports)
    {
        $this->airports = $airports;
    }

    private function fillAirportsWithData()
    {
        $airportsWithOutdatedWeather = $this->filterRelevantAirportsByTime();

        if (count($airportsWithOutdatedWeather) > 0) {
            $freshMetars = $this->getWeatherFromProvider($airportsWithOutdatedWeather, 'metar');
            $this->populateAirportsWithWeather($freshMetars, 'metar');

            $freshTafs = $this->getWeatherFromProvider($airportsWithOutdatedWeather, 'taf');
            $this->populateAirportsWithWeather($freshTafs, 'taf');
        }

        $this->decodeValidateColorizeWather();
    }

    private function filterRelevantAirportsByTime($threshold = 30)
    {
        $relevantAirports = [];

        foreach ($this->airports as $key => $airport) {
            $metarDateTime = $airport->getRawMetarDateTime();
            $now = new \DateTime('now', new \DateTimeZone('UTC'));

            if (!$metarDateTime || $now->diff($metarDateTime, 1)->format('%i') > $threshold) {
                $relevantAirports[] = $key;

            }
        }

        return $relevantAirports;
    }

    private function getWeatherFromProvider($airports, $type)
    {
        return $this->weatherProvider->getWeather($airports, $type);
    }

    private function populateAirportsWithWeather($freshWeather, $type)
    {
        $firstLetterUpperType = ucfirst($type);

        $weatherSet = 'setRaw'.$firstLetterUpperType;
        $dateSet = $weatherSet.'DateTime';

        foreach ($freshWeather as $stationID => $data) {
            $this->airports[$stationID]->$weatherSet($data['rawWeather']);
            $this->airports[$stationID]->$dateSet($data['rawWeatherTime']);
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

<?php

namespace AppBundle\Services;

use AppBundle\Entity\MonitoredAirports;
use AppBundle\Services\WeatherValidator\MetarValidator;
use AppBundle\Services\WeatherValidator\TafValidator;
use Doctrine\ORM\EntityManager;
use GeoJson\Feature\Feature;
use GeoJson\Feature\FeatureCollection;
use GeoJson\Geometry\Point;
use MetarDecoder\MetarDecoder;
use Symfony\Bridge\Monolog\Logger;
use TafDecoder\TafDecoder;

class WeatherProcessor
{

    const STATUS_COLOR = array(
        0 => "grey",
        1 => "green",
        2 => "yellow",
        3 => "red"
    );

    /**
     * @var MonitoredAirports[]
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
        $this->init($airports);

        $features = array();

        foreach ($this->airports as $airport) {
            $data = $airport->getAirportData();
            $features[] = new Feature(
                new Point(array($data->getLon(), $data->getLat())), array(
                    'name' => $airport->getAirportData()->getAirportIcao(),
                    'colorizedMetar' => $airport->getColorizedMetar(),
                    'metarStatus' => $airport->getValidatedMetar()->getWeatherStatus(),
                    'colorizedTaf' => $airport->getColorizedTaf(),
                    'tafStatus' => $airport->getValidatedTaf()->getWeatherStatus()
                )
            );
        }

        $featureCollection = new FeatureCollection($features);

        return $featureCollection;
    }

    /**
     * @param $airports
     */
    private function init($airports)
    {
        $this->airports = $airports;
        $this->fillAirportsWithData();
    }

    private function fillAirportsWithData()
    {
        $airportsWithOutdatedWeather = $this->filterRelevantAirportsByTime();

        if (count($airportsWithOutdatedWeather) > 0) {
            $this->fillAirportsWithNewWeather($airportsWithOutdatedWeather, 'metar');
            $this->fillAirportsWithNewWeather($airportsWithOutdatedWeather, 'taf');
        }

        $this->decodeValidateColorizeWeather();
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

    /**
     * @param $airportsWithOutdatedWeather
     * @param $type
     */
    private function fillAirportsWithNewWeather($airportsWithOutdatedWeather, $type)
    {
        $freshWeather = $this->getWeatherFromProvider($airportsWithOutdatedWeather, $type);
        $this->populateAirportsWithWeather($freshWeather, $type);
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

    private function decodeValidateColorizeWeather()
    {
        foreach ($this->airports as $airport) {
            $this->weatherDecodePass($airport);
            $this->weatherValidatePass($airport);

            $validatedMetar = $airport->getValidatedMetar();
            $metarStatus = $validatedMetar->getWeatherStatus();

            if ($metarStatus > 1) {
                foreach ($validatedMetar->getWeatherWarnings() as $warning) {
                    $airport->setColorizedMetar(
                        $this->colorize(
                            $warning->getChunk(),
                            self::STATUS_COLOR[$metarStatus],
                            $airport->getColorizedMetar()
                        )
                    );
                }
            }

            $validatedTaf = $airport->getValidatedTaf();
            $tafStatus = $validatedTaf->getWeatherStatus();

            $airport->setColorizedTaf(preg_replace(
                '/(BECMG)|((PROB30|PROB40)(\sTEMPO)?)|(TEMPO)|(FM)/',
                '<br/>&nbsp;&nbsp;$0',
                $airport->getColorizedTaf()
            ));

            if ($tafStatus > 1) {
                foreach ($validatedTaf->getWeatherWarnings() as $warning) {
                    $airport->setColorizedTaf(
                        $this->colorize(
                            $warning->getChunk(),
                            self::STATUS_COLOR[$tafStatus],
                            $airport->getColorizedTaf()
                        )
                    );
                }
            }
        }
    }

    private function colorize($partToColorize, $color, $oldTextWeather)
    {
        return str_replace(
            $partToColorize,
            '<span class="'.$color.'">'.$partToColorize.'</span>',
            $oldTextWeather
        );
    }

    /**
     * @param MonitoredAirports $airport
     */
    private function weatherDecodePass(MonitoredAirports $airport)
    {
        $metarDecoder = new MetarDecoder();
        $tafDecoder = new TafDecoder();

        $decodedMetar = $metarDecoder->parse($airport->getRawMetar());
        $airport->setDecodedMetar($decodedMetar);

        $decodedTaf = $tafDecoder->parse($airport->getRawTaf());
        $airport->setDecodedTaf($decodedTaf);
    }

    /**
     * @param MonitoredAirports $airport
     * @return \AppBundle\Entity\ValidatedWeather
     */
    private function weatherValidatePass(MonitoredAirports $airport)
    {
        $metarValidator = new MetarValidator($this->weatherLogger);
        $tafValidator = new TafValidator($this->weatherLogger);

        $validatedMetar = $metarValidator->validate($airport);
        $airport->setValidatedMetar($validatedMetar);

        $validatedTaf = $tafValidator->validate($airport);
        $airport->setValidatedTaf($validatedTaf);
    }
}

<?php

namespace AppBundle\Services;

use AppBundle\Entity\MonitoredAirport;
use AppBundle\Helpers\WeatherHelper;
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
        0 => 'grey',
        1 => 'green',
        2 => 'yellow',
        3 => 'red',
    );

    /**
     * @var MonitoredAirport[]
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
     * @var WeatherHelper
     */
    private $weatherHelper;

    /**
     * @var array
     */
    private $phenomenons;

    /**
     * @var int
     */
    private $alternate;

    /**
     * @return int
     */
    public function getAlternate()
    {
        return $this->alternate;
    }

    /**
     * @param int $alternate
     *
     * @return WeatherProcessor
     */
    public function setAlternate($alternate)
    {
        $this->alternate = $alternate;

        return $this;
    }

    /**
     * @return array
     */
    public function getPhenomenons()
    {
        return $this->phenomenons;
    }

    /**
     * @param array $phenomenons
     */
    public function setPhenomenons($phenomenons)
    {
        $this->phenomenons = $phenomenons;
    }

    /**
     * WeatherProcessor constructor.
     *
     * @param EntityManager $entityManager
     * @param Logger        $weatherLogger
     */
    public function __construct(
        EntityManager $entityManager,
        Logger $weatherLogger,
        WeatherHelper $weatherHelper,
        WeatherProvider $weatherProvider
    ) {
        $this->entityManager = $entityManager;
        $this->weatherLogger = $weatherLogger;
        $this->weatherHelper = $weatherHelper;
        $this->weatherProvider = $weatherProvider;
    }

    /**
     * @param MonitoredAirport[] $airports
     *
     * @return FeatureCollection
     */
    public function getGeoJsonWeather($airports, $alternate, $phenomenons)
    {
        $this->airports = $airports;
        $this->phenomenons = $phenomenons;
        $this->alternate = $alternate;
        $airports = $this->fillAirportsWithData();

        $features = array();

        foreach ($airports as $airport) {
            $data = $airport->getAirportData();
            $features[] = new Feature(
                new Point(array($data->getLon(), $data->getLat())), array(
                    'name' => $airport->getAirportData()->getAirportIcao(),
                    'colorizedMetar' => $airport->getColorizedMetar(),
                    'metarStatus' => $airport->getValidatedMetar()->getWeatherStatus(),
                    'colorizedTaf' => $airport->getColorizedTaf(),
                    'tafStatus' => $airport->getValidatedTaf()->getWeatherStatus(),
                )
            );
        }

        $featureCollection = new FeatureCollection($features);

        return $featureCollection;
    }

    private function fillAirportsWithData()
    {
        $airportsWithOldWeather = $this->filterAirportsByWeatherTime();

        if (count($airportsWithOldWeather) > 0) {
            $this->getAndUpdateWeather($airportsWithOldWeather);
        }

        $this->decodeValidateColorizeWeather();

        return $this->airports;
    }

    /**
     * @param int $difference
     *
     * @return array
     */
    private function filterAirportsByWeatherTime($difference = 30)
    {
        $relevantAirports = [];

        foreach ($this->airports as $key => $airport) {
            $metarDateTime = $airport->getRawMetarDateTime();
            $referenceTime = $this->weatherHelper->getReferenceTime($difference);

            if (!$metarDateTime || $referenceTime > $metarDateTime) {
                $relevantAirports[] = $key;
            }
        }

        return $relevantAirports;
    }

    /**
     * @param $airportsWithOutdatedWeather
     * @param $type
     */
    private function getAndUpdateWeather($airportsWithOutdatedWeather)
    {
        $freshWeather = $this->getWeather($airportsWithOutdatedWeather);
        $this->updateWeather($freshWeather);
    }

    /**
     * @param $airports
     * @param $type
     *
     * @return array
     */
    private function getWeather($airports)
    {
        return $this->weatherProvider->getWeather($airports);
    }

    /**
     * @param $freshWeather
     * @param $type
     */
    private function updateWeather($freshWeather)
    {
        foreach ($freshWeather as $stationID => $data) {
            $this->airports[$stationID]->setRawMetar($data["metar"]);
            $this->airports[$stationID]->setRawMetarDateTime(new \DateTime($data['metar_obs_time']));
            $this->airports[$stationID]->setRawTaf($data["taf"]);
            $this->airports[$stationID]->setRawTafDateTime(new \DateTime($data['taf_obs_time']));
            $this->entityManager->persist($this->airports[$stationID]);
        }

        $this->entityManager->flush();
    }

    private function decodeValidateColorizeWeather()
    {
        foreach ($this->airports as $airport) {
            $this->weatherDecodePass($airport);
            $this->weatherValidatePass($airport, $this->alternate);
            $this->weatherColorizePass($airport, 'metar');
            $this->beautifyTaf($airport);
            $this->weatherColorizePass($airport, 'taf');
        }
    }

    /**
     * @param MonitoredAirport $airport
     *
     * @return MonitoredAirport
     */
    private function weatherDecodePass(MonitoredAirport $airport)
    {
        $metarDecoder = new MetarDecoder();
        $tafDecoder = new TafDecoder();

        $decodedMetar = $metarDecoder->parse($airport->getRawMetar());
        $airport->setDecodedMetar($decodedMetar);

        $decodedTaf = $tafDecoder->parse($airport->getRawTaf());
        $airport->setDecodedTaf($decodedTaf);

        return $airport;
    }

    /**
     * @param MonitoredAirport $airport
     *
     * @return MonitoredAirport
     */
    private function weatherValidatePass(MonitoredAirport $airport, $alternate)
    {
        $metarValidator = new MetarValidator($this->weatherLogger, $this->phenomenons);
        $tafValidator = new TafValidator($this->weatherLogger, $this->phenomenons);

        $validatedMetar = $metarValidator->validate($airport, $alternate);
        $airport->setValidatedMetar($validatedMetar);

        $validatedTaf = $tafValidator->validate($airport, $alternate);
        $airport->setValidatedTaf($validatedTaf);

        return $airport;
    }

    /**
     * @param MonitoredAirport $airport
     * @param $type
     *
     * @return MonitoredAirport
     */
    private function weatherColorizePass(MonitoredAirport $airport, $type)
    {
        $firstLetterUpperType = ucfirst($type);
        $getValidatedWeather = 'getValidated'.$firstLetterUpperType;
        $getColorizedWeather = 'getColorized'.$firstLetterUpperType;
        $setColorizedWeather = 'setColorized'.$firstLetterUpperType;

        $validatedWeather = $airport->$getValidatedWeather();
        $weatherStatus = $validatedWeather->getWeatherStatus();

        if ($weatherStatus > 1) {
            foreach ($validatedWeather->getWeatherWarnings() as $warning) {
                $airport->$setColorizedWeather(
                    $this->colorString(
                        $warning->getChunk(),
                        self::STATUS_COLOR[$warning->getWarningLevel()],
                        $airport->$getColorizedWeather()
                    )
                );
            }
        }

        return $airport;
    }

    /**
     * @param MonitoredAirport $airport
     *
     * @return MonitoredAirport
     */
    private function beautifyTaf(MonitoredAirport $airport)
    {
        $airport->setColorizedTaf(
            preg_replace(
                '/(BECMG)|((PROB30|PROB40)(\sTEMPO)?)|(TEMPO)|(FM)/',
                '<br/>&nbsp;&nbsp;$0',
                $airport->getColorizedTaf()
            )
        );

        return $airport;
    }

    /**
     * @param $partToColorize
     * @param $color
     * @param $oldTextWeather
     *
     * @return mixed
     */
    private function colorString($partToColorize, $color, $oldTextWeather)
    {
        return str_replace(
            $partToColorize,
            '<span class="'.$color.'">'.$partToColorize.'</span>',
            $oldTextWeather
        );
    }
}

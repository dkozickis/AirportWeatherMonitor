<?php
/**
 * Created by PhpStorm.
 * User: Denis
 * Date: 24/04/16
 * Time: 18:29.
 */
namespace AppBundle\Services\WeatherValidator;

use AppBundle\Entity\MonitoredAirport;
use AppBundle\Entity\ValidatedWeather;
use AppBundle\Entity\ValidatorWarning;
use MetarDecoder\Entity\Value;
use MetarDecoder\Exception\ChunkDecoderException;
use Symfony\Bridge\Monolog\Logger;

abstract class WeatherValidator
{
    const HIGH_ALERT = 3;
    const MID_ALERT = 2;
    const NO_ALERT = 1;
    const CEILING_CLOUDS = array('BKN', 'OVC', 'VV');
    const BAD_DECODER_EXCEPTIONS = array('SurfaceWindChunkDecoder', 'VisibilityChunkDecoder', 'CloudChunkDecoder');

    /**
     * @var MonitoredAirport
     */
    public $airport;

    /**
     * @var ValidatedWeather
     */
    public $validatedWeather;

    /**
     * @var Logger
     */
    public $weatherLogger;

    /**
     * @var string
     */
    public $type;

    /**
     * @var array
     */
    public $phenomenons;

    /**
     * @var int
     */
    public $alternate;

    /**
     * WeatherValidator constructor.
     *
     * @param Logger $weatherLogger
     */
    public function __construct(Logger $weatherLogger, $phenomenons)
    {
        $this->weatherLogger = $weatherLogger;
        $this->phenomenons = $phenomenons;
    }

    abstract public function validate(MonitoredAirport $airport, $alternate);

    /**
     * @param \MetarDecoder\Entity\SurfaceWind|\TafDecoder\Entity\SurfaceWind $surfaceWind
     */
    protected function validateWind($surfaceWind)
    {
        if ($this->alternate == 1) {
            $midWarning = $this->airport->getMidWarningWindAlt();
            $highWarning = $this->airport->getHighWarningWindAlt();
            if (null === $midWarning) {
                $midWarning = $this->airport->getMidWarningWind();
            }
            if (null === $highWarning) {
                $highWarning = $this->airport->getHighWarningWind();
            }
        } else {
            $midWarning = $this->airport->getMidWarningWind();
            $highWarning = $this->airport->getHighWarningWind();
        }

        $surfaceWindChunk = $surfaceWind->getChunk();
        $knots = $surfaceWind->getMeanSpeed()->getConvertedValue('kt');
        $gustKnotsValue = $surfaceWind->getSpeedVariations();

        if (isset($gustKnotsValue)) {
            $knots = $gustKnotsValue->getConvertedValue('kt');
        }

        $this->exceedsWarningCheck($knots, $midWarning, $highWarning, $surfaceWindChunk);
    }

    /**
     * @param $referenceValue
     * @param $midWarning
     * @param $highWarning
     * @param $chunk
     */
    protected function exceedsWarningCheck($referenceValue, $midWarning, $highWarning, $chunk)
    {
        $metarStatus = $this->exceedsValueCheck($referenceValue, $midWarning, $highWarning);

        if ($metarStatus > self::NO_ALERT) {
            $this->generateWarning($chunk, $metarStatus);
        }
    }

    /**
     * @param $referenceValue
     * @param $midWarning
     * @param $highWarning
     *
     * @return int
     */
    protected function exceedsValueCheck($referenceValue, $midWarning, $highWarning)
    {
        if ($referenceValue > $highWarning) {
            return self::HIGH_ALERT;
        } elseif ($referenceValue > $midWarning) {
            return self::MID_ALERT;
        } else {
            return self::NO_ALERT;
        }
    }

    /**
     * @param $chunk
     * @param $weatherStatus
     *
     * @return ValidatedWeather
     */
    protected function generateWarning($chunk, $weatherStatus)
    {
        $validatorWarning = new ValidatorWarning();
        $validatorWarning->setChunk($chunk);
        $validatorWarning->setWarningLevel($weatherStatus);
        $this->validatedWeather->setWeatherStatus($weatherStatus);
        $this->validatedWeather->addWarning($validatorWarning);
    }

    /**
     * @param \MetarDecoder\Entity\CloudLayer|\TafDecoder\Entity\CloudLayer $cloud
     */
    protected function validateCeiling($cloud)
    {
        if ($this->alternate == 1) {
            $midWarning = $this->airport->getMidWarningCeilingAlt();
            $highWarning = $this->airport->getHighWarningCeilingAlt();
            if (null === $midWarning) {
                $midWarning = $this->airport->getMidWarningCeiling();
            }
            if (null === $highWarning) {
                $highWarning = $this->airport->getHighWarningCeiling();
            }
        } else {
            $midWarning = $this->airport->getMidWarningCeiling();
            $highWarning = $this->airport->getHighWarningCeiling();
        }
        $cloudChunk = $cloud->getChunk();
        $cloudAmount = $cloud->getAmount();
        $cloudBase = $cloud->getBaseHeight()->getConvertedValue('ft');

        if (in_array($cloudAmount, self::CEILING_CLOUDS)) {
            $this->belowWarningCheck($cloudBase, $midWarning, $highWarning, $cloudChunk);
        }
    }

    /**
     * @param $referenceValue
     * @param $midWarning
     * @param $highWarning
     * @param $chunk
     */
    protected function belowWarningCheck($referenceValue, $midWarning, $highWarning, $chunk)
    {
        $metarStatus = $this->belowValueCheck($referenceValue, $midWarning, $highWarning);

        if ($metarStatus > self::NO_ALERT) {
            $this->generateWarning($chunk, $metarStatus);
        }
    }

    /**
     * @param $referenceValue
     * @param $midWarning
     * @param $highWarning
     *
     * @return int
     */
    protected function belowValueCheck($referenceValue, $midWarning, $highWarning)
    {
        if ($referenceValue < $highWarning) {
            return self::HIGH_ALERT;
        } elseif ($referenceValue < $midWarning) {
            return self::MID_ALERT;
        } else {
            return self::NO_ALERT;
        }
    }

    /**
     * @param \MetarDecoder\Entity\Visibility|\TafDecoder\Entity\Visibility $visibility
     */
    protected function validateVisibility($visibility)
    {
        if ($this->alternate == 1) {
            $midWarning = $this->airport->getMidWarningVisAlt();
            $highWarning = $this->airport->getHighWarningVisAlt();
            if (null === $midWarning) {
                $midWarning = $this->airport->getMidWarningVis();
            }
            if (null === $highWarning) {
                $highWarning = $this->airport->getHighWarningVis();
            }
        } else {
            $midWarning = $this->airport->getMidWarningVis();
            $highWarning = $this->airport->getHighWarningVis();
        }
        if (null === $visibility->getVisibility()) {
            $realVis = new Value(0, 'm');
            $visibility->setVisibility($realVis);
        }
        $visDistance = $visibility->getVisibility()->getConvertedValue('m');
        $visChunk = $visibility->getChunk();

        $this->belowWarningCheck($visDistance, $midWarning, $highWarning, $visChunk);
    }

    /**
     * @param $rawWeather
     * @param $decodingExceptions
     *
     * @return bool
     */
    protected function checkProcessingErrors($rawWeather, $decodingExceptions)
    {
        $airportIcao = $this->airport->getAirportData()->getAirportIcao();

        if (!$rawWeather) {
            $this->validatedWeather->setWeatherStatus(0);
            $this->weatherLogger->warning($airportIcao.' had wrong '.$this->type.": '".$rawWeather."'");

            return false;
        }

        if ($decodingExceptions) {
            /** @var ChunkDecoderException $exception */
            foreach ($decodingExceptions as $exception) {
                if (in_array($exception->getChunkDecoder(), self::BAD_DECODER_EXCEPTIONS)) {
                    $this->validatedWeather->setWeatherStatus(0);
                    $this->weatherLogger->warning(
                        $airportIcao.' had '.$exception->getChunkDecoder().": '".$rawWeather."'"
                    );

                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param $weatherPhenomenonChunk
     */
    protected function validatePhenomenon($weatherPhenomenonChunk)
    {
        if (in_array($weatherPhenomenonChunk, $this->phenomenons['mid'])) {
            $this->generateWarning($weatherPhenomenonChunk, self::MID_ALERT);
        }
        if (in_array($weatherPhenomenonChunk, $this->phenomenons['high'])) {
            $this->generateWarning($weatherPhenomenonChunk, self::HIGH_ALERT);
        }
    }
}

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
use MetarDecoder\Exception\ChunkDecoderException;
use Symfony\Bridge\Monolog\Logger;

abstract class WeatherValidator
{
    const HIGH_ALERT = 3;
    const MID_ALERT = 2;
    const NO_ALERT = 1;
    const CEILING_CLOUDS = array('BKN', 'OVC', 'VV');
    const BAD_DECODER_EXCEPTIONS = array('SurfaceWindChunkDecoder', 'VisibilityChunkDecoder', 'CloudChunkDecoder');

    //TODO: Move phenoms to DB

    const MID_WEATHER_PHENOMEN = array(
        'TSRA',
        'TSPL',
        '+PL',
        'GR',
        'TSGR',
        'TSGS',
        '+SHPL',
        'SHGR',
        'FG',
        'PRFG',
        'BCFG',
        'FU',
        'VA',
        'DU',
        'BLDU',
        'VCBLSA',
        'BLPY',
        'PO',
        'SQ',
        'FC',
        '+FC',
        'SS',
        '+SS',
        'DS',
        '+DS',
        'VCDS',
        'FZBR',
        'VV///',
    );

    const HIGH_WEATHER_PHENOMEN = array(
        '+FZDZ',
        'FZFG',
        'FZRA',
        'SA',
        'BLSA',
        '+SN',
        'BLSN',
        'VCBLSN',
        '+TSRA',
        'TSSN',
        '+TSSN',
        '+SHSN',
        'FZDZ',
        '-FZDZ',
        '-FZRA',
        '+FZRA',
    );

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
     * WeatherValidator constructor.
     *
     * @param Logger $weatherLogger
     */
    public function __construct(Logger $weatherLogger)
    {
        $this->weatherLogger = $weatherLogger;
    }

    abstract public function validate(MonitoredAirport $airport);

    /**
     * @param \MetarDecoder\Entity\SurfaceWind|\TafDecoder\Entity\SurfaceWind $surfaceWind
     */
    protected function validateWind($surfaceWind)
    {
        $highWarning = $this->airport->getHighWarningWind();

        $surfaceWindChunk = $surfaceWind->getChunk();
        $knots = $surfaceWind->getMeanSpeed()->getConvertedValue('kt');
        $gustKnotsValue = $surfaceWind->getSpeedVariations();

        if (isset($gustKnotsValue)) {
            $knots = $gustKnotsValue->getConvertedValue('kt');
        }

        $this->exceedsWarningCheck($knots, $highWarning, $highWarning, $surfaceWindChunk);
    }

    /**
     * @param \MetarDecoder\Entity\CloudLayer|\TafDecoder\Entity\CloudLayer $cloud
     */
    protected function validateCeiling($cloud)
    {
        $midWarning = $this->airport->getMidWarningCeiling();
        $highWarning = $this->airport->getHighWarningCeiling();

        $cloudChunk = $cloud->getChunk();
        $cloudAmount = $cloud->getAmount();
        $cloudBase = $cloud->getBaseHeight()->getConvertedValue('ft');

        if (in_array($cloudAmount, self::CEILING_CLOUDS)) {
            $this->belowWarningCheck($cloudBase, $midWarning, $highWarning, $cloudChunk);
        }
    }

    /**
     * @param \MetarDecoder\Entity\Visibility|\TafDecoder\Entity\Visibility $visibility
     */
    protected function validateVisibility($visibility)
    {
        $midWarning = $this->airport->getMidWarningVis();
        $highWarning = $this->airport->getHighWarningVis();

        $visDistance = $visibility->getVisibility()->getConvertedValue('m');
        $visChunk = $visibility->getChunk();

        $this->belowWarningCheck($visDistance, $midWarning, $highWarning, $visChunk);
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
}

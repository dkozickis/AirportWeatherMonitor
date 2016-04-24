<?php
/**
 * Created by PhpStorm.
 * User: Denis
 * Date: 24/04/16
 * Time: 18:29
 */

namespace AppBundle\Services\WeatherValidator;

use AppBundle\Entity\MonitoredAirports;
use AppBundle\Entity\ValidatedWeather;
use AppBundle\Entity\ValidatorWarning;
use Symfony\Bridge\Monolog\Logger;


class WeatherValidator
{

    const HIGH_ALERT = 3;
    const MID_ALERT = 2;
    const NO_ALERT = 1;
    const CEILING_CLOUDS = array('BKN', 'OVC', 'VV');

    /**
     * @var MonitoredAirports
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
     * WeatherValidator constructor.
     * @param Logger $weatherLogger
     */
    public function __construct(Logger $weatherLogger)
    {
        $this->weatherLogger = $weatherLogger;
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

        if ($metarStatus > 1) {
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

        if ($metarStatus > 1) {
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
     * @param $metarStatus
     *
     * @return ValidatedWeather
     */
    protected function generateWarning($chunk, $metarStatus)
    {
        $validatorWarning = new ValidatorWarning();
        $validatorWarning->setChunk($chunk);
        $validatorWarning->setWarningLevel($metarStatus);
        $this->validatedWeather->setWeatherStatus($metarStatus);
        $this->validatedWeather->addWarning($validatorWarning);
    }

}
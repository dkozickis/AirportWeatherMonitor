<?php

namespace AppBundle\Services\WeatherValidator;

use AppBundle\Entity\MonitoredAirport;
use AppBundle\Entity\ValidatedWeather;
use MetarDecoder\Entity\WeatherPhenomenon;

class MetarValidator extends WeatherValidator
{
    /**
     * @var string
     */
    public $type = 'METAR';

    /**
     * @param MonitoredAirport $airport
     *
     * @return ValidatedWeather
     */
    public function validate(MonitoredAirport $airport)
    {
        $this->airport = $airport;
        $this->validatedWeather = new ValidatedWeather();
        $decodedMetar = $this->airport->getDecodedMetar();
        $rawMetar = $this->airport->getRawMetar();

        $surfaceWind = $decodedMetar->getSurfaceWind();
        $clouds = $decodedMetar->getClouds();
        $visibility = $decodedMetar->getVisibility();
        $decodingExceptions = $decodedMetar->getDecodingExceptions();
        $presentWeather = $decodedMetar->getPresentWeather();

        if (!$this->checkProcessingErrors($rawMetar, $decodingExceptions)) {
            return $this->validatedWeather;
        }

        if ($presentWeather) {
            /* @var WeatherPhenomenon $phenomenon */
            foreach ($presentWeather as $phenomenon) {
                $this->validatePhenomenon($phenomenon->getChunk());
            }
        }

        if ($surfaceWind) {
            $this->validateWind($surfaceWind);
        }

        if ($decodedMetar->getCavok() !== true) {
            if (!empty($clouds)) {
                foreach ($clouds as $cloud) {
                    if ($cloud->getBaseHeight()) {
                        $this->validateCeiling($cloud);
                    }
                }
            }
            if(!empty($visibility)){
                $this->validateVisibility($visibility);
            }
        }

        return $this->validatedWeather;
    }
}

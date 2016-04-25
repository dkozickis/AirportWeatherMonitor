<?php

namespace AppBundle\Services\WeatherValidator;

use AppBundle\Entity\MonitoredAirports;
use AppBundle\Entity\ValidatedWeather;
use TafDecoder\Entity\CloudLayer;
use TafDecoder\Entity\Evolution;
use TafDecoder\Entity\SurfaceWind;
use TafDecoder\Entity\Visibility;

class TafValidator extends WeatherValidator
{
    /**
     * @var string
     */
    public $type = 'TAF';

    /**
     * @param MonitoredAirports $airport
     *
     * @return ValidatedWeather
     */
    public function validate(MonitoredAirports $airport)
    {
        $this->airport = $airport;
        $this->validatedWeather = new ValidatedWeather();
        $rawTaf = $this->airport->getRawTaf();
        $decodingExceptions = $this->airport->getDecodedTaf()->getDecodingExceptions();

        /* @var SurfaceWind $surfaceWind */
        $surfaceWind = $this->airport->getDecodedTaf()->getSurfaceWind();

        /* @var CloudLayer[] $clouds */
        $clouds = $this->airport->getDecodedTaf()->getClouds();

        /* @var Visibility $visibility */
        $visibility = $this->airport->getDecodedTaf()->getVisibility();

        if (!$this->checkProcessingErrors($rawTaf, $decodingExceptions)) {
            return $this->validatedWeather;
        }

        if ($surfaceWind) {
            /* @var Evolution[] $surfaceWindEvolutions */
            $surfaceWindEvolutions = $surfaceWind->getEvolutions();

            $this->validateWind($surfaceWind);

            if (!empty($surfaceWindEvolutions)) {
                foreach ($surfaceWindEvolutions as $evolution) {
                    $this->validateWind($evolution->getEntity());
                }
            }
        }

        if (!empty($clouds)) {
            foreach ($clouds as $cloud) {
                if ($cloud->getBaseHeight()) {
                    $this->validateCeiling($cloud);
                }
                if ($cloud->getEvolutions()) {
                    /* @var Evolution $evolution */
                    foreach ($cloud->getEvolutions() as $evolution) {
                        /* @var CloudLayer[] $cloudsEvolution */
                        $cloudsEvolution = $evolution->getEntity();
                        foreach ($clouds as $cloud) {
                            if ($cloud->getBaseHeight()) {
                                $this->validateCeiling($cloud);
                            }
                        }
                    }
                }
            }
        }

        if ($this->airport->getDecodedTaf()->getCavok() !== true && isset($visibility)) {
            /* @var Evolution[] $visibilityEvolutions */
            $visibilityEvolutions = $visibility->getEvolutions();

            $this->validateVisibility($visibility);

            if (!empty($visibilityEvolutions)) {
                foreach ($visibilityEvolutions as $evolution) {
                    if (!empty($evolution->getEntity())) {
                        $this->validateVisibility($evolution->getEntity());
                    }
                }
            }
        }

        return $this->validatedWeather;
    }
}

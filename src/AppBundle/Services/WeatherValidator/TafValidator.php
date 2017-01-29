<?php

namespace AppBundle\Services\WeatherValidator;

use AppBundle\Entity\MonitoredAirport;
use AppBundle\Entity\ValidatedWeather;
use TafDecoder\Entity\CloudLayer;
use TafDecoder\Entity\Evolution;
use TafDecoder\Entity\SurfaceWind;
use TafDecoder\Entity\Visibility;
use TafDecoder\Entity\WeatherPhenomenon;

class TafValidator extends WeatherValidator
{
    /**
     * @var string
     */
    public $type = 'TAF';

    /**
     * @param MonitoredAirport $airport
     *
     * @return ValidatedWeather
     */
    public function validate(MonitoredAirport $airport, $alternate)
    {
        $this->airport = $airport;
        $this->alternate = $alternate;
        $this->validatedWeather = new ValidatedWeather();
        $rawTaf = $this->airport->getRawTaf();
        $decodingExceptions = $this->airport->getDecodedTaf()->getDecodingExceptions();

        /* @var SurfaceWind $surfaceWind */
        $surfaceWind = $this->airport->getDecodedTaf()->getSurfaceWind();

        /* @var CloudLayer[] $clouds */
        $clouds = $this->airport->getDecodedTaf()->getClouds();

        /* @var Visibility $visibility */
        $visibility = $this->airport->getDecodedTaf()->getVisibility();

        /* @var WeatherPhenomenon $weatherPhenomenon */
        $weatherPhenomenon = $this->airport->getDecodedTaf()->getWeatherPhenomenon();

        if (!$this->checkProcessingErrors($rawTaf, $decodingExceptions)) {
            return $this->validatedWeather;
        }

        if ($weatherPhenomenon) {
            $weatherPhenomenonChunk = $weatherPhenomenon->getChunk();
            $this->validatePhenomenon($weatherPhenomenonChunk);
            if ($weatherPhenomenon->getEvolutions()) {
                /* @var Evolution $evolution */
                foreach ($weatherPhenomenon->getEvolutions() as $evolution) {
                    /* @var WeatherPhenomenon $weatherPhenomenonEvolution */
                    $weatherPhenomenonEvolution = $evolution->getEntity();
                    $weatherPhenomenonEvolutionChunk = $weatherPhenomenonEvolution->getChunk();
                    $this->validatePhenomenon($weatherPhenomenonEvolutionChunk);
                }
            }
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
                        foreach ($cloudsEvolution as $cloud) {
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

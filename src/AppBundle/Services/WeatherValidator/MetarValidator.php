<?php

namespace AppBundle\Services\WeatherValidator;

use AppBundle\Entity\MonitoredAirports;
use AppBundle\Entity\ValidatedWeather;
use MetarDecoder\Entity\SurfaceWind;
use MetarDecoder\Entity\CloudLayer;
use MetarDecoder\Entity\Value as Value;
use MetarDecoder\Entity\Visibility;
use MetarDecoder\Exception\ChunkDecoderException;

class MetarValidator extends WeatherValidator
{
    /**
     * @param MonitoredAirports $airport
     * @return ValidatedWeather
     */
    public function validate(MonitoredAirports $airport)
    {
        $this->airport = $airport;
        $this->validatedWeather = new ValidatedWeather();

        if (!$this->checkProcessingErrors()) {
            return $this->validatedWeather;
        }

        $this->validateWind();
        if ($this->airport->getDecodedMetar()->getCavok() !== true) {
            if (count($this->airport->getDecodedMetar()->getClouds()) > 0) {
                $this->validateCeiling();
            }
            $this->validateVisibility();
        }

        return $this->validatedWeather;
    }

    private function validateWind()
    {
        $highWarning = $this->airport->getHighWarningWind();
        $surfaceWind = $this->airport->getDecodedMetar()->getSurfaceWind();

        if ($surfaceWind) {
            /* @var SurfaceWind $surfaceWind */
            $surfaceWindChunk = $surfaceWind->getChunk();
            $knots = $surfaceWind->getMeanSpeed()->getConvertedValue('kt');
            $gustKnotsValue = $surfaceWind->getSpeedVariations();

            if (isset($gustKnotsValue)) {
                /* @var Value $knots */
                $knots = $gustKnotsValue->getConvertedValue('kt');
            }

            /**
             * High warning value is passed into both mid and high paremater for check.
             * Requirement for GWI wind check - only HIGH warning.
             * TODO: think how to make it reusable
             */
            $this->exceedsWarningCheck($knots, $highWarning, $highWarning, $surfaceWindChunk);
        }
    }

    private function validateCeiling()
    {
        $midWarning = $this->airport->getMidWarningCeiling();
        $highWarning = $this->airport->getHighWarningCeiling();

        /** @var CloudLayer[] $clouds */
        $clouds = $this->airport->getDecodedMetar()->getClouds();

        foreach ($clouds as $cloud) {
            if ($cloud->getBaseHeight()) {
                $cloudChunk = $cloud->getChunk();
                $cloudAmount = $cloud->getAmount();
                $cloudBase = $cloud->getBaseHeight()->getConvertedValue('ft');

                if (in_array($cloudAmount, self::CEILING_CLOUDS)) {
                    $this->belowWarningCheck($cloudBase, $midWarning, $highWarning, $cloudChunk);
                }
            }
        }
    }

    private function validateVisibility()
    {
        $midWarning = $this->airport->getMidWarningVis();
        $highWarning = $this->airport->getHighWarningVis();

        /** @var Visibility $visibility */
        $visibility = $this->airport->getDecodedMetar()->getVisibility();

        $visDistance = $visibility->getVisibility()->getConvertedValue('m');
        $visChunk = $visibility->getChunk();

        $this->belowWarningCheck($visDistance, $midWarning, $highWarning, $visChunk);

    }

    /**
     * @return bool
     */
    private function checkProcessingErrors()
    {
        $airportIcao = $this->airport->getAirportData()->getAirportIcao();
        $rawMetar = $this->airport->getRawMetar();
        $decodingExceptions = $this->airport->getDecodedMetar()->getDecodingExceptions();
        $badExceptions = array('SurfaceWindChunkDecoder');

        if (!$rawMetar) {
            $this->validatedWeather->setWeatherStatus(0);
            $this->weatherLogger->warning($airportIcao." had NO METAR: '".$rawMetar."'");

            return false;
        }

        if ($decodingExceptions) {
            /** @var ChunkDecoderException $exception */
            foreach ($decodingExceptions as $exception) {
                if (in_array($exception->getChunkDecoder(), $badExceptions)) {
                    $this->validatedWeather->setWeatherStatus(0);
                    $this->weatherLogger->warning(
                        $airportIcao.' had '.$exception->getChunkDecoder().": '".$rawMetar."'"
                    );

                    return false;
                }
            }
        }

        return true;
    }
}

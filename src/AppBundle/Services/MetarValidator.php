<?php

namespace AppBundle\Services;

use AppBundle\Entity\MonitoredAirports;
use AppBundle\Entity\ValidatedWeather;
use AppBundle\Entity\ValidatorWarning;
use MetarDecoder\Entity\SurfaceWind;
use MetarDecoder\Entity\CloudLayer;
use MetarDecoder\Entity\Visibility;
use Symfony\Bridge\Monolog\Logger;
use MetarDecoder\Exception\ChunkDecoderException;

class MetarValidator
{
    /**
     * @var MonitoredAirports
     */
    private $airport;

    /**
     * @var ValidatedWeather
     */
    private $validatedMetar;

    /**
     * @var Logger
     */
    private $weatherLogger;

    public function __construct(Logger $weatherLogger)
    {
        $this->weatherLogger = $weatherLogger;
    }

    /**
     * @return ValidatedWeather
     */
    public function validate(MonitoredAirports $airport)
    {
        $this->setAirport($airport);
        $this->validatedMetar = new ValidatedWeather();

        if (!$this->checkProcessingErrors()) {
            return $this->validatedMetar;
        }

        $this->validateWind();
        if ($this->airport->getDecodedMetar()->getCavok() != true) {
            if (count($this->airport->getDecodedMetar()->getClouds()) > 0) {
                $this->validateCeiling();
            }

            $this->validateVisibility();
        }

        return $this->validatedMetar;
    }

    /**
     * @param MonitoredAirports $airport
     */
    private function setAirport(MonitoredAirports $airport)
    {
        $this->airport = $airport;
    }

    private function validateWind($midWarning = null, $highWarning = null)
    {
        if ($highWarning === null) {
            $highWarning = $this->airport->getHighWarningWind();
        }

        /*
         * Turns our we only want to check if Wind exceeds high warning...
         * Understood that too late into dev, hence the quick fix.
         * Sorry.
         */
        $midWarning = $highWarning;

        $surfaceWind = $this->airport->getDecodedMetar()->getSurfaceWind();
        if ($surfaceWind) {
            /* @var SurfaceWind $surfaceWind */
            $surfaceWindChunk = $surfaceWind->getChunk();
            $knots = $surfaceWind->getMeanSpeed()->getConvertedValue('kt');
            $gustKnotsValue = $surfaceWind->getSpeedVariations();

            if (isset($gustKnotsValue)) {
                $knots = $gustKnotsValue->getConvertedValue('kt');
            }

            $this->exceedsWarningCheck(
                $knots,
                $midWarning,
                $highWarning,
                $surfaceWindChunk
            );
        }

        return $this->validatedMetar;
    }

    /**
     * @param $referenceValue
     * @param $midWarning
     * @param $highWarning
     * @param $chunk
     *
     * @return ValidatedWeather
     */
    private function exceedsWarningCheck(
        $referenceValue,
        $midWarning,
        $highWarning,
        $chunk
    ) {
        $metarStatus = $this->exceedsValueCheck($referenceValue, $midWarning, $highWarning);

        if ($metarStatus > 1) {
            $this->generateWarning($chunk, $metarStatus);
        }

        return $this->validatedMetar;
    }

    /**
     * @param $referenceValue
     * @param $midWarning
     * @param $highWarning
     *
     * @return int
     */
    private function exceedsValueCheck(
        $referenceValue,
        $midWarning,
        $highWarning
    ) {

        if ($referenceValue > $highWarning) {
            $metarStatus = 3;
        } elseif ($referenceValue > $midWarning) {
            $metarStatus = 2;
        } else {
            $metarStatus = 1;
        }

        return $metarStatus;
    }

    /**
     * @param $chunk
     * @param $metarStatus
     *
     * @return ValidatedWeather
     */
    private function generateWarning(
        $chunk,
        $metarStatus
    ) {
        $validatorWarning = new ValidatorWarning();
        $validatorWarning->setChunk($chunk);
        $validatorWarning->setWarningLevel($metarStatus);
        $this->validatedMetar->setWeatherStatus($metarStatus);
        $this->validatedMetar->addWarning($validatorWarning);

        return $this->validatedMetar;
    }

    private function validateCeiling(
        $midWarning = null,
        $highWarning = null
    ) {
        $ceilingClouds = array('BKN', 'OVC', 'VV');

        if ($midWarning === null) {
            $midWarning = $this->airport->getMidWarningCeiling();
        }

        if ($highWarning === null) {
            $highWarning = $this->airport->getHighWarningCeiling();
        }

        /** @var CloudLayer[] $clouds */
        $clouds = $this->airport->getDecodedMetar()->getClouds();

        foreach ($clouds as $cloud) {
            if ($cloud->getBaseHeight()) {
                $cloudChunk = $cloud->getChunk();
                $cloudAmount = $cloud->getAmount();
                $cloudBase = $cloud->getBaseHeight()->getConvertedValue('ft');

                if (in_array($cloudAmount, $ceilingClouds)) {
                    $this->belowWarningCheck($cloudBase, $midWarning, $highWarning, $cloudChunk);
                }
            }
        }

        return $this->validatedMetar;
    }

    /**
     * @param $referenceValue
     * @param $midWarning
     * @param $highWarning
     * @param $chunk
     *
     * @return ValidatedWeather
     */
    private function belowWarningCheck(
        $referenceValue,
        $midWarning,
        $highWarning,
        $chunk
    ) {
        $metarStatus = $this->belowValueCheck($referenceValue, $midWarning, $highWarning);

        if ($metarStatus > 1) {
            $this->generateWarning($chunk, $metarStatus);
        }

        return $this->validatedMetar;
    }

    /**
     * @param $referenceValue
     * @param $midWarning
     * @param $highWarning
     *
     * @return int
     */
    private function belowValueCheck(
        $referenceValue,
        $midWarning,
        $highWarning
    ) {
        if ($referenceValue < $highWarning) {
            $metCondition = 3;
        } elseif ($referenceValue < $midWarning) {
            $metCondition = 2;
        } else {
            $metCondition = 1;
        }

        return $metCondition;
    }

    private function validateVisibility(
        $midWarning = null,
        $highWarning = null
    ) {
        if ($midWarning === null) {
            $midWarning = $this->airport->getMidWarningVis();
        }

        if ($highWarning === null) {
            $highWarning = $this->airport->getHighWarningVis();
        }

        /** @var Visibility $visibility */
        $visibility = $this->airport->getDecodedMetar()->getVisibility();

        $visDistance = $visibility->getVisibility()->getConvertedValue('m');
        $visChunk = $visibility->getChunk();

        $this->belowWarningCheck($visDistance, $midWarning, $highWarning, $visChunk);

        return $this->validatedMetar;
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
            $this->validatedMetar->setWeatherStatus(0);
            $this->weatherLogger->warning($airportIcao." had NO METAR: '".$rawMetar."'");

            return false;
        }

        if ($decodingExceptions) {
            /** @var ChunkDecoderException $exception */
            foreach ($decodingExceptions as $exception) {
                if (in_array($exception->getChunkDecoder(), $badExceptions)) {
                    $this->validatedMetar->setWeatherStatus(0);
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

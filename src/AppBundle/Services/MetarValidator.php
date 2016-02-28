<?php

namespace AppBundle\Services;

use AppBundle\Entity\Airports;
use AppBundle\Entity\ValidatedMetar;
use AppBundle\Entity\ValidatorWarning;
use MetarDecoder\Entity\DecodedMetar;
use MetarDecoder\Entity\SurfaceWind;
use MetarDecoder\Entity\CloudLayer;
use MetarDecoder\Entity\Visibility;


class MetarValidator
{
    private $airport;
    private $decodedMetar;
    private $validatedMetar;

    public function __construct(Airports $airport, DecodedMetar $decodedMetar)
    {
        $this->airport = $airport;
        $this->decodedMetar = $decodedMetar;
        $this->validatedMetar = new ValidatedMetar();
    }

    /**
     * @return ValidatedMetar
     */
    public function validate()
    {
        $this->validatedMetar = $this->validateWind();
        if($this->decodedMetar->getCavok() != TRUE){
            $this->validatedMetar = $this->validateCeiling();
            $this->validatedMetar = $this->validateVisibility();
        }

        return $this->validatedMetar;
    }

    private function validateWind($midWarning = null, $highWarning = null)
    {
        if ($midWarning === null) {
            $midWarning = $this->airport->getMidWarningWind();
        }

        if ($highWarning === null) {
            $highWarning = $this->airport->getHighWarningWind();
        }

        // TODO: Check against SurfaceWinds equals NULL
        /** @var SurfaceWind $surfaceWind */
        $surfaceWind = $this->decodedMetar->getSurfaceWind();
        $surfaceWindChunk = $surfaceWind->getChunk();
        $knots = $surfaceWind->getMeanSpeed()->getConvertedValue('kt');
        $gustKnotsValue = $surfaceWind->getSpeedVariations();

        if (isset($gustKnotsValue)) {
            $knots = $gustKnotsValue->getConvertedValue('kt');
        }

        $this->validatedMetar = $this->exceedsWarningCheck(
            $knots,
            $midWarning,
            $highWarning,
            $surfaceWindChunk
        );

        return $this->validatedMetar;
    }

    public function validateCeiling($midWarning = null, $highWarning = null)
    {
        $ceilingClouds = array('BKN', 'OVC', 'VV');

        if ($midWarning === null) {
            $midWarning = $this->airport->getMidWarningCeiling();
        }

        if ($highWarning === null) {
            $highWarning = $this->airport->getHighWarningCeiling();
        }

        /** @var CloudLayer[] $clouds */
        $clouds = $this->decodedMetar->getClouds();

        foreach ($clouds as $cloud) {
            $cloudChunk = $cloud->getChunk();
            $cloudAmount = $cloud->getAmount();
            $cloudBase = $cloud->getBaseHeight()->getConvertedValue('ft');

            if (in_array($cloudAmount, $ceilingClouds)) {
                $this->validatedMetar = $this->belowWarningCheck($cloudBase, $midWarning, $highWarning, $cloudChunk);
            }
        }

        return $this->validatedMetar;
    }


    public function validateVisibility($midWarning = null, $highWarning = null)
    {
        if ($midWarning === null) {
            $midWarning = $this->airport->getMidWarningVis();
        }

        if ($highWarning === null) {
            $highWarning = $this->airport->getHighWarningVis();
        }

        /** @var Visibility $visibility */
        $visibility = $this->decodedMetar->getVisibility();

        $visDistance = $visibility->getVisibility()->getConvertedValue('m');
        $visChunk = $visibility->getChunk();

        $this->validatedMetar = $this->belowWarningCheck($visDistance, $midWarning, $highWarning, $visChunk);

        return $this->validatedMetar;
    }

    /**
     * @param $referenceValue
     * @param $midWarning
     * @param $highWarning
     * @param $chunk
     * @return ValidatedMetar
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
     * @return int
     */
    private function exceedsValueCheck($referenceValue, $midWarning, $highWarning)
    {
        if ($referenceValue >= $midWarning && $referenceValue < $highWarning) {
            $metarStatus = 2;
        } elseif ($referenceValue >= $highWarning) {
            $metarStatus = 3;
        } else {
            $metarStatus = 1;
        }

        return $metarStatus;
    }

    /**
     * @param $referenceValue
     * @param $midWarning
     * @param $highWarning
     * @param $chunk
     * @return ValidatedMetar
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
     * @return int
     */
    private function belowValueCheck($referenceValue, $midWarning, $highWarning)
    {

        if ($referenceValue < $highWarning) {
            $metCondition = 3;
        } elseif ($referenceValue < $midWarning) {
            $metCondition = 2;
        } else {
            $metCondition = 1;
        }

        return $metCondition;
    }

    /**
     * @param $chunk
     * @param $metarStatus
     * @return ValidatedMetar
     */
    private function generateWarning($chunk, $metarStatus)
    {
        $vw = new ValidatorWarning();
        $vw->setChunk($chunk);
        $vw->setWarningLevel($metarStatus);
        $this->validatedMetar->setMetarStatus($metarStatus);
        $this->validatedMetar->addWarning($vw);

        return $this->validatedMetar;
    }

}
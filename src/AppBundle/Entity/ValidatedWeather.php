<?php

namespace AppBundle\Entity;

class ValidatedWeather
{
    /**
     * @var int
     */
    private $weatherStatus;

    /**
     * @var array
     */
    private $weatherWarnings;

    public function __construct()
    {
        $this->weatherStatus = 1;
        $this->weatherWarnings = array();
    }

    /**
     * @return ValidatorWarning[]
     */
    public function getWeatherWarnings()
    {
        return $this->weatherWarnings;
    }

    /**
     * @param $metarWarning
     *
     * @return $this
     */
    public function addWarning($metarWarning)
    {
        $this->weatherWarnings[] = $metarWarning;

        return $this;
    }

    /**
     * @return int
     */
    public function getWeatherStatus()
    {
        return $this->weatherStatus;
    }

    /**
     * @param int $weatherStatus
     */
    public function setWeatherStatus($weatherStatus)
    {
        if ($weatherStatus > $this->weatherStatus || $weatherStatus == 0) {
            $this->weatherStatus = $weatherStatus;
        }
    }
}

<?php

namespace AppBundle\Entity;

class ValidatedMetar
{

    /**
     * @var int
     */
    private $metarStatus;

    /**
     * @var array
     */
    private $metarWarnings;

    public function __construct()
    {
        $this->metarStatus = 1;
        $this->metarWarnings = array();
    }

    /**
     * @return ValidatorWarning[]
     */
    public function getMetarWarnings()
    {
        return $this->metarWarnings;
    }

    /**
     * @param array $metarWarnings
     */
    public function setMetarWarnings($metarWarnings)
    {
        $this->metarWarnings = $metarWarnings;
    }

    public function addWarning($metarWarning)
    {
        $this->metarWarnings[] = $metarWarning;

        return $this;
    }

    /**
     * @return int
     */
    public function getMetarStatus()
    {
        return $this->metarStatus;
    }

    /**
     * @param int $metarStatus
     */
    public function setMetarStatus($metarStatus)
    {
        if($metarStatus > $this->metarStatus)
        {
            $this->metarStatus = $metarStatus;
        }

    }


}
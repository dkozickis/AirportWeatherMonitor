<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use MetarDecoder\Entity\DecodedMetar;

/**
 * Airports
 *
 * @ORM\Table(name="airports")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\AirportsRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Airports
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="airport_icao", type="string", length=4, unique=true)
     */
    private $airportIcao;

    /**
     * @var bool
     *
     * @ORM\Column(name="active_winter", type="boolean", nullable=true)
     */
    private $activeWinter;

    /**
     * @var bool
     *
     * @ORM\Column(name="alternate_winter", type="boolean", nullable=true)
     */
    private $alternateWinter;

    /**
     * @var bool
     *
     * @ORM\Column(name="active_summer", type="boolean", nullable=true)
     */
    private $activeSummer;

    /**
     * @var bool
     *
     * @ORM\Column(name="alternate_summer", type="boolean", nullable=true)
     */
    private $alternateSummer;

    /**
     * @var int
     *
     * @ORM\Column(name="mid_warning_vis", type="smallint", nullable=true)
     */
    private $midWarningVis;

    /**
     * @var int
     *
     * @ORM\Column(name="mid_warning_ceiling", type="smallint", nullable=true)
     */
    private $midWarningCeiling;

    /**
     * @var int
     *
     * @ORM\Column(name="mid_warning_wind", type="smallint", nullable=true)
     */
    private $midWarningWind;

    /**
     * @var int
     *
     * @ORM\Column(name="high_warning_vis", type="smallint", nullable=true)
     */
    private $highWarningVis;

    /**
     * @var int
     *
     * @ORM\Column(name="high_warning_ceiling", type="smallint", nullable=true)
     */
    private $highWarningCeiling;

    /**
     * @var int
     *
     * @ORM\Column(name="high_warning_wind", type="smallint", nullable=true)
     */
    private $highWarningWind;

    /**
     * @var string
     *
     * @ORM\Column(name="raw_metar", type="string", nullable=true)
     */
    private $rawMetar;
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="raw_metar_date_time", type="datetime", nullable=true)
     */
    private $rawMetarDateTime;
    /**
     * @var DecodedMetar
     */
    private $decodedMetar;
    /**
     * @var ValidatedMetar
     */
    private $validatedWeather;

    /**
     * @return mixed
     */
    public function getValidatedWeather()
    {
        return $this->validatedWeather;
    }

    /**
     * @param mixed $validatedWeather
     */
    public function setValidatedWeather($validatedWeather)
    {
        $this->validatedWeather = $validatedWeather;
    }

    /**
     * @return DecodedMetar
     */
    public function getDecodedMetar()
    {
        return $this->decodedMetar;
    }

    /**
     * @param DecodedMetar $decodedMetar
     */
    public function setDecodedMetar($decodedMetar)
    {
        $this->decodedMetar = $decodedMetar;
    }
    /**
     * @var string
     *
     * @ORM\Column(name="raw_taf", type="string", nullable=true)
     */
    private $rawTaf;
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="raw_taf_date_time", type="datetime", nullable=true)
     */
    private $rawTafDateTime;

    /**
     * @return \DateTime
     */
    public function getRawTafDateTime()
    {
        return $this->rawTafDateTime;
    }

    /**
     * @param \DateTime $rawTafDateTime
     */
    public function setRawTafDateTime($rawTafDateTime)
    {
        $this->rawTafDateTime = $rawTafDateTime;
    }

    /**
     * @return string
     */
    public function getRawMetar()
    {
        return $this->rawMetar;
    }

    /**
     * @param string $rawMetar
     */
    public function setRawMetar($rawMetar)
    {
        $this->rawMetar = $rawMetar;
    }

    /**
     * @return \DateTime
     */
    public function getRawMetarDateTime()
    {
        return $this->rawMetarDateTime;
    }

    /**
     * @param \DateTime $rawMetarDateTime
     */
    public function setRawMetarDateTime($rawMetarDateTime)
    {
        $this->rawMetarDateTime = $rawMetarDateTime;
    }

    /**
     * @return mixed
     */
    public function getRawTaf()
    {
        return $this->rawTaf;
    }

    /**
     * @param mixed $rawTaf
     */
    public function setRawTaf($rawTaf)
    {
        $this->rawTaf = $rawTaf;
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function upperCaseAirportICAOCode(){

        $this->airportIcao = strtoupper($this->airportIcao);

    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get airportIcao
     *
     * @return string
     */
    public function getAirportIcao()
    {
        return $this->airportIcao;
    }

    /**
     * Set airportIcao
     *
     * @param string $airportIcao
     * @return Airports
     */
    public function setAirportIcao($airportIcao)
    {
        $this->airportIcao = $airportIcao;

        return $this;
    }

    /**
     * Get activeWinter
     *
     * @return boolean
     */
    public function getActiveWinter()
    {
        return $this->activeWinter;
    }

    /**
     * Set activeWinter
     *
     * @param boolean $activeWinter
     * @return Airports
     */
    public function setActiveWinter($activeWinter)
    {
        $this->activeWinter = $activeWinter;

        return $this;
    }

    /**
     * Get alternateWinter
     *
     * @return boolean
     */
    public function getAlternateWinter()
    {
        return $this->alternateWinter;
    }

    /**
     * Set alternateWinter
     *
     * @param boolean $alternateWinter
     * @return Airports
     */
    public function setAlternateWinter($alternateWinter)
    {
        $this->alternateWinter = $alternateWinter;

        return $this;
    }

    /**
     * Get activeSummer
     *
     * @return boolean
     */
    public function getActiveSummer()
    {
        return $this->activeSummer;
    }

    /**
     * Set activeSummer
     *
     * @param boolean $activeSummer
     * @return Airports
     */
    public function setActiveSummer($activeSummer)
    {
        $this->activeSummer = $activeSummer;

        return $this;
    }

    /**
     * Get alternateSummer
     *
     * @return boolean
     */
    public function getAlternateSummer()
    {
        return $this->alternateSummer;
    }

    /**
     * Set alternateSummer
     *
     * @param boolean $alternateSummer
     * @return Airports
     */
    public function setAlternateSummer($alternateSummer)
    {
        $this->alternateSummer = $alternateSummer;

        return $this;
    }

    /**
     * Get midWarningVis
     *
     * @return integer
     */
    public function getMidWarningVis()
    {
        return $this->midWarningVis;
    }

    /**
     * Set midWarningVis
     *
     * @param integer $midWarningVis
     * @return Airports
     */
    public function setMidWarningVis($midWarningVis)
    {
        $this->midWarningVis = $midWarningVis;

        return $this;
    }

    /**
     * Get midWarningCeiling
     *
     * @return integer
     */
    public function getMidWarningCeiling()
    {
        return $this->midWarningCeiling;
    }

    /**
     * Set midWarningCeiling
     *
     * @param integer $midWarningCeiling
     * @return Airports
     */
    public function setMidWarningCeiling($midWarningCeiling)
    {
        $this->midWarningCeiling = $midWarningCeiling;

        return $this;
    }

    /**
     * Get midWarningWind
     *
     * @return integer
     */
    public function getMidWarningWind()
    {
        return $this->midWarningWind;
    }

    /**
     * Set midWarningWind
     *
     * @param integer $midWarningWind
     * @return Airports
     */
    public function setMidWarningWind($midWarningWind)
    {
        $this->midWarningWind = $midWarningWind;

        return $this;
    }

    /**
     * Get highWarningVis
     *
     * @return integer
     */
    public function getHighWarningVis()
    {
        return $this->highWarningVis;
    }

    /**
     * Set highWarningVis
     *
     * @param integer $highWarningVis
     * @return Airports
     */
    public function setHighWarningVis($highWarningVis)
    {
        $this->highWarningVis = $highWarningVis;

        return $this;
    }

    /**
     * Get highWarningCeiling
     *
     * @return integer
     */
    public function getHighWarningCeiling()
    {
        return $this->highWarningCeiling;
    }

    /**
     * Set highWarningCeiling
     *
     * @param integer $highWarningCeiling
     * @return Airports
     */
    public function setHighWarningCeiling($highWarningCeiling)
    {
        $this->highWarningCeiling = $highWarningCeiling;

        return $this;
    }

    /**
     * Get highWarningWind
     *
     * @return integer
     */
    public function getHighWarningWind()
    {
        return $this->highWarningWind;
    }

    /**
     * Set highWarningWind
     *
     * @param integer $highWarningWind
     * @return Airports
     */
    public function setHighWarningWind($highWarningWind)
    {
        $this->highWarningWind = $highWarningWind;

        return $this;
    }
}

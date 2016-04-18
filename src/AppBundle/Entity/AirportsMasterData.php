<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class AirportsMasterData.
 *
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 */
class AirportsMasterData
{
    public function __toString()
    {
        return $this->airportIcao;
    }

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
     * @var float
     *
     * @ORM\Column(type="float")
     */
    private $lat;

    /**
     * @var float
     *
     * @ORM\Column(type="float")
     */
    private $lon;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set airportIcao.
     *
     * @param string $airportIcao
     *
     * @return AirportsMasterData
     */
    public function setAirportIcao($airportIcao)
    {
        $this->airportIcao = $airportIcao;

        return $this;
    }

    /**
     * Get airportIcao.
     *
     * @return string
     */
    public function getAirportIcao()
    {
        return $this->airportIcao;
    }

    /**
     * Set lat.
     *
     * @param float $lat
     *
     * @return AirportsMasterData
     */
    public function setLat($lat)
    {
        $this->lat = $lat;

        return $this;
    }

    /**
     * Get lat.
     *
     * @return float
     */
    public function getLat()
    {
        return $this->lat;
    }

    /**
     * Set lon.
     *
     * @param float $lon
     *
     * @return AirportsMasterData
     */
    public function setLon($lon)
    {
        $this->lon = $lon;

        return $this;
    }

    /**
     * Get lon.
     *
     * @return float
     */
    public function getLon()
    {
        return $this->lon;
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function upperCaseAirportICAOCode()
    {
        $this->airportIcao = strtoupper($this->airportIcao);
    }
}

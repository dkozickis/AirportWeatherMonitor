<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class MonitoredPhenomenons
 * @package AppBundle\Entity
 *
 * @ORM\Entity(repositoryClass="AppBundle\Repository\MonitoredPhenomenonsRepository")
 */
class MonitoredPhenomenons
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
     * @var
     *
     * @ORM\Column(name="warning_level", type="string")
     */
    private $warningLevel;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return MonitoredPhenomenons
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPhenomenons()
    {
        return $this->phenomenons;
    }

    /**
     * @param mixed $phenomenons
     * @return MonitoredPhenomenons
     */
    public function setPhenomenons($phenomenons)
    {
        $this->phenomenons = $phenomenons;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getWarningLevel()
    {
        return $this->warningLevel;
    }

    /**
     * @param mixed $warningLevel
     * @return MonitoredPhenomenons
     */
    public function setWarningLevel($warningLevel)
    {
        $this->warningLevel = $warningLevel;

        return $this;
    }

    /**
     * @var
     *
     * @ORM\Column(name="phenomenons", type="text", nullable=true)
     */
    private $phenomenons;
}
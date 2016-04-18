<?php
/**
 * Created by PhpStorm.
 * User: Denis
 * Date: 28/02/16
 * Time: 12:37.
 */
namespace AppBundle\Entity;

class ValidatorWarning
{
    /**
     * @var string
     */
    private $chunk;

    /**
     * @var int
     */
    private $warningLevel;

    /**
     * @return string
     */
    public function getChunk()
    {
        return $this->chunk;
    }

    /**
     * @param string $chunk
     */
    public function setChunk($chunk)
    {
        $this->chunk = $chunk;
    }

    /**
     * @return int
     */
    public function getWarningLevel()
    {
        return $this->warningLevel;
    }

    /**
     * @param int $warningLevel
     */
    public function setWarningLevel($warningLevel)
    {
        $this->warningLevel = $warningLevel;
    }
}

<?php

namespace AppBundle\Helpers;

class WeatherHelper
{
    /**
     * @return bool
     *
     * Function returns 0 or 1.
     * 0 is Winter season in Berlin timezone (no DST).
     * 1 is Summer season in Berlin timezone (DST active).
     * Berlin chosen as reference with normal DST.
     */
    public function getDateSeason($dateTime = null)
    {
        if (!$dateTime) {
            $dateTime = new \DateTime('now', new \DateTimeZone('Europe/Berlin'));
        }

        $season = $dateTime->format('I');

        return (int)$season;
    }

    public function getReferenceTime($difference = 30, $dateTime = null)
    {
        if (!$dateTime) {
            $dateTime = new \DateTime('now', new \DateTimeZone('UTC'));
        }
        $dateTime->modify('-'.$difference.' minutes');

        return $dateTime;
    }
}

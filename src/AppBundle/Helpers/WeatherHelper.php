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
    public function getCurrentSeason()
    {
        $date = new \DateTime('now');
        $date->setTimezone(new \DateTimeZone('Europe/Berlin'));

        $season = $date->format('I');

        return (int) $season;
    }

    public function getReferenceTime($difference = 30)
    {
        $dateTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $dateTime->modify('-'.$difference.' minutes');

        return $dateTime;
    }
}

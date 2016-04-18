<?php

namespace AppBundle\Repository;

use Doctrine\ORM\EntityRepository;
use AppBundle\Entity\MonitoredAirports;

/**
 * AirportsRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class MonitoredAirportsRepository extends EntityRepository
{
    /**
     * @return MonitoredAirports[] array
     */
    public function getSeasonActiveAirports($alternate = 0)
    {
        $airportsArray = [];

        $date = new \DateTime('now');
        $date->setTimezone(new \DateTimeZone('Europe/Berlin'));

        /*
         * Below returns 0 or 1.
         * 0 is Winter season in Berlin timezone (no DST).
         * 1 is Summer season in Berlin timezone (DST active).
         * Berlin chosen as reference with normal DST.
         */
        $season = $date->format('I');

        $qb = $this->createQueryBuilder('a');

        if ($season == 0) {
            $qb->where('a.activeWinter = 1');
            if ($alternate == 1) {
                $qb->andWhere('a.alternateWinter = 1');
            } else {
                $qb->andWhere('a.alternateWinter = 0');
            }
        } elseif ($season == 1) {
            $qb->where('a.activeSummer = 1');
            if ($alternate == 1) {
                $qb->andWhere('a.alternateSummer = 1');
            } else {
                $qb->andWhere('a.alternateSummer = 0');
            }
        }

        $airports = $qb->getQuery()->getResult();

        /** @var MonitoredAirports $airport */
        foreach ($airports as $airport) {
            $airportsArray[$airport->getAirportData()->getAirportIcao()] = $airport;
        }

        return $airportsArray;
    }
}
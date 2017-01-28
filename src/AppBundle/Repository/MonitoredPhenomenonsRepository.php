<?php

namespace AppBundle\Repository;

use Doctrine\ORM\EntityRepository;

class MonitoredPhenomenonsRepository extends EntityRepository
{
    public function getLevelsPhenomenons()
    {
        $levelPhenomenons = array();
        $qb = $this->createQueryBuilder('p');
        $result = $qb->getQuery()->getArrayResult();

        foreach ($result as $key => $value)
        {
            $levelPhenomenons[$value['warningLevel']] = preg_split('/\s+/', $value['phenomenons']);
        }

        return $levelPhenomenons;
    }
}
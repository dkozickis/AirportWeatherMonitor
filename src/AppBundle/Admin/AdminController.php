<?php

namespace AppBundle\Admin;

use JavierEguiluz\Bundle\EasyAdminBundle\Controller\AdminController as BaseAdminController;
use Doctrine\ORM\EntityManager;

/**
 * Class AdminController. Will not test.
 *
 * @codeCoverageIgnore
 */
class AdminController extends BaseAdminController
{
    protected function createMonitoredAirportListQueryBuilder($entityClass, $sortDirection, $sortField)
    {

        /* @var EntityManager */
        $em = $this->get('doctrine')->getManagerForClass($entityClass);

        $queryBuilder = $em->createQueryBuilder()
            ->select(array('entity', 'ad'))
            ->from($entityClass, 'entity')
            ->leftJoin('entity.airportData', 'ad');

        if (null !== $sortField) {
            if ($sortField == 'airportData' || $sortField == 'id') {
                $sortDirection = ($sortField == 'id' && $sortDirection == 'DESC') ? 'ASC' : $sortDirection;
                $queryBuilder->orderBy('ad.airportIcao', $sortDirection);
            } else {
                $queryBuilder->orderBy('entity.'.$sortField, $sortDirection);
            }
        }

        return $queryBuilder;
    }

    protected function createMonitoredAirportSearchQueryBuilder(
        $entityClass, $searchQuery, $searchableFields, $sortField, $sortDirection
    ) {

        /* @var EntityManager */
        $em = $this->get('doctrine')->getManagerForClass($entityClass);

        $queryBuilder = $em->createQueryBuilder()
            ->select(array('entity', 'ad'))
            ->from($entityClass, 'entity')
            ->leftJoin('entity.airportData', 'ad')
            ->orWhere('ad.airportIcao LIKE :query')
            ->setParameter('query', '%'.strtolower($searchQuery).'%');
        
        if (null !== $sortField) {
            if ($sortField == 'airportData') {
                $queryBuilder->orderBy('ad.airportIcao', $sortDirection);
            } else {
                $queryBuilder->orderBy('entity.'.$sortField, $sortDirection);
            }
        }

        return $queryBuilder;
    }
}

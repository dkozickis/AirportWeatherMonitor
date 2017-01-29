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
            ->leftJoin('entity.airportData', 'ad');

        $queryParameters = array();
        foreach ($searchableFields as $name => $metadata) {
            $isNumericField = in_array(
                $metadata['dataType'],
                array('integer', 'number', 'smallint', 'bigint', 'decimal', 'float')
            );
            $isTextField = in_array($metadata['dataType'], array('string', 'text', 'guid'));

            if ($isNumericField && is_numeric($searchQuery)) {
                $queryBuilder->orWhere(sprintf('entity.%s = :exact_query', $name));
                // adding '0' turns the string into a numeric value
                $queryParameters['exact_query'] = 0 + $searchQuery;
            } elseif ($isTextField) {
                $queryBuilder->orWhere(sprintf('entity.%s LIKE :fuzzy_query', $name));
                $queryParameters['fuzzy_query'] = '%'.$searchQuery.'%';

                $queryBuilder->orWhere(sprintf('entity.%s IN (:words_query)', $name));
                $queryParameters['words_query'] = explode(' ', $searchQuery);
            }
        }

        if (0 !== count($queryParameters)) {
            $queryBuilder->setParameters($queryParameters);
        }

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

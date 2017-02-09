<?php

namespace AppBundle\Admin;

use JavierEguiluz\Bundle\EasyAdminBundle\Controller\AdminController as BaseAdminController;
use Doctrine\ORM\EntityManager;
use JavierEguiluz\Bundle\EasyAdminBundle\Event\EasyAdminEvents;

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
        $entityClass,
        $searchQuery,
        $searchableFields,
        $sortField,
        $sortDirection
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

    protected function createMonitoredAirportQuickListQueryBuilder($entityClass, $sortDirection, $sortField)
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

    protected function createMonitoredAirportQuickSearchQueryBuilder(
        $entityClass,
        $searchQuery,
        $searchableFields,
        $sortField,
        $sortDirection
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

    protected function findAll(
        $entityClass,
        $page = 1,
        $maxPerPage = 15,
        $sortField = null,
        $sortDirection = null,
        $dqlFilter = null
    ) {
        if (empty($sortDirection) || !in_array(strtoupper($sortDirection), array('ASC', 'DESC'))) {
            $sortDirection = 'DESC';
        }

        if ($this->entity['name'] == 'MonitoredAirportQuick') {
            $maxPerPage = 1000;
        }

        $queryBuilder = $this->executeDynamicMethod(
            'create<EntityName>ListQueryBuilder',
            array($entityClass, $sortDirection, $sortField, $dqlFilter)
        );

        $this->dispatch(
            EasyAdminEvents::POST_LIST_QUERY_BUILDER,
            array(
                'query_builder' => $queryBuilder,
                'sort_field' => $sortField,
                'sort_direction' => $sortDirection,
            )
        );

        return $this->get('easyadmin.paginator')->createOrmPaginator($queryBuilder, $page, $maxPerPage);
    }

    /**
     * @param string $methodNamePattern
     * @param array $arguments
     *
     * @return mixed
     */
    protected function executeDynamicMethod($methodNamePattern, array $arguments = array())
    {
        $methodName = str_replace('<EntityName>', $this->entity['name'], $methodNamePattern);

        if (!is_callable(array($this, $methodName))) {
            $methodName = str_replace('<EntityName>', '', $methodNamePattern);
        }

        return call_user_func_array(array($this, $methodName), $arguments);
    }
}

<?php

namespace Mautic\LeadBundle\Entity;

use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\Serializer;

trait TimelineTrait
{
    /**
     * @param QueryBuilder $query                 DBAL QueryBuilder
     * @param array<mixed> $options               Query optons from LeadTimelineEvent
     * @param string       $eventNameColumn       Name of column to sort event name by
     * @param string       $timestampColumn       Name of column to sort timestamp by
     * @param array<mixed> $serializedColumns     Array of columns to unserialize
     * @param array<mixed> $dateTimeColumns       Array of columns to be converted to \DateTime
     * @param mixed|null   $resultsParserCallback Callback to custom parse results
     *
     * @return array<mixed>
     */
    private function getTimelineResults(
        QueryBuilder $query,
        array $options,
        $eventNameColumn,
        $timestampColumn,
        $serializedColumns = [],
        $dateTimeColumns = [],
        $resultsParserCallback = null
    ) {
        if (!empty($options['unitCounts'])) {
            [$tablePrefix, $column] = explode('.', $timestampColumn);

            // Get counts grouped by unit based on date range
            /** @var ChartQuery $cq */
            $cq = $options['chartQuery'];
            $cq->modifyTimeDataQuery($query, $column, $tablePrefix);
            $cq->applyDateFilters($query, $column, $tablePrefix);
            $data = $query->execute()->fetchAllAssociative();

            return $cq->completeTimeData($data);
        }

        if (!empty($options['fromDate']) && !empty($options['toDate'])) {
            $query->andWhere($timestampColumn.' BETWEEN :dateFrom AND :dateTo')
                ->setParameter('dateFrom', $options['fromDate']->format('Y-m-d H:i:s'))
                ->setParameter('dateTo', $options['toDate']->format('Y-m-d H:i:s'));
        } elseif (!empty($options['fromDate'])) {
            $query->andWhere($query->expr()->gte($timestampColumn, ':dateFrom'))
                ->setParameter('dateFrom', $options['fromDate']->format('Y-m-d H:i:s'));
        } elseif (!empty($options['toDate'])) {
            $query->andWhere($query->expr()->lte($timestampColumn, ':dateTo'))
                ->setParameter('dateTo', $options['toDate']->format('Y-m-d H:i:s'));
        }

        if (isset($options['leadIds'])) {
            $leadColumn = $this->getTableAlias().'.lead_id';
            $query->addSelect($leadColumn);
            $query->andWhere(
                $query->expr()->in($leadColumn, $options['leadIds'])
            );
        }

        if (isset($options['order'])) {
            [$orderBy, $orderByDir] = $options['order'];

            $orderBy = match ($orderBy) {
                'eventLabel' => $eventNameColumn,
                default      => $timestampColumn,
            };

            $query->orderBy($orderBy, $orderByDir);
        }

        if (!empty($options['limit'])) {
            $query->setMaxResults($options['limit']);
            if (!empty($options['start'])) {
                $query->setFirstResult($options['start']);
            }
        }

        $results = $query->executeQuery()->fetchAllAssociative();

        if (!empty($serializedColumns) || !empty($dateTimeColumns) || is_callable($resultsParserCallback)) {
            // Convert to array or \DateTime since we're using DBAL here
            foreach ($results as &$result) {
                foreach ($serializedColumns as $col) {
                    if (isset($result[$col])) {
                        $result[$col] = (null == $result[$col]) ? [] : Serializer::decode($result[$col]);
                    }
                }

                foreach ($dateTimeColumns as $col) {
                    if (isset($result[$col]) && !empty($result[$col])) {
                        $dt           = new DateTimeHelper($result[$col], 'Y-m-d H:i:s', 'UTC');
                        $result[$col] = $dt->getLocalDateTime();
                        unset($dt);
                    }
                }

                if (is_callable($resultsParserCallback)) {
                    $resultsParserCallback($result);
                }
            }
        }

        if (!empty($options['paginated'])) {
            // Get a total count along with results
            $query->resetQueryParts(['select', 'orderBy'])
                ->setFirstResult(0)
                ->setMaxResults(null)
                ->select('count(*)');

            $total = $query->executeQuery()->fetchOne();

            return [
                'total'   => $total,
                'results' => $results,
            ];
        }

        return $results;
    }
}

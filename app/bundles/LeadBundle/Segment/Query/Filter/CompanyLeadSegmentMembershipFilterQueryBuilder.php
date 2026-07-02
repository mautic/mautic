<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Segment\Query\Filter;

use Doctrine\DBAL\ArrayParameterType;
use Mautic\LeadBundle\Event\SegmentDictionaryGenerationEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\OperatorOptions;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Filters companies based on their primary contact's lead segment membership.
 *
 * This filter checks if any of the company's primary contacts are members of specified lead segments.
 */
final class CompanyLeadSegmentMembershipFilterQueryBuilder extends BaseFilterQueryBuilder implements EventSubscriberInterface
{
    public static function getServiceId(): string
    {
        return self::class;
    }

    public function applyQuery(QueryBuilder $queryBuilder, ContactSegmentFilter $filter): QueryBuilder
    {
        if ('contactsegmentmembership' !== $filter->getField()) {
            throw new \RuntimeException('This filter only supports contactsegmentmembership field');
        }

        $companiesTableAlias = $this->ensureStringTableAlias($queryBuilder, MAUTIC_TABLE_PREFIX.'companies');

        // Handle empty/notEmpty operators
        if (OperatorOptions::EMPTY === $filter->getOperator() || 'notEmpty' === $filter->getOperator()) {
            $sub            = $queryBuilder->createQueryBuilder();
            $cl             = $this->generateRandomParameterName();
            $lll            = $this->generateRandomParameterName();
            $isPrimaryParam = $this->generateRandomParameterName();

            $sub->select('1')
                ->from(MAUTIC_TABLE_PREFIX.'companies_leads', $cl)
                ->join($cl, MAUTIC_TABLE_PREFIX.'lead_lists_leads', $lll, $lll.'.lead_id = '.$cl.'.lead_id')
                ->where($sub->expr()->eq($cl.'.company_id', $companiesTableAlias.'.id'))
                ->andWhere($sub->expr()->eq($cl.'.is_primary', ':'.$isPrimaryParam));

            $queryBuilder->setParameter($isPrimaryParam, 1);

            $expr = (OperatorOptions::EMPTY === $filter->getOperator())
                ? $queryBuilder->expr()->notExists($sub->getSQL())
                : $queryBuilder->expr()->exists($sub->getSQL());

            $queryBuilder->addLogic($expr, $filter->getGlue());

            return $queryBuilder;
        }

        // Handle in/notIn operators with specific segment IDs
        $segmentIds  = $this->ensureNumericSegmentIds($filter->getParameterValue());
        $operator    = $filter->getOperator();
        $isExclusion = in_array($operator, ['notExists', 'notIn'], true);

        $sub             = $queryBuilder->createQueryBuilder();
        $cl              = $this->generateRandomParameterName();
        $lll             = $this->generateRandomParameterName();
        $segmentIdsParam = $this->generateRandomParameterName();
        $isPrimaryParam  = $this->generateRandomParameterName();

        $sub->select('1')
            ->from(MAUTIC_TABLE_PREFIX.'companies_leads', $cl)
            ->join($cl, MAUTIC_TABLE_PREFIX.'lead_lists_leads', $lll, $lll.'.lead_id = '.$cl.'.lead_id')
            ->where($sub->expr()->eq($cl.'.company_id', $companiesTableAlias.'.id'))
            ->andWhere($sub->expr()->eq($cl.'.is_primary', ':'.$isPrimaryParam))
            ->andWhere($sub->expr()->in($lll.'.leadlist_id', ':'.$segmentIdsParam));

        $queryBuilder->setParameter($segmentIdsParam, $segmentIds, ArrayParameterType::STRING);
        $queryBuilder->setParameter($isPrimaryParam, 1);

        $expr = $isExclusion
            ? $queryBuilder->expr()->notExists($sub->getSQL())
            : $queryBuilder->expr()->exists($sub->getSQL());

        $queryBuilder->addLogic($expr, $filter->getGlue());

        return $queryBuilder;
    }

    public function onAddFilter(SegmentDictionaryGenerationEvent $event): void
    {
        $event->addTranslation('contactsegmentmembership', [
            'type' => self::getServiceId(),
        ]);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LeadEvents::SEGMENT_DICTIONARY_ON_GENERATE => 'onAddFilter',
        ];
    }

    private function ensureStringTableAlias(QueryBuilder $queryBuilder, string $tableName): string
    {
        $alias = $queryBuilder->getTableAlias($tableName);
        if (!is_string($alias)) {
            throw new \RuntimeException("Could not get table alias for table: $tableName");
        }

        return $alias;
    }

    /**
     * @return array<int>
     */
    private function ensureNumericSegmentIds(mixed $segmentIds): array
    {
        if (!is_array($segmentIds)) {
            if (!is_numeric($segmentIds)) {
                throw new \RuntimeException('Segment IDs must be numeric or array of numeric values');
            }

            return [(int) $segmentIds];
        }

        return array_map(function ($id): int {
            if (!is_numeric($id)) {
                throw new \RuntimeException('All segment IDs must be numeric');
            }

            return (int) $id;
        }, $segmentIds);
    }
}

<?php

namespace Mautic\LeadBundle\Segment\Query\Filter;

use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\ContactSegmentFilterFactory;
use Mautic\LeadBundle\Segment\Exception\SegmentNotFoundException;
use Mautic\LeadBundle\Segment\Exception\SegmentQueryException;
use Mautic\LeadBundle\Segment\OperatorOptions;
use Mautic\LeadBundle\Segment\Query\ContactSegmentQueryBuilder;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use Mautic\LeadBundle\Segment\Query\QueryException;
use Mautic\LeadBundle\Segment\RandomParameterName;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SegmentReferenceFilterQueryBuilder extends BaseFilterQueryBuilder
{
    public function __construct(
        RandomParameterName $randomParameterNameService,
        private ContactSegmentQueryBuilder $leadSegmentQueryBuilder,
        private EntityManager $entityManager,
        private ContactSegmentFilterFactory $leadSegmentFilterFactory,
        EventDispatcherInterface $dispatcher,
    ) {
        parent::__construct($randomParameterNameService, $dispatcher);
    }

    public static function getServiceId(): string
    {
        return 'mautic.lead.query.builder.special.leadlist';
    }

    /**
     * @throws SegmentNotFoundException
     * @throws SegmentQueryException
     * @throws \Doctrine\DBAL\Exception
     * @throws QueryException
     */
    public function applyQuery(QueryBuilder $queryBuilder, ContactSegmentFilter $filter): QueryBuilder
    {
        $leadsTableAlias = $queryBuilder->getTableAlias(MAUTIC_TABLE_PREFIX.'leads');
        $segmentIds      = $filter->getParameterValue();

        if (!is_array($segmentIds)) {
            $segmentIds = [(int) $segmentIds];
        }

        $logic = [];

        $filterArray      = $filter->contactSegmentFilterCrate->getArray();
        $originalOperator = $filterArray['operator'];
        $exclusion        = in_array($filter->getOperator(), ['notExists', 'notIn']);

        foreach ($segmentIds as $segmentId) {
            /** @var LeadList $contactSegment */
            $contactSegment = $this->entityManager->getRepository(LeadList::class)->find($segmentId);
            if (!$contactSegment) {
                throw new SegmentNotFoundException(sprintf('Segment %d used in the filter does not exist anymore.', $segmentId));
            }

            $filters = $this->leadSegmentFilterFactory->getSegmentFilters($contactSegment);

            $segmentQueryBuilder       = $this->leadSegmentQueryBuilder->assembleContactsSegmentQueryBuilder($contactSegment->getId(), $filters, true);
            $subSegmentLeadsTableAlias = $segmentQueryBuilder->getTableAlias(MAUTIC_TABLE_PREFIX.'leads');
            $segmentQueryBuilder->resetQueryParts(['select'])->select('null');

            //  If the segment contains no filters; it means its for manually subscribed only
            if (count($filters)) {
                $segmentQueryBuilder = $this->leadSegmentQueryBuilder->addManuallyUnsubscribedQuery($segmentQueryBuilder, (int) $contactSegment->getId());
            }

            $segmentQueryBuilder = $this->leadSegmentQueryBuilder->addManuallySubscribedQuery($segmentQueryBuilder, (int) $contactSegment->getId());

            $parameters = $segmentQueryBuilder->getParameters();
            foreach ($parameters as $key => $value) {
                $queryBuilder->setParameter($key, $value);
            }

            $this->leadSegmentQueryBuilder->queryBuilderGenerated($contactSegment, $segmentQueryBuilder);

            $segmentQueryWherePart = $segmentQueryBuilder->getQueryPart('where');
            $segmentQueryBuilder->where("$leadsTableAlias.id = $subSegmentLeadsTableAlias.id");
            $segmentQueryBuilder->andWhere($segmentQueryWherePart);

            if ($exclusion) {
                $expression = $queryBuilder->expr()->notExists($segmentQueryBuilder->getSQL());
            } else {
                $expression = $queryBuilder->expr()->exists($segmentQueryBuilder->getSQL());
            }

            $logic[] = $expression;
        }

        /**
         * The "exists" is controlled by the $exclusion above.
         * Including all: and(exists)
         * Excluding all: not(and(exists))
         * Including any: or(exists)
         * Excluding any: and(not exists).
         */
        if (OperatorOptions::INCLUDING_ANY !== $originalOperator) {
            $logic = new CompositeExpression(CompositeExpression::TYPE_AND, $logic);
        } else {
            $logic = new CompositeExpression(CompositeExpression::TYPE_OR, $logic);
        }

        if (OperatorOptions::EXCLUDING_ALL === $originalOperator) {
            $logic = (string) new Expr\Func('NOT', (string) $logic);
        }

        $queryBuilder->addLogic($logic, $filter->getGlue());

        return $queryBuilder;
    }
}

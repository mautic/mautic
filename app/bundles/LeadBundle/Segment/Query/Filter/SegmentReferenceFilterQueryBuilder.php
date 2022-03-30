<?php

namespace Mautic\LeadBundle\Segment\Query\Filter;

use Doctrine\ORM\EntityManager;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\ContactSegmentFilterFactory;
use Mautic\LeadBundle\Segment\Exception\SegmentNotFoundException;
use Mautic\LeadBundle\Segment\Exception\SegmentQueryException;
use Mautic\LeadBundle\Segment\Query\ContactSegmentQueryBuilder;
use Mautic\LeadBundle\Segment\Query\Expression\CompositeExpression;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use Mautic\LeadBundle\Segment\RandomParameterName;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SegmentReferenceFilterQueryBuilder extends BaseFilterQueryBuilder
{
    /**
     * @var ContactSegmentQueryBuilder
     */
    private $leadSegmentQueryBuilder;

    /**
     * @var ContactSegmentFilterFactory
     */
    private $leadSegmentFilterFactory;

    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(
        RandomParameterName $randomParameterNameService,
        ContactSegmentQueryBuilder $leadSegmentQueryBuilder,
        EntityManager $entityManager,
        ContactSegmentFilterFactory $leadSegmentFilterFactory,
        EventDispatcherInterface $dispatcher
    ) {
        parent::__construct($randomParameterNameService, $dispatcher);

        $this->leadSegmentQueryBuilder  = $leadSegmentQueryBuilder;
        $this->leadSegmentFilterFactory = $leadSegmentFilterFactory;
        $this->entityManager            = $entityManager;
    }

    /**
     * @return string
     */
    public static function getServiceId()
    {
        return 'mautic.lead.query.builder.special.leadlist';
    }

    /**
     * @return QueryBuilder
     *
     * @throws \Doctrine\DBAL\Query\QueryException
     * @throws \Exception
     * @throws SegmentQueryException
     */
    public function applyQuery(QueryBuilder $queryBuilder, ContactSegmentFilter $filter)
    {
        $segmentIds = $filter->getParameterValue();

        if (!is_array($segmentIds)) {
            $segmentIds = [intval($segmentIds)];
        }

        $logic     = [];
        $exclusion = false;
        foreach ($segmentIds as $segmentId) {
            $exclusion = in_array($filter->getOperator(), ['notExists', 'notIn']);

            /** @var LeadList $contactSegment */
            $contactSegment = $this->entityManager->getRepository('MauticLeadBundle:LeadList')->find($segmentId);
            if (!$contactSegment) {
                throw new SegmentNotFoundException(sprintf('Segment %d used in the filter does not exist anymore.', $segmentId));
            }

            $filters = $this->leadSegmentFilterFactory->getSegmentFilters($contactSegment);

            $segmentQueryBuilder = $this->leadSegmentQueryBuilder->assembleContactsSegmentQueryBuilder($contactSegment->getId(), $filters);

            //  If the segment contains no filters; it means its for manually subscribed only
            if (count($filters)) {
                $segmentQueryBuilder = $this->leadSegmentQueryBuilder->addManuallyUnsubscribedQuery($segmentQueryBuilder, $contactSegment->getId());
            }

            $segmentQueryBuilder = $this->leadSegmentQueryBuilder->addManuallySubscribedQuery($segmentQueryBuilder, $contactSegment->getId());

            $parameters = $segmentQueryBuilder->getParameters();
            foreach ($parameters as $key => $value) {
                $queryBuilder->setParameter($key, $value);
            }

            $this->leadSegmentQueryBuilder->queryBuilderGenerated($contactSegment, $segmentQueryBuilder);

            $segmentAlias = $this->generateRandomParameterName();
            if ($exclusion) {
                $queryBuilder->leftJoin('l', '('.$segmentQueryBuilder->getSQL().') ', $segmentAlias, $segmentAlias.'.id = l.id');
                $expression = $queryBuilder->expr()->isNull($segmentAlias.'.id');
            } else {
                $queryBuilder->leftJoin('l', '('.$segmentQueryBuilder->getSQL().') ', $segmentAlias, $segmentAlias.'.id = l.id');
                $expression = $queryBuilder->expr()->isNotNull($segmentAlias.'.id');
            }
            $queryBuilder->addSelect($segmentAlias.'.id as '.$segmentAlias.'_id');

            $logic[] = $expression;
        }

        if ($exclusion) {
            $queryBuilder->addLogic(new CompositeExpression(CompositeExpression::TYPE_AND, $logic), $filter->getGlue());
        } else {
            $queryBuilder->addLogic(new CompositeExpression(CompositeExpression::TYPE_OR, $logic), $filter->getGlue());
        }

        return $queryBuilder;
    }
}

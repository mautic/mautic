<?php
/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Segment\Query\Filter;

use Doctrine\ORM\EntityManager;
use Mautic\LeadBundle\Segment\LeadSegmentFilter;
use Mautic\LeadBundle\Segment\LeadSegmentFilterFactory;
use Mautic\LeadBundle\Segment\Query\LeadSegmentQueryBuilder;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use Mautic\LeadBundle\Segment\RandomParameterName;

/**
 * Class LeadListFilterQueryBuilder.
 */
class LeadListFilterQueryBuilder extends BaseFilterQueryBuilder
{
    /**
     * @var LeadSegmentQueryBuilder
     */
    private $leadSegmentQueryBuilder;

    /**
     * @var LeadSegmentFilterFactory
     */
    private $leadSegmentFilterFactory;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * LeadListFilterQueryBuilder constructor.
     *
     * @param RandomParameterName      $randomParameterNameService
     * @param LeadSegmentQueryBuilder  $leadSegmentQueryBuilder
     * @param EntityManager            $entityManager
     * @param LeadSegmentFilterFactory $leadSegmentFilterFactory
     */
    public function __construct(
        RandomParameterName $randomParameterNameService,
        LeadSegmentQueryBuilder $leadSegmentQueryBuilder,
        EntityManager $entityManager,
        LeadSegmentFilterFactory $leadSegmentFilterFactory
    ) {
        parent::__construct($randomParameterNameService);

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
     * @param QueryBuilder      $queryBuilder
     * @param LeadSegmentFilter $filter
     *
     * @return QueryBuilder
     */
    public function applyQuery(QueryBuilder $queryBuilder, LeadSegmentFilter $filter)
    {
        $segmentIds = $filter->getParameterValue();

        if (!is_array($segmentIds)) {
            $segmentIds = [intval($segmentIds)];
        }

        $leftIds  = [];
        $innerIds = [];

        foreach ($segmentIds as $segmentId) {
            $ids[]     = $segmentId;
            dump($filter->getOperator());

            $exclusion = in_array($filter->getOperator(), ['notExists', 'notIn']);
            if ($exclusion) {
                $leftIds[] = $segmentId;
            } else {
                $innerIds[] = $segmentId;
            }
        }

        if (count($leftIds)) {
            $leftAlias = $this->generateRandomParameterName();
            $queryBuilder->leftJoin('l', MAUTIC_TABLE_PREFIX.'lead_lists_leads', $leftAlias,
                                    $queryBuilder->expr()->andX(
                                        $queryBuilder->expr()->in('l.id', $leftIds),
                                        $queryBuilder->expr()->eq('l.id', $leftAlias.'.lead_id'))
            );

            // do not contact restriction, those who are do no to contact are not considered for exclusion
            $dncAlias = $this->generateRandomParameterName();

            $queryBuilder->leftJoin($leftAlias, MAUTIC_TABLE_PREFIX.'lead_donotcontact', $dncAlias, $dncAlias.'.lead_id = '.$leftAlias.'.lead_id');

            $expression = $queryBuilder->expr()->andX(
                $queryBuilder->expr()->eq($dncAlias.'.reason', 1),
                $queryBuilder->expr()
                             ->eq($dncAlias.'.channel', 'email')    //@todo  I really need to verify that this is the value to use, where is the email coming from?
            );

            $queryBuilder->addJoinCondition($dncAlias, $expression);

//
//            $exprParameter    = $this->generateRandomParameterName();
//            $channelParameter = $this->generateRandomParameterName();
//
//            $expression = $queryBuilder->expr()->andX(
//                $queryBuilder->expr()->eq($tableAlias.'.reason', ":$exprParameter"),
//                $queryBuilder->expr()
//                             ->eq($tableAlias.'.channel', ":$channelParameter")
//            );
//
//            $queryBuilder->addJoinCondition($tableAlias, $expression);
//
//            $queryType = $filter->getOperator() === 'eq' ? 'isNull' : 'isNotNull';
//
//            $queryBuilder->andWhere($queryBuilder->expr()->$queryType($tableAlias.'.id'));

            $queryBuilder->andWhere(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->isNull($leftAlias.'.lead_id'),
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->isNotNull($leftAlias.'.lead_id'),
                        $queryBuilder->expr()->isNotNull($dncAlias.'.lead_id')
                    )
                )
            );
        }

        if (count($innerIds)) {
            $innerAlias = $this->generateRandomParameterName();
            $queryBuilder->innerJoin('l', MAUTIC_TABLE_PREFIX.'lead_lists_leads', $innerAlias,
                                    $queryBuilder->expr()->andX(
                                        $queryBuilder->expr()->in('l.id', $innerIds),
                                        $queryBuilder->expr()->eq('l.id', $innerAlias.'.lead_id'))
            );
        }

        return $queryBuilder;
    }
}

$sql ="ELECT
					null
				FROM
					mautic_leads nlUhHOxv
				LEFT JOIN mautic_lead_lists_leads dVzaIsGt ON
					dVzaIsGt.lead_id = nlUhHOxv.id
					AND dVzaIsGt.leadlist_id = 7
				WHERE
					(
						(
							EXISTS(
								SELECT
									null
								FROM
									mautic_lead_donotcontact MnuDztmo
								WHERE
									(
										MnuDztmo.reason = 1
									)
									AND(
										MnuDztmo.lead_id = l.id
									)
									AND(
										MnuDztmo.channel = 'email'
									)
							)
						)
						OR(
							dVzaIsGt.manually_added = '1'
						)
					)
					AND(
						nlUhHOxv.id = l.id
					)
					AND(
						(
							dVzaIsGt.manually_removed IS NULL
						)
						OR(
							dVzaIsGt.manually_removed = ''
						)
					)
			)";

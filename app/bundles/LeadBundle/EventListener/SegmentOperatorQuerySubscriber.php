<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\EventListener;

use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\ORM\Query\Expr;
use Mautic\LeadBundle\Event\SegmentOperatorQueryBuilderEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Segment\OperatorOptions;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class SegmentOperatorQuerySubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            LeadEvents::LIST_FILTERS_OPERATOR_QUERYBUILDER_ON_GENERATE => [
                ['onEmptyOperator', 0],
                ['onNotEmptyOperator', 0],
                ['onNegativeOperators', 0],
                ['onMultiselectOperators', 0],
                ['onDefaultOperators', 0],
            ],
        ];
    }

    public function onEmptyOperator(SegmentOperatorQueryBuilderEvent $event): void
    {
        if (!$event->operatorIsOneOf('empty')) {
            return;
        }

        $leadsTableAlias = $event->getLeadsTableAlias();
        $filter          = $event->getFilter();
        $field           = $leadsTableAlias.'.'.$filter->getField();
        $expr            = $event->getQueryBuilder()->expr();
        $parts           = [$expr->isNull($field)];

        if ($filter->doesColumnSupportEmptyValue()) {
            $parts[] = $expr->eq($field, $expr->literal(''));
        }

        $event->addExpression(new CompositeExpression(CompositeExpression::TYPE_OR, $parts));
        $event->stopPropagation();
    }

    public function onNotEmptyOperator(SegmentOperatorQueryBuilderEvent $event): void
    {
        if (!$event->operatorIsOneOf('notEmpty')) {
            return;
        }

        $leadsTableAlias = $event->getLeadsTableAlias();
        $filter          = $event->getFilter();
        $field           = $leadsTableAlias.'.'.$filter->getField();
        $expr            = $event->getQueryBuilder()->expr();
        $parts           = [$expr->isNotNull($field)];

        if ($filter->doesColumnSupportEmptyValue()) {
            $parts[] = $expr->neq($field, $expr->literal(''));
        }

        $event->addExpression(new CompositeExpression(CompositeExpression::TYPE_AND, $parts));
        $event->stopPropagation();
    }

    public function onNegativeOperators(SegmentOperatorQueryBuilderEvent $event): void
    {
        if (!$event->operatorIsOneOf(
            'neq',
            'notLike',
            'notBetween', // Used only for date with week combination (NOT EQUAL [this week, next week, last week])
            'notIn'
        )) {
            return;
        }

        $leadsTableAlias = $event->getLeadsTableAlias();

        $event->addExpression(
            $event->getQueryBuilder()->expr()->or(
                $event->getQueryBuilder()->expr()->isNull($leadsTableAlias.'.'.$event->getFilter()->getField()),
                $event->getQueryBuilder()->expr()->{$event->getFilter()->getOperator()}(
                    $leadsTableAlias.'.'.$event->getFilter()->getField(),
                    $event->getParameterHolder()
                )
            )
        );

        $event->stopPropagation();
    }

    public function onMultiselectOperators(SegmentOperatorQueryBuilderEvent $event): void
    {
        if (!$event->operatorIsOneOf('multiselect', '!multiselect')) {
            return;
        }

        $leadsTableAlias = $event->getLeadsTableAlias();

        $expressions = [];

        $queryBuilder = $event->getQueryBuilder();

        /**
         * Given contact fields REX1, REX2:
         * Including all: and(regexp) function must succeed if both fields are matched: REX1 AND REX2
         * Excluding all: not(and(regexp)) function must succeed if both fields are matched, or the field is NULL: NOT (REX1 AND REX2) OR IS NULL
         * Including any: or(regexp) function must succeed: REX1 OR REX2
         * Excluding any: and(notRegexp) function must succeed, or field is null: (NOT REX1 AND NOT REX2) OR IS NULL
         */
        $filterArray      = $event->getFilter()->contactSegmentFilterCrate->getArray();
        $originalOperator = $filterArray['operator'];
        $applyIsNull      = in_array($originalOperator, [OperatorOptions::EXCLUDING_ALL, OperatorOptions::EXCLUDING_ANY], true);
        $applyNot         = OperatorOptions::EXCLUDING_ALL === $originalOperator;

        $operator = 'regexp';
        if (OperatorOptions::EXCLUDING_ANY === $originalOperator) {
            $operator = 'notRegexp';
        }

        if (in_array($originalOperator, [OperatorOptions::INCLUDING_ALL, OperatorOptions::EXCLUDING_ALL, OperatorOptions::EXCLUDING_ANY], true)) {
            $filterGlue = 'and';
        } else {
            $filterGlue = 'or';
        }

        foreach ($event->getParameterHolder() as $parameter) {
            $expressions[] = $queryBuilder->expr()->$operator($leadsTableAlias.'.'.$event->getFilter()->getField(), $parameter);
        }

        if ($applyIsNull) {
            if ($applyNot) {
                $expressions = [$queryBuilder->expr()->or(
                    (string) new Expr\Func('NOT', (string) $queryBuilder->expr()->$filterGlue(...$expressions)),
                    $queryBuilder->expr()->isNull($leadsTableAlias.'.'.$event->getFilter()->getField()),
                )];
            } else {
                $expressions = [$queryBuilder->expr()->or(
                    $queryBuilder->expr()->$filterGlue(...$expressions),
                    $queryBuilder->expr()->isNull($leadsTableAlias.'.'.$event->getFilter()->getField()),
                )];
            }
        }

        $event->addExpression($queryBuilder->expr()->$filterGlue(...$expressions));
        $event->stopPropagation();
    }

    public function onDefaultOperators(SegmentOperatorQueryBuilderEvent $event): void
    {
        if (!$event->operatorIsOneOf(
            'startsWith',
            'endsWith',
            'gt',
            'eq',
            'gte',
            'like',
            'lt',
            'lte',
            'in',
            'between', // Used only for date with week combination (EQUAL [this week, next week, last week])
            'regexp',
            'notRegexp' // Different behaviour from 'notLike' because of BC (do not use condition for NULL). Could be changed in Mautic 3.
        )) {
            return;
        }

        $leadsTableAlias = $event->getLeadsTableAlias();

        $event->addExpression(
            $event->getQueryBuilder()->expr()->{$event->getFilter()->getOperator()}(
                $leadsTableAlias.'.'.$event->getFilter()->getField(),
                $event->getParameterHolder()
            )
        );

        $event->stopPropagation();
    }
}

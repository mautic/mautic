<?php

namespace Mautic\LeadBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Helper\InputHelper;

class LeadFieldRepository extends CommonRepository
{
    /**
     * Retrieves array of aliases used to ensure unique alias for new fields.
     *
     * @param int    $exludingId
     * @param bool   $publishedOnly
     * @param bool   $includeEntityFields
     * @param string $object              name of object using the custom fields
     *
     * @return array
     */
    public function getAliases($exludingId, $publishedOnly = false, $includeEntityFields = true, $object = 'lead')
    {
        $q = $this->_em->getConnection()->createQueryBuilder()
            ->select('l.alias')
            ->from(MAUTIC_TABLE_PREFIX.'lead_fields', 'l');

        if (!empty($exludingId)) {
            $q->where('l.id != :id')
                ->setParameter('id', $exludingId);
        }

        if ($publishedOnly) {
            $q->andWhere(
                $q->expr()->eq('is_published', ':true')
            )
                ->setParameter(':true', true, 'boolean');
        }

        if ($object) {
            $q->andWhere(
                $q->expr()->eq('l.object', ':object')
            )->setParameter('object', $object);
        }

        $results = $q->execute()->fetchAll();
        $aliases = [];
        foreach ($results as $item) {
            $aliases[] = $item['alias'];
        }

        if ($includeEntityFields) {
            //add lead main column names to prevent attempt to create a field with the same name
            $leadRepo = $this->_em->getRepository('MauticLeadBundle:Lead')->getBaseColumns('Mautic\\LeadBundle\\Entity\\Lead', true);
            $aliases  = array_merge($aliases, $leadRepo);
        }

        return $aliases;
    }

    /**
     * @return string
     */
    public function getTableAlias()
    {
        return 'f';
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $q
     * @param object                                                       $filter
     *
     * @return array
     */
    protected function addCatchAllWhereClause($q, $filter)
    {
        return $this->addStandardCatchAllWhereClause(
            $q,
            $filter,
            [
                'f.label',
                'f.alias',
            ]
        );
    }

    /**
     * @return string
     */
    protected function getDefaultOrder()
    {
        return [
            ['f.order', 'ASC'],
        ];
    }

    /**
     * Get field aliases for lead table columns.
     *
     * @param string $object name of object using the custom fields
     *
     * @return array
     */
    public function getFieldAliases($object = 'lead')
    {
        $qb = $this->_em->getConnection()->createQueryBuilder();

        return $qb->select('f.alias, f.is_unique_identifer as is_unique, f.type, f.object')
                ->from(MAUTIC_TABLE_PREFIX.'lead_fields', 'f')
                ->where($qb->expr()->eq('object', ':object'))
                ->setParameter('object', $object)
                ->orderBy('f.field_order', 'ASC')
                ->execute()->fetchAll();
    }

    /**
     * @return ArrayCollection<int,LeadField>
     */
    public function getListablePublishedFields(): ArrayCollection
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select($this->getTableAlias());
        $queryBuilder->from($this->_entityName, $this->getTableAlias(), "{$this->getTableAlias()}.id");
        $queryBuilder->where("{$this->getTableAlias()}.isListable = 1");
        $queryBuilder->andWhere("{$this->getTableAlias()}.isPublished = 1");
        $queryBuilder->orderBy("{$this->getTableAlias()}.object");

        return new ArrayCollection($queryBuilder->getQuery()->execute());
    }

    /**
     * Add company left join.
     *
     * @param \Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $q
     */
    private function addCompanyLeftJoin($q)
    {
        $q->leftJoin('l', MAUTIC_TABLE_PREFIX.'companies_leads', 'companies_lead', 'l.id = companies_lead.lead_id');
        $q->leftJoin('companies_lead', MAUTIC_TABLE_PREFIX.'companies', 'company', 'companies_lead.company_id = company.id');
    }

    /**
     * Return property by field alias and join tables.
     *
     * @param string                                                       $field
     * @param \Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $q
     */
    public function getPropertyByField($field, $q)
    {
        $columnAlias = 'l.';
        // Join company tables If we're trying search by company fields
        if (in_array($field, array_column($this->getFieldAliases('company'), 'alias'))) {
            $this->addCompanyLeftJoin($q);
            $columnAlias = 'company.';
        } elseif (in_array($field, ['utm_campaign', 'utm_content', 'utm_medium', 'utm_source', 'utm_term'])) {
            $q->join('l', MAUTIC_TABLE_PREFIX.'lead_utmtags', 'u', 'l.id = u.lead_id');
            $columnAlias = 'u.';
        }

        return $columnAlias.$field;
    }

    /**
     * Compare a form result value with defined value for defined lead.
     *
     * @param int    $lead         ID
     * @param int    $field        alias
     * @param string $value        to compare with
     * @param string $operatorExpr for WHERE clause
     *
     * @return bool
     */
    public function compareValue($lead, $field, $value, $operatorExpr, ?string $fieldType = null)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->select('l.id')
            ->from(MAUTIC_TABLE_PREFIX.'leads', 'l');

        if ('tags' === $field) {
            // Special reserved tags field
            $q->join('l', MAUTIC_TABLE_PREFIX.'lead_tags_xref', 'x', 'l.id = x.lead_id')
                ->join('x', MAUTIC_TABLE_PREFIX.'lead_tags', 't', 'x.tag_id = t.id')
                ->where(
                    $q->expr()->andX(
                        $q->expr()->eq('l.id', ':lead'),
                        $q->expr()->eq('t.tag', ':value')
                    )
                )
                ->setParameter('lead', (int) $lead)
                ->setParameter('value', $value);

            $result = $q->execute()->fetch();

            if (('eq' === $operatorExpr) || ('like' === $operatorExpr)) {
                return !empty($result['id']);
            } elseif (('neq' === $operatorExpr) || ('notLike' === $operatorExpr)) {
                return empty($result['id']);
            } else {
                return false;
            }
        } else {
            $property = $this->getPropertyByField($field, $q);
            if ('empty' === $operatorExpr || 'notEmpty' === $operatorExpr) {
                $doesSupportEmptyValue            = !in_array($fieldType, ['date', 'datetime'], true);
                $compositeExpression              = ('empty' === $operatorExpr) ?
                    $q->expr()->orX(
                         $q->expr()->isNull($property),
                        $doesSupportEmptyValue ? $q->expr()->eq($property, $q->expr()->literal('')) : null
                    )
                    :
                    $q->expr()->andX(
                        $q->expr()->isNotNull($property),
                        $doesSupportEmptyValue ? $q->expr()->neq($property, $q->expr()->literal('')) : null
                    );
                $q->where(
                    $q->expr()->andX(
                        $q->expr()->eq('l.id', ':lead'),
                        $compositeExpression
                    )
                )
                  ->setParameter('lead', (int) $lead);
            } elseif ('regexp' === $operatorExpr || 'notRegexp' === $operatorExpr) {
                if ('regexp' === $operatorExpr) {
                    $where = $property.' REGEXP  :value';
                } else {
                    $where = $property.' NOT REGEXP  :value';
                }

                $q->where(
                    $q->expr()->andX(
                        $q->expr()->eq('l.id', ':lead'),
                        $q->expr()->andX($where)
                    )
                )
                  ->setParameter('lead', (int) $lead)
                  ->setParameter('value', $value);
            } elseif ('in' === $operatorExpr || 'notIn' === $operatorExpr) {
                $value = $q->expr()->literal(
                    InputHelper::clean($value)
                );

                $value = trim($value, "'");
                if ('not' === substr($operatorExpr, 0, 3)) {
                    $operator = 'NOT REGEXP';
                } else {
                    $operator = 'REGEXP';
                }

                $expr = $q->expr()->andX(
                    $q->expr()->eq('l.id', ':lead')
                );

                $expr->add(
                    $property." $operator '\\\\|?$value\\\\|?'"
                );

                $q->where($expr)
                    ->setParameter('lead', (int) $lead);
            } else {
                $expr = $q->expr()->andX(
                    $q->expr()->eq('l.id', ':lead')
                );

                if ('neq' == $operatorExpr) {
                    // include null
                    $expr->add(
                        $q->expr()->orX(
                            $q->expr()->$operatorExpr($property, ':value'),
                            $q->expr()->isNull($property)
                        )
                    );
                } else {
                    switch ($operatorExpr) {
                        case 'startsWith':
                            $operatorExpr    = 'like';
                            $value           = $value.'%';
                            break;
                        case 'endsWith':
                            $operatorExpr   = 'like';
                            $value          = '%'.$value;
                            break;
                        case 'contains':
                            $operatorExpr   = 'like';
                            $value          = '%'.$value.'%';
                            break;
                    }

                    $expr->add(
                        $q->expr()->$operatorExpr($property, ':value')
                    );
                }

                $q->where($expr)
                  ->setParameter('lead', (int) $lead)
                  ->setParameter('value', $value);
            }
            if (0 === strpos($property, 'u.')) {
                // Match only against the latest UTM properties.
                $q->orderBy('u.date_added', 'DESC');
                $q->setMaxResults(1);
            }
            $result = $q->execute()->fetch();

            return !empty($result['id']);
        }
    }

    /**
     * Compare a form result value with defined date value for defined lead.
     *
     * @param int    $lead  ID
     * @param int    $field alias
     * @param string $value to compare with
     *
     * @return bool
     */
    public function compareDateValue($lead, $field, $value)
    {
        $q        = $this->_em->getConnection()->createQueryBuilder();
        $property = $this->getPropertyByField($field, $q);
        $q->select('l.id')
            ->from(MAUTIC_TABLE_PREFIX.'leads', 'l')
            ->where(
                $q->expr()->andX(
                    $q->expr()->eq('l.id', ':lead'),
                    $q->expr()->eq($property, ':value')
                )
            )
            ->setParameter('lead', (int) $lead)
            ->setParameter('value', $value);

        $result = $q->execute()->fetch();

        return !empty($result['id']);
    }

    /**
     * Compare a form result value with defined date value ( only day and month compare for
     * events such as anniversary) for defined lead.
     *
     * @param int    $lead  ID
     * @param int    $field alias
     * @param object $value Date object to compare with
     *
     * @return bool
     */
    public function compareDateMonthValue($lead, $field, $value)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->select('l.id')
            ->from(MAUTIC_TABLE_PREFIX.'leads', 'l')
            ->where(
                $q->expr()->andX(
                    $q->expr()->eq('l.id', ':lead'),
                    $q->expr()->eq("MONTH(l. $field)", ':month'),
                    $q->expr()->eq("DAY(l. $field)", ':day')
                )
            )
            ->setParameter('lead', (int) $lead)
            ->setParameter('month', $value->format('m'))
            ->setParameter('day', $value->format('d'));

        $result = $q->execute()->fetch();

        return !empty($result['id']);
    }

    public function getFieldThatIsMissingColumn(): ?LeadField
    {
        $qb = $this->createQueryBuilder($this->getTableAlias());
        $qb->where($qb->expr()->eq("{$this->getTableAlias()}.columnIsNotCreated", 1));
        $qb->orderBy("{$this->getTableAlias()}.dateAdded", 'ASC');
        $qb->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param $type
     *
     * @return LeadField[]
     */
    public function getFieldsByType($type)
    {
        return $this->findBy(['type' => $type]);
    }
}

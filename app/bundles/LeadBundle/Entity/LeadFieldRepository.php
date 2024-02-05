<?php

namespace Mautic\LeadBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\ArrayParameterType;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Helper\InputHelper;

/**
 * @extends CommonRepository<LeadField>
 */
class LeadFieldRepository extends CommonRepository
{
    /**
     * Retrieves array of aliases used to ensure unique alias for new fields.
     *
     * @param int    $exludingId
     * @param bool   $publishedOnly
     * @param bool   $includeEntityFields
     * @param string $object              name of object using the custom fields
     */
    public function getAliases($exludingId, $publishedOnly = false, $includeEntityFields = true, $object = 'lead'): array
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
                ->setParameter('true', true, 'boolean');
        }

        if ($object) {
            $q->andWhere(
                $q->expr()->eq('l.object', ':object')
            )->setParameter('object', $object);
        }

        $results = $q->executeQuery()->fetchAllAssociative();

        $aliases = [];
        foreach ($results as $item) {
            $aliases[] = $item['alias'];
        }

        if ($includeEntityFields) {
            // add lead main column names to prevent attempt to create a field with the same name
            $leadRepo = $this->_em->getRepository(\Mautic\LeadBundle\Entity\Lead::class)->getBaseColumns(\Mautic\LeadBundle\Entity\Lead::class, true);
            $aliases  = array_merge($aliases, $leadRepo);
        }

        return $aliases;
    }

    /**
     * @return LeadField[]
     */
    public function getFieldsForObject(string $object): array
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select($this->getTableAlias());
        $queryBuilder->from($this->getEntityName(), $this->getTableAlias(), "{$this->getTableAlias()}.id");
        $queryBuilder->where("{$this->getTableAlias()}.object = :object");
        $queryBuilder->andWhere("{$this->getTableAlias()}.isPublished = 1");
        $queryBuilder->orderBy("{$this->getTableAlias()}.label");
        $queryBuilder->setParameter('object', $object);

        return $queryBuilder->getQuery()->execute();
    }

    /**
     * @return array<int|string, array<string, mixed>>
     */
    public function getFields(): array
    {
        $fq = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $fq->select('f.id, f.label, f.alias, f.type, f.field_group as "group", f.object, f.is_fixed, f.properties, f.default_value')
            ->from(MAUTIC_TABLE_PREFIX.'lead_fields', 'f')
            ->where('f.is_published = :published')
            ->setParameter('published', true, 'boolean')
            ->addOrderBy('f.field_order', 'asc');
        $results = $fq->executeQuery()->fetchAllAssociative();

        return array_column($results, null, 'alias');
    }

    public function getTableAlias(): string
    {
        return 'f';
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $q
     * @param object                                                       $filter
     */
    protected function addCatchAllWhereClause($q, $filter): array
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
     * @return string[][]
     */
    protected function getDefaultOrder(): array
    {
        return [
            ['f.order', 'ASC'],
        ];
    }

    /**
     * Get field aliases for lead table columns.
     *
     * @param string $object name of object using the custom fields
     */
    public function getFieldAliases($object = 'lead'): array
    {
        $qb = $this->_em->getConnection()->createQueryBuilder();

        return $qb->select('f.alias, f.is_unique_identifer as is_unique, f.type, f.object')
                ->from(MAUTIC_TABLE_PREFIX.'lead_fields', 'f')
                ->where($qb->expr()->eq('object', ':object'))
                ->setParameter('object', $object)
                ->orderBy('f.field_order', 'ASC')
                ->executeQuery()
                ->fetchAllAssociative();
    }

    /**
     * @return ArrayCollection<int,LeadField>
     */
    public function getListablePublishedFields(): ArrayCollection
    {
        $queryBuilder = $this->_em->createQueryBuilder();
        $queryBuilder->select($this->getTableAlias());
        $queryBuilder->from($this->getEntityName(), $this->getTableAlias(), "{$this->getTableAlias()}.id");
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
    private function addCompanyLeftJoin($q): void
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
    public function getPropertyByField($field, $q): string
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
     * @param string $field        alias
     * @param mixed  $value        to compare with
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
                    $q->expr()->and(
                        $q->expr()->eq('l.id', ':lead'),
                        $q->expr()->eq('t.tag', ':value')
                    )
                )
                ->setParameter('lead', (int) $lead)
                ->setParameter('value', $value);

            $result = $q->executeQuery()->fetchAssociative();

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
                    $q->expr()->or(
                        $q->expr()->isNull($property),
                        $doesSupportEmptyValue ? $q->expr()->eq($property, $q->expr()->literal('')) : null
                    )
                    :
                    $q->expr()->and(
                        $q->expr()->isNotNull($property),
                        $doesSupportEmptyValue ? $q->expr()->neq($property, $q->expr()->literal('')) : null
                    );
                $q->where(
                    $q->expr()->and(
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
                    $q->expr()->and(
                        $q->expr()->eq('l.id', ':lead'),
                        $q->expr()->and($where)
                    )
                )
                  ->setParameter('lead', (int) $lead)
                  ->setParameter('value', $value);
            } elseif ('in' === $operatorExpr || 'notIn' === $operatorExpr) {
                $property  = $this->getPropertyByField($field, $q);
                $fieldType = $this->findOneBy(['alias' => $field])->getType();
                $values    = (!is_array($value)) ? [$value] : $value;

                if ('multiselect' == $fieldType) {
                    // multiselect field values are separated by `|` and must be queried using regexp
                    $operator = str_starts_with($operatorExpr, 'not') ? 'NOT REGEXP' : 'REGEXP';

                    $expr = $q->expr()->and(
                        $q->expr()->eq('l.id', ':lead')
                    );

                    // require all multiselect values in condition
                    $andExpr = [];
                    foreach ($value as $v) {
                        $v = $q->expr()->literal(
                            InputHelper::clean($v)
                        );

                        $v         = trim($v, "'");
                        $andExpr[] = $property." $operator '\\\\|?$v\\\\|?'";
                    }

                    $expr = $expr->with($q->expr()->and(...$andExpr));

                    $q->where($expr)
                        ->setParameter('lead', (int) $lead);
                } else {
                    $expr = $q->expr()->and(
                        $q->expr()->eq('l.id', ':lead'),
                        'in' === $operatorExpr ? $q->expr()->in($property, ':values') : $q->expr()->notIn($property, ':values')
                    );

                    $q->where($expr)
                        ->setParameter('lead', (int) $lead)
                        ->setParameter('values', $values, ArrayParameterType::STRING);
                }
            } else {
                $expr = $q->expr()->and(
                    $q->expr()->eq('l.id', ':lead')
                );

                if ('neq' === $operatorExpr) {
                    // include null
                    $expr = $expr->with(
                        $q->expr()->or(
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

                    $expr = $expr->with(
                        $q->expr()->$operatorExpr($property, ':value')
                    );
                }

                $q->where($expr)
                  ->setParameter('lead', (int) $lead)
                  ->setParameter('value', $value);
            }
            if (str_starts_with($property, 'u.')) {
                // Match only against the latest UTM properties.
                $q->orderBy('u.date_added', 'DESC');
                $q->setMaxResults(1);
            }
            $result = $q->executeQuery()->fetchAssociative();

            return !empty($result['id']);
        }
    }

    /**
     * Compare a form result value with defined date value for defined lead.
     *
     * @param int    $lead  ID
     * @param int    $field alias
     * @param string $value to compare with
     */
    public function compareDateValue($lead, $field, $value): bool
    {
        $q        = $this->_em->getConnection()->createQueryBuilder();
        $property = $this->getPropertyByField($field, $q);
        $q->select('l.id')
            ->from(MAUTIC_TABLE_PREFIX.'leads', 'l')
            ->where(
                $q->expr()->and(
                    $q->expr()->eq('l.id', ':lead'),
                    $q->expr()->eq($property, ':value')
                )
            )
            ->setParameter('lead', (int) $lead)
            ->setParameter('value', $value);

        $result = $q->executeQuery()->fetchAssociative();

        return !empty($result['id']);
    }

    /**
     * Compare a form result value with defined date value ( only day and month compare for
     * events such as anniversary) for defined lead.
     *
     * @param int    $lead  ID
     * @param int    $field alias
     * @param object $value Date object to compare with
     */
    public function compareDateMonthValue($lead, $field, $value): bool
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->select('l.id')
            ->from(MAUTIC_TABLE_PREFIX.'leads', 'l')
            ->where(
                $q->expr()->and(
                    $q->expr()->eq('l.id', ':lead'),
                    $q->expr()->eq("MONTH(l. $field)", ':month'),
                    $q->expr()->eq("DAY(l. $field)", ':day')
                )
            )
            ->setParameter('lead', (int) $lead)
            ->setParameter('month', $value->format('m'))
            ->setParameter('day', $value->format('d'));

        $result = $q->executeQuery()->fetchAssociative();

        return !empty($result['id']);
    }

    public function getFieldThatIsMissingColumn(): ?LeadField
    {
        $qb = $this->createQueryBuilder($this->getTableAlias());
        $qb->where($qb->expr()->eq("{$this->getTableAlias()}.columnIsNotCreated", 1));
        $qb->orderBy("{$this->getTableAlias()}.dateAdded", \Doctrine\Common\Collections\Criteria::ASC);
        $qb->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @return LeadField[]
     */
    public function getFieldsByType($type)
    {
        return $this->findBy(['type' => $type]);
    }

    /**
     * @return mixed[]
     */
    public function getFieldSchemaData(string $object): array
    {
        return $this->_em->createQueryBuilder()
            ->select('f.alias, f.label, f.type, f.isUniqueIdentifer')
            ->from($this->getEntityName(), 'f', 'f.alias')
            ->where('f.object = :object')
            ->setParameter('object', $object)
            ->getQuery()
            ->execute();
    }
}

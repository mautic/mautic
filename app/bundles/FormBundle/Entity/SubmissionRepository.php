<?php

namespace Mautic\FormBundle\Entity;

use Doctrine\Common\Collections\Order;
use Doctrine\DBAL\Query\QueryBuilder as DbalQueryBuilder;
use Doctrine\ORM\QueryBuilder;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Entity\TimelineTrait;

/**
 * @extends CommonRepository<Submission>
 */
class SubmissionRepository extends CommonRepository
{
    use TimelineTrait;

    public function saveEntity($entity, $flush = true): void
    {
        parent::saveEntity($entity, $flush);

        // add the results
        $results                  = $entity->getResults();
        $results['submission_id'] = $entity->getId();
        $form                     = $entity->getForm();
        $results['form_id']       = $form->getId();

        if (!empty($results)) {
            // Check that alias is SQL safe since it will be used for the column name
            $databasePlatform = $this->_em->getConnection()->getDatabasePlatform();
            $reservedWords    = $databasePlatform->getReservedKeywordsList();
            foreach ($results as $alias => $value) {
                if ($reservedWords->isKeyword($alias)) {
                    $results[$databasePlatform->quoteIdentifier($alias)] = $value;
                    unset($results[$alias]);
                }
            }

            $this->_em->getConnection()->insert($this->getResultsTableName($form->getId(), $form->getAlias()), $results);
        }
    }

    public function getEntities(array $args = [])
    {
        $form = $args['form'];

        // DBAL
        if (!isset($args['viewOnlyFields'])) {
            $args['viewOnlyFields'] = ['button', 'freetext', 'freehtml', 'pagebreak', 'captcha'];
        }
        $viewOnlyFields = array_map(
            fn ($value): string => '"'.$value.'"',
            $args['viewOnlyFields']
        );

        // Get the list of custom fields
        $fq = $this->_em->getConnection()->createQueryBuilder();
        $fq->select('f.id, f.label, f.alias, f.type')
            ->from(MAUTIC_TABLE_PREFIX.'form_fields', 'f')
            ->where('f.form_id = '.$form->getId())
            ->andWhere(
                $fq->expr()->notIn('f.type', $viewOnlyFields),
                $fq->expr()->eq('f.save_result', ':saveResult')
            )
            ->orderBy('f.field_order, f.id', 'ASC')
            ->setParameter('saveResult', true);
        $results = $fq->executeQuery()->fetchAllAssociative();

        $fields = [];
        foreach ($results as $r) {
            $fields[$r['alias']] = $r;
        }
        unset($results);
        $fieldAliases = array_keys($fields);

        $dq = $this->_em->getConnection()->createQueryBuilder();
        $dq->select('count(r.submission_id) as count')
            ->from($this->getResultsTableName($form->getId(), $form->getAlias()), 'r')
            ->innerJoin('r', MAUTIC_TABLE_PREFIX.'form_submissions', 's', 'r.submission_id = s.id')
            ->leftJoin('s', MAUTIC_TABLE_PREFIX.'ip_addresses', 'i', 's.ip_id = i.id')
            ->where('r.form_id = '.$form->getId());

        $this->buildWhereClause($dq, $args);

        // get a total count
        $result  = $dq->executeQuery()->fetchAllAssociative();
        $total   = $result[0]['count'];

        // now get the actual paginated results
        $this->buildOrderByClause($dq, $args);
        $this->buildLimiterClauses($dq, $args);

        $dq->resetQueryPart('select');

        $databasePlatform = $this->_em->getConnection()->getDatabasePlatform();
        // Quote reserved keywords in field aliases
        $fieldAliases = array_map(fn ($alias) => $databasePlatform->quoteIdentifier($alias), $fieldAliases);

        $fieldAliasSql = (!empty($fieldAliases)) ? ', r.'.implode(',r.', $fieldAliases) : '';
        $dq->select('r.submission_id, s.date_submitted as dateSubmitted, s.lead_id as leadId, s.referer, i.ip_address as ipAddress'.$fieldAliasSql);
        $results = $dq->executeQuery()->fetchAllAssociative();

        // loop over results to put form submission results in something that can be assigned to the entities
        $values         = [];
        $flattenResults = !empty($args['flatten_results']);
        foreach ($results as &$result) {
            $submissionId = $result['submission_id'];
            unset($result['submission_id']);

            $values[$submissionId] = [];
            foreach ($result as $k => $r) {
                if (isset($fields[$k])) {
                    if ($flattenResults) {
                        $values[$submissionId][$k] = $r;
                    } else {
                        $values[$submissionId][$k]          = $fields[$k];
                        $values[$submissionId][$k]['value'] = $r;
                    }
                }
            }
            $result['id']      =     $submissionId;
            $result['results'] = $values[$submissionId];
        }

        if (empty($args['simpleResults'])) {
            // get an array of IDs for ORM query
            $ids = array_keys($values);

            if (count($ids)) {
                // ORM

                // build the order by id since the order was applied above
                // unfortunately, can't use MySQL's FIELD function since we have to be cross-platform
                $order = '(CASE';
                foreach ($ids as $count => $id) {
                    $order .= ' WHEN s.id = '.$id.' THEN '.$count;
                    ++$count;
                }
                $order .= ' ELSE '.$count.' END) AS HIDDEN ORD';

                // ORM - generates lead entities
                $returnEntities = !empty($args['return_entities']);
                $leadSelect     = $returnEntities ? 'l' : 'partial l.{id}';
                $q              = $this
                    ->createQueryBuilder('s');
                $q->select('s, p, i,'.$leadSelect.','.$order)
                    ->leftJoin('s.ipAddress', 'i')
                    ->leftJoin('s.page', 'p')
                    ->leftJoin('s.lead', 'l');

                // only pull the submissions as filtered via DBAL
                $q->where(
                    $q->expr()->in('s.id', ':ids')
                )->setParameter('ids', $ids);

                $q->orderBy('ORD', Order::Ascending->value);
                $results = $returnEntities ? $q->getQuery()->getResult() : $q->getQuery()->getArrayResult();

                foreach ($results as &$r) {
                    if ($r instanceof Submission) {
                        $r->setResults($values[$r->getId()]);
                    } else {
                        $r['results'] = $values[$r['id']];
                    }
                }
            }
        }

        return (!empty($args['withTotalCount'])) ?
            [
                'count'   => $total,
                'results' => $results,
            ] : $results;
    }

    /**
     * @param int $id
     */
    public function getEntity($id = 0): ?Submission
    {
        $entity = parent::getEntity($id);

        if (null != $entity) {
            $form = $entity->getForm();

            // use DBAL to get entity fields
            $q = $this->_em->getConnection()->createQueryBuilder();
            $q->select('*')
                ->from($this->getResultsTableName($form->getId(), $form->getAlias()), 'r')
                ->where('r.submission_id = :id')
                ->setParameter('id', $id);
            $results = $q->executeQuery()->fetchAllAssociative();

            if (!empty($results)) {
                unset($results[0]['submission_id']);
                $entity->setResults($results[0]);
            }
        }

        return $entity;
    }

    /**
     * Get all submissions that derive from a landing page.
     *
     * @param array<mixed> $args
     *
     * @return array<mixed>
     */
    public function getEntitiesByPage(array $args = []): array
    {
        $activePage = $args['activePage'];

        $dq = $this->_em->getConnection()->createQueryBuilder();
        $dq->select('count(s.id) as count')
            ->from(MAUTIC_TABLE_PREFIX.'form_submissions', 's')
            ->innerJoin('s', MAUTIC_TABLE_PREFIX.'pages', 'p', 's.page_id = p.id')
            ->leftJoin('s', MAUTIC_TABLE_PREFIX.'ip_addresses', 'i', 's.ip_id = i.id')
            ->where($dq->expr()->eq('s.page_id', ':page'))
            ->setParameter('page', $activePage->getId());

        $this->buildWhereClause($dq, $args);

        // get a total count
        $result = $dq->executeQuery()->fetchAllAssociative();

        $total  = $result[0]['count'];

        // now get the actual paginated results
        $this->buildOrderByClause($dq, $args);
        $this->buildLimiterClauses($dq, $args);

        $dq->resetQueryPart('select');
        $dq->select('s.id, s.date_submitted as dateSubmitted, s.lead_id as leadId, s.form_id as formId, s.referer, i.ip_address as ipAddress');
        $results = $dq->executeQuery()->fetchAllAssociative();

        return [
            'count'   => $total,
            'results' => $results,
        ];
    }

    /**
     * @param QueryBuilder|DbalQueryBuilder $q
     * @param array<mixed>                  $filter
     */
    public function getFilterExpr($q, array $filter, ?string $unique = null): array
    {
        if ('s.date_submitted' === $filter['column']) {
            $date       = (new DateTimeHelper($filter['value'], 'Y-m-d'))->toUtcString();
            $date1      = $this->generateRandomParameterName();
            $date2      = $this->generateRandomParameterName();
            $parameters = [$date1 => $date.' 00:00:00', $date2 => $date.' 23:59:59'];
            $expr       = $q->expr()->and(
                $q->expr()->gte('s.date_submitted', ":$date1"),
                $q->expr()->lte('s.date_submitted', ":$date2")
            );

            return [$expr, $parameters];
        }

        return parent::getFilterExpr($q, $filter);
    }

    protected function getDefaultOrder(): array
    {
        return [
            ['s.date_submitted', 'ASC'],
        ];
    }

    /**
     * Fetch the base submission data from the database.
     *
     * @return array
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getSubmissions(array $options = [])
    {
        $query = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $query->select('fs.id, f.name, fs.form_id, fs.page_id, fs.date_submitted AS "dateSubmitted", fs.lead_id')
            ->from(MAUTIC_TABLE_PREFIX.'form_submissions', 'fs')
            ->leftJoin('fs', MAUTIC_TABLE_PREFIX.'forms', 'f', 'f.id = fs.form_id');

        if (!empty($options['leadId'])) {
            $query->andWhere('fs.lead_id = :leadId')
                ->setParameter('leadId', $options['leadId']);
        }

        if (!empty($options['id'])) {
            $query->andWhere($query->expr()->eq('fs.form_id', ':id'))
            ->setParameter('id', $options['id']);
        }

        if (isset($options['search']) && $options['search']) {
            $query->andWhere(
                $query->expr()->like('f.name', ':search')
            )->setParameter('search', '%'.$options['search'].'%');
        }

        return $this->getTimelineResults($query, $options, 'f.name', 'fs.date_submitted', [], ['dateSubmitted'], null, 'fs.id');
    }

    /**
     * Get list of forms ordered by it's count.
     *
     * @param DbalQueryBuilder $query
     * @param int              $limit
     * @param int              $offset
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getTopReferrers($query, $limit = 10, $offset = 0): array
    {
        $query->select('fs.referer, count(fs.referer) as sessions')
            ->groupBy('fs.referer')
            ->orderBy('sessions', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        return $query->executeQuery()->fetchAllAssociative();
    }

    /**
     * Get list of forms ordered by it's count.
     *
     * @param DbalQueryBuilder $query
     * @param int              $limit
     * @param int              $offset
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getMostSubmitted($query, $limit = 10, $offset = 0, $column = 'fs.id', $as = 'submissions'): array
    {
        $asSelect = ($as) ? ' as '.$as : '';

        $query->select('f.name as title, f.id, count(distinct '.$column.')'.$asSelect)
            ->groupBy('f.id, f.name')
            ->orderBy($as, 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        return $query->executeQuery()->fetchAllAssociative();
    }

    /**
     * @return mixed[]
     */
    public function getSubmissionCountsByPage($pageId, \DateTime $fromDate = null): array
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->select('count(distinct(s.tracking_id)) as count, s.page_id as id, p.title as name, p.variant_hits as total')
            ->from(MAUTIC_TABLE_PREFIX.'form_submissions', 's')
            ->join('s', MAUTIC_TABLE_PREFIX.'pages', 'p', 's.page_id = p.id');

        if (is_array($pageId)) {
            $q->where($q->expr()->in('s.page_id', $pageId))
                ->groupBy('s.page_id, p.title, p.variant_hits');
        } else {
            $q->where($q->expr()->eq('s.page_id', ':page'))
                ->setParameter('page', (int) $pageId);
        }

        if (null != $fromDate) {
            $dh = new DateTimeHelper($fromDate);
            $q->andWhere($q->expr()->gte('s.date_submitted', ':date'))
                ->setParameter('date', $dh->toUtcString());
        }

        return $q->executeQuery()->fetchAllAssociative();
    }

    /**
     * Get submission count by email by linking emails that have been associated with a page hit that has the
     * same tracking ID as a form submission tracking ID and thus assumed happened in the same session.
     *
     * @return mixed[]
     */
    public function getSubmissionCountsByEmail($emailId, \DateTime $fromDate = null): array
    {
        // link email to page hit tracking id to form submission tracking id
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->select('count(distinct(s.tracking_id)) as count, e.id, e.subject as name, e.variant_sent_count as total')
            ->from(MAUTIC_TABLE_PREFIX.'form_submissions', 's')
            ->join('s', MAUTIC_TABLE_PREFIX.'page_hits', 'h', 's.tracking_id = h.tracking_id')
            ->join('h', MAUTIC_TABLE_PREFIX.'emails', 'e', 'h.email_id = e.id');

        if (is_array($emailId)) {
            $q->where($q->expr()->in('e.id', $emailId))
                ->groupBy('e.id, e.subject, e.variant_sent_count');
        } else {
            $q->where($q->expr()->eq('e.id', ':id'))
                ->setParameter('id', (int) $emailId);
        }

        if (null != $fromDate) {
            $dh = new DateTimeHelper($fromDate);
            $q->andWhere($q->expr()->gte('s.date_submitted', ':date'))
                ->setParameter('date', $dh->toUtcString());
        }

        return $q->executeQuery()->fetchAllAssociative();
    }

    /**
     * Updates lead ID (e.g. after a lead merge).
     */
    public function updateLead($fromLeadId, $toLeadId): void
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->update(MAUTIC_TABLE_PREFIX.'form_submissions')
            ->set('lead_id', (int) $toLeadId)
            ->where('lead_id = '.(int) $fromLeadId)
            ->executeStatement();
    }

    /**
     * Validates that an array of submission IDs belong to a specific form.
     */
    public function validateSubmissions($ids, $formId): array
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->select('s.id')
            ->from(MAUTIC_TABLE_PREFIX.'form_submissions', 's')
            ->where(
                $q->expr()->and(
                    $q->expr()->eq('s.form_id', (int) $formId),
                    $q->expr()->in('s.id', $ids)
                )
            );

        $validIds = [];
        $results  = $q->executeQuery()->fetchAllAssociative();

        foreach ($results as $r) {
            $validIds[] = $r['id'];
        }

        return $validIds;
    }

    /**
     * Compare a form result value with defined value for defined lead.
     *
     * @param int         $lead         ID
     * @param int         $form         ID
     * @param string      $formAlias
     * @param int         $field        alias
     * @param string      $value        to compare with
     * @param string      $operatorExpr for WHERE clause
     * @param string|null $type
     */
    public function compareValue($lead, $form, $formAlias, $field, $value, $operatorExpr, $type = null): bool
    {
        // Modify operator
        switch ($operatorExpr) {
            case 'like':
            case 'notLike':
                $value = !str_contains($value, '%') ? '%'.$value.'%' : $value;
                break;
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

        // use DBAL to get entity fields
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->select('s.id')
            ->from($this->getResultsTableName($form, $formAlias), 'r')
            ->leftJoin('r', MAUTIC_TABLE_PREFIX.'form_submissions', 's', 's.id = r.submission_id')
            ->where(
                $q->expr()->and(
                    $q->expr()->eq('s.lead_id', ':lead'),
                    $q->expr()->eq('s.form_id', ':form')
                )
            )
            ->setParameter('lead', (int) $lead)
            ->setParameter('form', (int) $form);

        match ($type) {
            'boolean', 'number' => $q->andWhere($q->expr()->$operatorExpr('r.'.$field, $value)),
            default => $q->andWhere($q->expr()->$operatorExpr('r.'.$field, ':value'))
                ->setParameter('value', $value),
        };

        $result = $q->executeQuery()->fetchAssociative();

        return !empty($result['id']);
    }

    /**
     * @param Form $form
     */
    public function getSubmissionCounts($form)
    {
        $query = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $query->select('COUNT(fs.id) AS `total`, COUNT(DISTINCT (fs.lead_id)) AS `unique`')
            ->from(MAUTIC_TABLE_PREFIX.'form_submissions', 'fs');
        $query->where($query->expr()->eq('fs.form_id', ':id'))
                ->setParameter('id', $form->getId());

        return $query->executeQuery()->fetchAssociative();
    }

    /**
     * Compile and return the form result table name.
     *
     * @param int    $formId
     * @param string $formAlias
     */
    public function getResultsTableName($formId, $formAlias): string
    {
        return MAUTIC_TABLE_PREFIX.'form_results_'.$formId.'_'.$formAlias;
    }

    public function getTableAlias(): string
    {
        return 'fs';
    }
}

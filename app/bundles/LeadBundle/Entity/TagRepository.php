<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * Class TagRepository.
 */
class TagRepository extends CommonRepository
{
    /**
     * Delete orphan tags that are not associated with any lead.
     */
    public function deleteOrphans()
    {
        $qb       = $this->_em->getConnection()->createQueryBuilder();
        $havingQb = $this->_em->getConnection()->createQueryBuilder();

        $havingQb->select('count(x.lead_id) as the_count')
            ->from(MAUTIC_TABLE_PREFIX.'lead_tags_xref', 'x')
            ->where('x.tag_id = t.id');

        $qb->select('t.id')
            ->from(MAUTIC_TABLE_PREFIX.'lead_tags', 't')
            ->having(sprintf('(%s)', $havingQb->getSQL()).' = 0');
        $delete = $qb->execute()->fetch();

        if (count($delete)) {
            $qb->resetQueryParts();
            $qb->delete(MAUTIC_TABLE_PREFIX.'lead_tags')
                ->where(
                    $qb->expr()->in('id', $delete)
                )
                ->execute();
        }
    }

    /**
     * Get tag entities by name.
     *
     * @param $tags
     *
     * @return array
     */
    public function getTagsByName($tags)
    {
        if (empty($tags)) {
            return [];
        }

        array_walk($tags, create_function('&$val', 'if (strpos($val, "-") === 0) $val = substr($val, 1);'));
        $qb = $this->_em->createQueryBuilder()
            ->select('t')
            ->from('MauticLeadBundle:Tag', 't', 't.tag');

        if ($tags) {
            $qb->where(
                $qb->expr()->in('t.tag', ':tags')
            )
                ->setParameter('tags', $tags);
        }

        $results = $qb->getQuery()->getResult();

        return $results;
    }

    /**
     * Check Lead tags by Ids.
     *
     * @param Lead $lead
     * @param $tags
     *
     * @return bool
     */
    public function checkLeadByTags(Lead $lead, $tags)
    {
        if (empty($tags)) {
            return false;
        }

        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->select('l.id')
            ->from(MAUTIC_TABLE_PREFIX.'leads', 'l');
        $q->join('l', MAUTIC_TABLE_PREFIX.'lead_tags_xref', 'x', 'l.id = x.lead_id')
                ->join('l', MAUTIC_TABLE_PREFIX.'lead_tags', 't', 'x.tag_id = t.id')
                ->where(
                    $q->expr()->andX(
                        $q->expr()->in('t.tag', ':tags'),
                        $q->expr()->eq('l.id', ':leadId')
                    )
                )
                ->setParameter('tags', $tags, \Doctrine\DBAL\Connection::PARAM_STR_ARRAY)
                ->setParameter('leadId', $lead->getId());

        return (bool) $q->execute()->fetchColumn();
    }
}

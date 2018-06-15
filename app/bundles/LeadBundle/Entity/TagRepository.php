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

use Mautic\CampaignBundle\Entity\Campaign;
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
        $qb                 = $this->_em->getConnection()->createQueryBuilder();
        $havingLeadsQb      = $this->_em->getConnection()->createQueryBuilder();
        $havingCampaignsQb  = $this->_em->getConnection()->createQueryBuilder();

        $havingLeadsQb->select('count(xl.lead_id) as lead_count')
            ->from(MAUTIC_TABLE_PREFIX.'lead_tags_xref', 'xl')
            ->where('xl.tag_id = t.id');

        $havingCampaignsQb->select('count(xc.campaign_id) as campaign_count')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_tags_xref', 'xc')
            ->where('xc.campaign_id = t.id');

        $qb->select('t.id')
            ->from(MAUTIC_TABLE_PREFIX.'lead_tags', 't')
            ->having(
                $qb->expr()->andX(
                    sprintf('(%s)', $havingLeadsQb->getSQL()).' = 0',
                    sprintf('(%s)', $havingCampaignsQb->getSQL()).' = 0'
                )
            );

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
     * @param array $tags
     *
     * @return array
     */
    public function getTagsByName(array $tags)
    {
        if (empty($tags)) {
            return [];
        }

        $tags = $this->removeMinusFromTags($tags);
        $qb   = $this->createQueryBuilder('t', 't.tag');

        if ($tags) {
            $qb->where(
                $qb->expr()->in('t.tag', ':tags')
            )
                ->setParameter('tags', $tags);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Goes through each element in the array expecting it to be a tag label and removes the '-' character infront of it.
     * The minus character is used to identify that the tag should be removed.
     *
     * @param array $tags
     *
     * @return array
     */
    public function removeMinusFromTags(array $tags)
    {
        return array_map(function ($val) {
            return (strpos($val, '-') === 0) ? substr($val, 1) : $val;
        }, $tags);
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
            ->from(MAUTIC_TABLE_PREFIX.'leads', 'l')
            ->join('l', MAUTIC_TABLE_PREFIX.'lead_tags_xref', 'x', 'l.id = x.lead_id')
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

    /**
     * Check Campaign tags by Ids.
     *
     * @param Lead $lead
     * @param $tags
     *
     * @return bool
     */
    public function checkCampaignByTags(Campaign $campaign, $tags)
    {
        if (empty($tags)) {
            return false;
        }

        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->select('c.id')
            ->from(MAUTIC_TABLE_PREFIX.'campaigns', 'c')
            ->join('l', MAUTIC_TABLE_PREFIX.'campaign_tags_xref', 'x', 'c.id = x.campaign_id')
            ->join('l', MAUTIC_TABLE_PREFIX.'lead_tags', 't', 'x.tag_id = t.id')
            ->where(
                $q->expr()->andX(
                    $q->expr()->in('t.tag', ':tags'),
                    $q->expr()->eq('c.id', ':campaignId')
                )
            )
            ->setParameter('tags', $tags, \Doctrine\DBAL\Connection::PARAM_STR_ARRAY)
            ->setParameter('campaignId', $campaign->getId());

        return (bool) $q->execute()->fetchColumn();
    }

    /**
     * @param string $name
     *
     * @return Tag
     */
    public function getTagByNameOrCreateNewOne($name)
    {
        $tag = $this->findOneBy(
            [
                'tag' => $name,
            ]
        );

        if (!$tag) {
            $tag = new Tag($name);
        }

        return $tag;
    }
}

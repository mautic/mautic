<?php

namespace MauticPlugin\MauticTagManagerBundle\Entity;

use Mautic\LeadBundle\Entity\TagRepository as BaseTagRepository;

/**
 * Class TagRepository.
 */
class TagRepository extends BaseTagRepository
{
    /**
     * @return array
     */
    protected function getDefaultOrder()
    {
        return [
            ['lt.tag', 'ASC'],
        ];
    }

    /**
     * @return string
     */
    public function getTableAlias()
    {
        return 'lt';
    }

    public function countOccurrences($tag)
    {
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $q->select('lt.tag')
            ->from(MAUTIC_TABLE_PREFIX.'lead_tags', 'lt')
            ->where('lt.tag = :tag')
            ->setParameter('tag', $tag);

        $result = $q->execute()->fetchAll();

        return count($result);
    }

    /**
     * Get a count of leads that belong to the tag.
     *
     * @param $tagIds
     *
     * @return array
     */
    public function countByLeads($tagIds)
    {
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $q->select('count(ltx.lead_id) as thecount, ltx.tag_id')
            ->from(MAUTIC_TABLE_PREFIX.'lead_tags_xref', 'ltx');

        $returnArray = (is_array($tagIds));

        if (!$returnArray) {
            $tagIds = [$tagIds];
        }

        $q->where(
            $q->expr()->in('ltx.tag_id', $tagIds)
        )
            ->groupBy('ltx.tag_id');

        $result = $q->execute()->fetchAll();

        $return = [];
        foreach ($result as $r) {
            $return[$r['tag_id']] = $r['thecount'];
        }

        // Ensure lists without leads have a value
        foreach ($tagIds as $l) {
            if (!isset($return[$l])) {
                $return[$l] = 0;
            }
        }

        return ($returnArray) ? $return : $return[$tagIds[0]];
    }
}

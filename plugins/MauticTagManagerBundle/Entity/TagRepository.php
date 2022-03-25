<?php

namespace MauticPlugin\MauticTagManagerBundle\Entity;

use Doctrine\ORM\NoResultException;
use Mautic\LeadBundle\Entity\Tag;
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

    public function getTagByName(string $tag): ?Tag
    {
        $q = $this->createQueryBuilder($this->getTableAlias());
        $q->where($this->getTableAlias().'.tag = :tag');
        $q->setParameter('tag', $tag);
        $q->setMaxResults(1);

        try {
            $result = $q->getQuery()->getSingleResult();
        } catch (NoResultException $e) {
            $result = null;
        }

        return $result;
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

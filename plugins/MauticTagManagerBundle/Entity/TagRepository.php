<?php

declare(strict_types=1);

namespace MauticPlugin\MauticTagManagerBundle\Entity;

use Mautic\LeadBundle\Entity\TagRepository as BaseTagRepository;

class TagRepository extends BaseTagRepository
{
    /**
     * @return string[][]
     */
    protected function getDefaultOrder(): array
    {
        return [
            ['lt.tag', 'ASC'],
        ];
    }

    public function getTableAlias(): string
    {
        return 'lt';
    }

    public function countOccurrences($tag): int
    {
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $q->select('lt.tag')
            ->from(MAUTIC_TABLE_PREFIX.'lead_tags', 'lt')
            ->where('lt.tag = :tag')
            ->setParameter('tag', $tag);

        return $q->executeQuery()->rowCount();
    }

    /**
     * Get a count of leads that belong to the tag.
     *
     * @return array
     */
    public function countByLeads($tagIds)
    {
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $q->select('count(ltx.lead_id) as thecount, ltx.tag_id')
            ->from(MAUTIC_TABLE_PREFIX.'lead_tags_xref', 'ltx');

        $returnArray = is_array($tagIds);

        if (!$returnArray) {
            $tagIds = [$tagIds];
        }

        $q->where(
            $q->expr()->in('ltx.tag_id', $tagIds)
        )
            ->groupBy('ltx.tag_id');

        $result = $q->executeQuery()->fetchAllAssociative();

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

    /**
     * @return array<mixed>
     */
    protected function addCatchAllWhereClause($qb, $filter): array
    {
        return $this->addStandardCatchAllWhereClause($qb, $filter, [
            'lt.tag',
        ]);
    }
}

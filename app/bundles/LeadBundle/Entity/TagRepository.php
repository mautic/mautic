<?php

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
     * @return array
     */
    public function removeMinusFromTags(array $tags)
    {
        return array_map(function ($val) {
            return (0 === strpos($val, '-')) ? substr($val, 1) : $val;
        }, $tags);
    }

    /**
     * Check Lead tags by Ids.
     *
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
     * @param string $name
     *
     * @return Tag
     */
    public function getTagByNameOrCreateNewOne($name)
    {
        $tag = new Tag($name, true);

        /** @var Tag|null $existingTag */
        $existingTag = $this->findOneBy(
            [
                'tag' => $tag->getTag(),
            ]
        );

        return $existingTag ?? $tag;
    }
}

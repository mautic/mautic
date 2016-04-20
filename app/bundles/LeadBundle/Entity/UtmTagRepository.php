<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UtmTagBundle\Entity;

use Doctrine\ORM\Query;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * Class UtmTagRepository
 */
class UtmTagRepository extends CommonRepository
{
    /**
     * Get array of published actions based on type
     *
     * @param string $type
     *
     * @return array
     */
    public function getUtmTagsByType($type)
    {
        $q = $this->createQueryBuilder('u')
            ->select('partial u.{id, type, name, referrer}')
            ->setParameter('type', $type);

        //make sure the published up and down dates are good
        $expr = $this->getPublishedByDateExpression($q);
        $expr->add($q->expr()->eq('u.type', ':type'));

        $q->where($expr);

        return $q->getQuery()->getResult();
    }
    /**
     * Delete orphan tags that are not associated with any lead
     */
    public function deleteOrphans()
    {
        $qb  = $this->_em->getConnection()->createQueryBuilder();
        $havingQb = $this->_em->getConnection()->createQueryBuilder();

        $havingQb->select('count(x.lead_id) as the_count')
            ->from(MAUTIC_TABLE_PREFIX.'lead_utmtags_xref', 'x')
            ->where('x.tag_id = t.id');

        $qb->select('t.id')
            ->from(MAUTIC_TABLE_PREFIX.'lead_utmtags', 't')
            ->having(sprintf('(%s)', $havingQb->getSQL()) . ' = 0');
        $delete = $qb->execute()->fetch();

        if (count($delete)) {
            $qb->resetQueryParts();
            $qb->delete(MAUTIC_TABLE_PREFIX.'lead_utmtags')
                ->where(
                    $qb->expr()->in('id', $delete)
                )
                ->execute();
        }
    }

    /**
     * Get tag entities by name
     *
     * @param $utmTags
     *
     * @return array
     */
    public function getTagsByName($utmTags)
    {
        if (empty($utmTags)) {

            return array();
        }

        array_walk($utmTags, create_function('&$val', 'if (strpos($val, "-") === 0) $val = substr($val, 1);'));
        $qb = $this->_em->createQueryBuilder()
            ->select('t')
            ->from('MauticLeadBundle:UtmTag', 't', 't.utmtag');

        if ($utmTags) {
            $qb->where(
                $qb->expr()->in('t.utmtag', ':utmtags')
            )
                ->setParameter('utmtags', $utmTags);
        }

        $results = $qb->getQuery()->getResult();

        return $results;
    }
}

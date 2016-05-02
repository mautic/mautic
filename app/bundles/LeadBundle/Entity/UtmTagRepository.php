<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Entity;

use Doctrine\ORM\Query;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * Class UtmTagRepository
 */
class UtmTagRepository extends CommonRepository
{
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
            ->select('ut')
            ->from('MauticLeadBundle:UtmTag', 'ut', 'ut.utmtag');

        if ($utmTags) {
            $qb->where(
                $qb->expr()->in('ut.utmtag', ':utmtags')
            )
                ->setParameter('utmtags', $utmTags);
        }

        $results = $qb->getQuery()->getResult();

        return $results;
    }
}

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
     * Get tag entities by lead
     *
     * @param $utmTags
     *
     * @return array
     */
    public function getUtmTagsByLead(Lead $lead, $options = array())
    {
        if (empty($lead)) {
            return array();
        }

        $qb = $this->_em->createQueryBuilder()
            ->select('ut')
            ->from('MauticLeadBundle:UtmTag', 'ut');

        $qb->where(
            'ut.lead = ' . $lead->getId() . 'and (ut.utmCampaign is not null or ut.utmContent is not null or ut.utmMedium is not null or ut.utmSource is not null or ut.utmTerm is not null)'
        );

        if (isset($options['filters']['search']) && $options['filters']['search']) {
        $qb->andWhere($qb->expr()->orX(
            $qb->expr()->like('ut.eventName', $qb->expr()->literal('%' . $options['filters']['search'] . '%')),
            $qb->expr()->like('ut.actionName', $qb->expr()->literal('%' . $options['filters']['search'] . '%'))
        ));
    }
        
        return $qb->getQuery()->getArrayResult();
    }
}

<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\WebhookBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * Class WebhookRepository
 */
class WebhookRepository extends CommonRepository
{
    /**
     * Get a list of entities
     *
     * @param array $args
     *
     * @return Paginator
     */
    public function getEntities($args = array())
    {
        return parent::getEntities($args);
    }

    /**
     * Get a list of entities
     *
     * @param array $args
     *
     * @return Paginator
     */
    public function getEntitiesByEventTypes($types)
    {
        if (!is_array($types)) {
            $types = array($types);
        }
        $alias = $this->getTableAlias();
        $q = $this->createQueryBuilder($alias)
            ->leftJoin($alias.'.events', 'u');

        $q->where(
            $q->expr()->in('u.event_type', ':types')
        )->setParameter('types', $types);

        return $q->getQuery()->getResult();
    }
}
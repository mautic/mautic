<?php

namespace Mautic\WebhookBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

class EventRepository extends CommonRepository
{
    /**
     * @param $type
     *
     * @return array
     */
    public function getEntitiesByEventType($type)
    {
        $alias = $this->getTableAlias();
        $q     = $this->createQueryBuilder($alias)
            ->leftJoin($alias.'.webhook', 'u');

        $q->where(
            $q->expr()->eq($alias.'.eventType', ':type')
        )->setParameter('type', $type);

        // only find published webhooks
        $q->andWhere($q->expr()->eq('u.isPublished', ':published'))
            ->setParameter('published', 1);

        return $q->getQuery()->getResult();
    }
}

<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * Class PluginRepository.
 */
class PluginRepository extends CommonRepository
{
    /**
     * Find an addon record by bundle name.
     *
     * @param string $bundle
     *
     * @return mixed
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findByBundle($bundle)
    {
        $q = $this->createQueryBuilder($this->getTableAlias());
        $q->where($q->expr()->eq('p.bundle', ':bundle'))
            ->setParameter('bundle', $bundle);

        return $q->getQuery()->getOneOrNullResult();
    }

    /**
     * {@inheritdoc}
     */
    public function getEntities(array $args = [])
    {
        $q = $this->_em->createQueryBuilder();
        $q->select($this->getTableAlias())
            ->from('MauticPluginBundle:Plugin', $this->getTableAlias(), (!empty($args['index'])) ? $this->getTableAlias().'.'.$args['index'] : $this->getTableAlias().'.id');

        $args['qb']               = $q;
        $args['ignore_paginator'] = true;

        return parent::getEntities($args);
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOrder()
    {
        return [
            ['p.name', 'ASC'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTableAlias()
    {
        return 'p';
    }
}

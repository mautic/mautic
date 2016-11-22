<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticSocialBundle\Model;

use Mautic\CoreBundle\Model\AbstractCommonModel;
use MauticPlugin\MauticSocialBundle\Entity\PostCount;

/**
 * Class PostCountModel.
 */
class PostCountModel extends AbstractCommonModel
{
    /**
     * Get a specific entity or generate a new one if id is empty.
     *
     * @param $id
     *
     * @return null|object
     */
    public function getEntity($id = null)
    {
        if (null !== $id) {
            $repo = $this->getRepository();
            if (method_exists($repo, 'getEntity')) {
                return $repo->getEntity($id);
            }

            return $repo->find($id);
        }

        return new PostCount();
    }

    /**
     * Get this model's repository.
     *
     * @return \MauticPlugin\MauticSocialBundle\Entity\PostCountRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticSocialBundle:PostCount');
    }

    /*
     * Updates a monitor record's post count on a daily basis
     *
     * @return boolean
     */
    public function updatePostCount($monitor, \DateTime $postDate)
    {
        // query the db for posts on this date
        $q    = $this->getRepository()->createQueryBuilder($this->getRepository()->getTableAlias());
        $expr = $q->expr()->eq($this->getRepository()->getTableAlias().'.postDate', ':date');

        $q->setParameter('date', $postDate, 'date');
        $q->where($expr);
        $args['qb'] = $q;

        // ignore paginator so we can use the array later
        $args['ignore_paginator'] = true;

        /** @var \MauticPlugin\MauticSocialBundle\Entity\PostCountRepository $postCountsRepository */
        $postCountsRepository = $this->getRepository();

        // get any existing records
        $postCounts = $postCountsRepository->getEntities($args);

        // if there isn't anything then create it
        if (!count($postCounts)) {
            /** @var \MauticPlugin\MauticSocialBundle\Entity\PostCount $postCount */
            $postCount = $this->getEntity();
            $postCount->setMonitor($monitor);
            $postCount->setPostDate($postDate); // $postDate->format('m-d-Y')
        } else {
            // use the first record to increment it.
            $postCount = $this->getEntity($postCounts[0]->getId());
        }

        // increment
        $postCount->setPostCount($postCount->getPostCount() + 1);

        // now save it
        $postCountsRepository->saveEntity($postCount);

        // nothing went wrong so return true here
        return true;
    }
}

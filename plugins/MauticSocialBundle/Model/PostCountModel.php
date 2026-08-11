<?php

namespace MauticPlugin\MauticSocialBundle\Model;

use Mautic\CoreBundle\Model\AbstractCommonModel;
use MauticPlugin\MauticSocialBundle\Entity\PostCount;
use MauticPlugin\MauticSocialBundle\Entity\PostCountRepository;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * @extends AbstractCommonModel<PostCount>
 */
final class PostCountModel extends AbstractCommonModel
{
    private PostCountRepository $postCountRepository;

    #[Required]
    public function autowirePostCountModel(
        PostCountRepository $postCountRepository,
    ): void {
        $this->postCountRepository = $postCountRepository;
    }

    /**
     * Get a specific entity or generate a new one if id is empty.
     */
    public function getEntity($id = null): ?PostCount
    {
        if (null !== $id) {
            if (method_exists($this->postCountRepository, 'getEntity')) {
                return $this->postCountRepository->getEntity($id);
            }

            return $this->postCountRepository->find($id);
        }

        return new PostCount();
    }

    /**
     * Get this model's repository.
     */
    public function getRepository(): PostCountRepository
    {
        return $this->postCountRepository;
    }

    /**
     * Updates a monitor record's post count on a daily basis.
     */
    public function updatePostCount($monitor, \DateTime $postDate): bool
    {
        // query the db for posts on this date
        $q    = $this->postCountRepository->createQueryBuilder($this->postCountRepository->getTableAlias());
        $expr = $q->expr()->eq($this->postCountRepository->getTableAlias().'.postDate', ':date');

        $q->setParameter('date', $postDate, 'date');
        $q->where($expr);
        $args['qb'] = $q;

        // ignore paginator so we can use the array later
        $args['ignore_paginator'] = true;

        // get any existing records
        $postCounts = $this->postCountRepository->getEntities($args);

        // if there isn't anything then create it
        if (!count($postCounts)) {
            /** @var PostCount $postCount */
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
        $this->postCountRepository->saveEntity($postCount);

        // nothing went wrong so return true here
        return true;
    }
}

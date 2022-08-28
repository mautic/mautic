<?php

namespace MauticPlugin\MauticTagManagerBundle\Model;

use Mautic\LeadBundle\Model\TagModel as BaseTagModel;
use MauticPlugin\MauticTagManagerBundle\Entity\Tag;
use MauticPlugin\MauticTagManagerBundle\Form\Type\TagEntityType;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class TagModel
 * {@inheritdoc}
 */
class TagModel extends BaseTagModel
{
    /**
     * {@inheritdoc}
     *
     * @return object
     */
    public function getRepository()
    {
        return $this->em->getRepository(Tag::class);
    }

    /**
     * {@inheritdoc}
     *
     * @param Tag   $entity
     * @param       $formFactory
     * @param null  $action
     * @param array $options
     *
     * @return mixed
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function createForm($entity, $formFactory, $action = null, $options = [])
    {
        if (!$entity instanceof \Mautic\LeadBundle\Entity\Tag) {
            throw new MethodNotAllowedHttpException(['Tag']);
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create(TagEntityType::class, $entity, $options);
    }
}

<?php

namespace MauticPlugin\MauticTagManagerBundle\Model;

use Mautic\CoreBundle\Model\GlobalSearchInterface;
use Mautic\CoreBundle\Model\CannotBeDeletedInterface;
use Mautic\LeadBundle\Model\TagModel as BaseTagModel;
use MauticPlugin\MauticTagManagerBundle\Entity\Tag;
use MauticPlugin\MauticTagManagerBundle\Entity\TagRepository;
use MauticPlugin\MauticTagManagerBundle\Form\Type\TagEntityType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class TagModel extends BaseTagModel implements GlobalSearchInterface, CannotBeDeletedInterface
{
    /**
     * @return TagRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository(Tag::class);
    }

    /**
     * @param Tag         $entity
     * @param string|null $action
     * @param array       $options
     */
    public function createForm($entity, FormFactoryInterface $formFactory, $action = null, $options = []): \Symfony\Component\Form\FormInterface
    {
        if (!$entity instanceof \Mautic\LeadBundle\Entity\Tag) {
            throw new MethodNotAllowedHttpException(['Tag']);
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create(TagEntityType::class, $entity, $options);
    }

    public function cannotBeDeleted(array $ids): array
    {
        $tagCounts   = $this->getRepository()->countByLeads($ids);
        $usedTagCount= array_filter($tagCounts, fn ($count) => $count > 0);
        $usedTags    = [];
        foreach ($usedTagCount as $id => $count) {
            $usedTags[$id] = [
                'type'    => 'error',
                'msg'     => 'mautic.tagmanager.tag.error.cannot.delete.batch',
                'msgVars' => [
                    '%name%'  => $this->getEntity($id)->getTag(),
                    '%count%' => $count,
                ],
            ];
        }

        return $usedTags;
    }
}

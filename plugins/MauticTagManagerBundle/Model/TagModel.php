<?php

namespace MauticPlugin\MauticTagManagerBundle\Model;

use Mautic\CoreBundle\Model\GlobalSearchInterface;
use Mautic\LeadBundle\Model\TagModel as BaseTagModel;
use MauticPlugin\MauticTagManagerBundle\Entity\Tag;
use MauticPlugin\MauticTagManagerBundle\Entity\TagRepository;
use MauticPlugin\MauticTagManagerBundle\Form\Type\TagEntityType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Contracts\Service\Attribute\Required;

final class TagModel extends BaseTagModel implements GlobalSearchInterface
{
    private TagRepository $tagRepository;

    #[Required]
    public function autowirePluginTagModel(
        TagRepository $tagRepository,
    ): void {
        $this->tagRepository = $tagRepository;
    }

    public function getRepository(): TagRepository
    {
        return $this->tagRepository;
    }

    /**
     * @param Tag $entity
     */
    public function createForm($entity, mixed ...$args): FormInterface
    {
        [$action, $options] = $this->resolveCreateFormArgs($args);

        if (!$entity instanceof \Mautic\LeadBundle\Entity\Tag) {
            throw new MethodNotAllowedHttpException(['Tag']);
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $this->formFactory->create(TagEntityType::class, $entity, $options);
    }
}

<?php

namespace Mautic\LeadBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\LeadBundle\Entity\Tag;
use Mautic\LeadBundle\Model\TagModel;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

/**
 * @extends CommonApiController<Tag>
 */
class TagApiController extends CommonApiController
{
    public function initialize(ControllerEvent $event)
    {
        $leadTagModel = $this->getModel('lead.tag');

        if (!$leadTagModel instanceof TagModel) {
            throw new \RuntimeException('Wrong model given.');
        }

        $this->model           = $leadTagModel;
        $this->entityClass     = Tag::class;
        $this->entityNameOne   = 'tag';
        $this->entityNameMulti = 'tags';

        parent::initialize($event);
    }

    /**
     * Creates new entity from provided params.
     *
     * @return object
     *
     * @throws \InvalidArgumentException
     */
    public function getNewEntity(array $params)
    {
        if (empty($params[$this->entityNameOne])) {
            throw new \InvalidArgumentException($this->get('translator')->trans('mautic.lead.api.tag.required', [], 'validators'));
        }

        $tagModel = $this->model;

        if (!$tagModel instanceof TagModel) {
            throw new \RuntimeException('Wrong model given.');
        }

        return $tagModel->getRepository()->getTagByNameOrCreateNewOne($params[$this->entityNameOne]);
    }
}

<?php

namespace Mautic\PageBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\PageBundle\Entity\Page;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Class PageApiController.
 */
class PageApiController extends CommonApiController
{
    /**
     * {@inheritdoc}
     */
    public function initialize(FilterControllerEvent $event)
    {
        $this->model            = $this->getModel('page');
        $this->entityClass      = Page::class;
        $this->entityNameOne    = 'page';
        $this->entityNameMulti  = 'pages';
        $this->serializerGroups = ['pageDetails', 'categoryList', 'publishDetails'];
        $this->dataInputMasks   = ['customHtml' => 'html'];

        parent::initialize($event);
    }

    /**
     * Obtains a list of pages.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getEntitiesAction()
    {
        //get parent level only
        $this->listFilters[] = [
            'column' => 'p.variantParent',
            'expr'   => 'isNull',
        ];

        $this->listFilters[] = [
            'column' => 'p.translationParent',
            'expr'   => 'isNull',
        ];

        return parent::getEntitiesAction();
    }

    /**
     * {@inheritdoc}
     */
    protected function preSerializeEntity(&$entity, $action = 'view')
    {
        $entity->url = $this->model->generateUrl($entity);
    }
}

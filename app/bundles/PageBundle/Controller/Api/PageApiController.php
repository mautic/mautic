<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
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
        $this->entityClass      = 'Mautic\PageBundle\Entity\Page';
        $this->entityNameOne    = 'page';
        $this->entityNameMulti  = 'pages';
        $this->serializerGroups = ['pageDetails', 'categoryList', 'publishDetails'];

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

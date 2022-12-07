<?php

namespace Mautic\PageBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Model\PageModel;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

/**
 * @extends CommonApiController<Page>
 */
class PageApiController extends CommonApiController
{
    /**
     * @var PageModel|null
     */
    protected $model = null;

    public function initialize(ControllerEvent $event)
    {
        $pageModel = $this->getModel('page');
        \assert($pageModel instanceof PageModel);

        $this->model            = $pageModel;
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
}

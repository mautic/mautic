<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Class PageApiController
 */
class PageApiController extends CommonApiController
{

    /**
     * {@inheritdoc}
     */
    public function initialize (FilterControllerEvent $event)
    {
        parent::initialize($event);
        $this->model            = $this->getModel('page');
        $this->entityClass      = 'Mautic\PageBundle\Entity\Page';
        $this->entityNameOne    = 'page';
        $this->entityNameMulti  = 'pages';
        $this->permissionBase   = 'page:pages';
        $this->serializerGroups = array('pageDetails', 'categoryList', 'publishDetails');
    }

    /**
     * Obtains a list of pages
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getEntitiesAction ()
    {
        if (!$this->security->isGranted('page:pages:viewother')) {
            $this->listFilters = array(
                'column' => 'p.createdBy',
                'expr'   => 'eq',
                'value'  => $this->user->getId()
            );
        }

        //get parent level only
        $this->listFilters[] = array(
            'column' => 'p.variantParent',
            'expr'   => 'isNull'
        );

        $this->listFilters[] = array(
            'column' => 'p.translationParent',
            'expr'   => 'isNull'
        );

        return parent::getEntitiesAction();
    }

    /**
     * {@inheritdoc}
     */
    protected function preSerializeEntity (&$entity, $action = 'view')
    {
        $entity->url = $this->model->generateUrl($entity);
    }
}

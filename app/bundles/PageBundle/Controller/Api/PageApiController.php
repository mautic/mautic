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
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * Class PageApiController
 */
class PageApiController extends CommonApiController
{

    /**
     * {@inheritdoc}
     */
    public function initialize(FilterControllerEvent $event)
    {
        parent::initialize($event);
        $this->model           = $this->factory->getModel('page');
        $this->entityClass     = 'Mautic\PageBundle\Entity\Page';
        $this->entityNameOne   = 'page';
        $this->entityNameMulti = 'pages';
        $this->permissionBase  = 'page:pages';
        $this->serializerGroups = array('pageDetails', 'categoryList', 'publishDetails');
    }

    /**
     * Obtains a list of pages
     *
     * @ApiDoc(
     *   section = "Pages",
     *   description = "Obtains a list of pages",
     *   statusCodes = {
     *     200 = "Returned when successful"
     *   },
     *   filters={
     *      {"name"="start", "dataType"="integer", "required"=false, "description"="Set the record to start with."},
     *      {"name"="limit", "dataType"="integer", "required"=false, "description"="Limit the number of records to retrieve."},
     *      {"name"="published", "dataType"="integer", "required"=false, "description"="If set to one, will return only published items."},
     *      {"name"="filter", "dataType"="string", "required"=false, "description"="A string in which to filter the results by."},
     *      {"name"="orderBy", "dataType"="string", "required"=false, "pattern"="(id|name|alias)", "description"="Table column in which to sort the results by."},
     *      {"name"="orderByDir", "dataType"="string", "required"=false, "pattern"="(ASC|DESC)", "description"="Direction in which to sort results by."}
     *   }
     * )
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getEntitiesAction()
    {
        if (!$this->security->isGranted('page:pages:viewother')) {
            $this->listFilters = array(
                'column' => 'p.createdBy',
                'expr'   => 'eq',
                'value'  => $this->factory->getUser()
            );
        }

        //get parent level only
        $this->listFilters[] = array(
            'column' => 'p.variantParent',
            'expr' => 'isNull'
        );

        $this->listFilters[] = array(
            'column' => 'p.translationParent',
            'expr' => 'isNull'
        );

        return parent::getEntitiesAction();
    }

    /**
     * Obtains a specific page
     *
     * @ApiDoc(
     *   section = "Pages",
     *   description = "Obtains a specific page",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     404 = "Returned if the page was not found"
     *   }
     * )
     *
     * @param int $id Page ID
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function getEntityAction($id)
    {
        return parent::getEntityAction($id);
    }

    /**
     * {@inheritdoc}
     */
    protected function preSerializeEntity(&$entity, $action = 'view')
    {
        $entity->url = $this->model->generateUrl($entity);
    }
}

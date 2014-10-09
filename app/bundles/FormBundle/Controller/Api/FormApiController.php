<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * Class FormApiController
 *
 * @package Mautic\FormBundle\Controller\Api
 */
class FormApiController extends CommonApiController
{

    public function initialize(FilterControllerEvent $event)
    {
        parent::initialize($event);
        $this->model           = $this->factory->getModel('form');
        $this->entityClass     = 'Mautic\FormBundle\Entity\Form';
        $this->entityNameOne   = 'form';
        $this->entityNameMulti = 'forms';
        $this->permissionBase  = 'form:forms';
        $this->serializerGroups = array('formDetails', 'categoryList', 'publishDetails');
    }

    /**
     * Obtains a list of forms
     *
     * @ApiDoc(
     *   section = "Forms",
     *   description = "Obtains a list of forms",
     *   statusCodes = {
     *     200 = "Returned when successful"
     *   },
     *   filters={
     *      {"name"="start", "dataType"="integer", "required"=false, "description"="Set the record to start with."},
     *      {"name"="limit", "dataType"="integer", "required"=false, "description"="Limit the number of records to retrieve."},
     *      {"name"="filter", "dataType"="string", "required"=false, "description"="A string in which to filter the results by."},
     *      {"name"="published", "dataType"="integer", "required"=false, "description"="If set to one, will return only published items."},
     *      {"name"="orderBy", "dataType"="string", "required"=false, "pattern"="(id|name|alias)", "description"="Table column in which to sort the results by."},
     *      {"name"="orderByDir", "dataType"="string", "required"=false, "pattern"="(ASC|DESC)", "description"="Direction in which to sort results by."}
     *   }
     * )
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getEntitiesAction()
    {
        if (!$this->security->isGranted('form:forms:viewother')) {
            $this->listFilters = array(
                'column' => 'f.createdBy',
                'expr'   => 'eq',
                'value'  => $this->factory->getUser()
            );
        }
        return parent::getEntitiesAction();
    }

    /**
     * Obtains a specific form
     *
     * @ApiDoc(
     *   section = "Forms",
     *   description = "Obtains a specific form",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     404 = "Returned if the form was not found"
     *   }
     * )
     *
     * @param int $id Form ID
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function getEntityAction($id)
    {
        return parent::getEntityAction($id);
    }

    /**
     * {@inheritdoc}
     *
     * @param $entity
     * @param $view
     */
    protected function preSerializeEntity(&$entity, $action = 'view')
    {
        $entity->automaticJs = '<script type="text/javascript" src="'.$this->generateUrl('mautic_form_generateform', array('id' => $entity->getId()), true).'"></script>';
    }
}
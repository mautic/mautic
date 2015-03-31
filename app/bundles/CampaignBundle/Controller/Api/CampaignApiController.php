<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Controller\Api;

use FOS\RestBundle\Util\Codes;
use Mautic\ApiBundle\Controller\CommonApiController;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * Class CampaignApiController
 *
 * @package Mautic\CampaignBundle\Controller\Api
 */
class CampaignApiController extends CommonApiController
{

    public function initialize(FilterControllerEvent $event)
    {
        parent::initialize($event);
        $this->model           = $this->factory->getModel('campaign');
        $this->entityClass     = 'Mautic\CampaignBundle\Entity\Campaign';
        $this->entityNameOne   = 'campaign';
        $this->entityNameMulti = 'campaigns';
        $this->permissionBase  = 'campaign:campaigns';
        $this->serializerGroups = array("campaignDetails", "categoryList", "publishDetails");
    }

    /**
     * Obtains a list of campaigns
     *
     * @ApiDoc(
     *   section = "Campaigns",
     *   description = "Obtains a list of campaigns",
     *   statusCodes = {
     *     200 = "Returned when successful"
     *   },
     *   output={
     *      "class"="Mautic\CampaignBundle\Entity\Campaign",
     *      "groups"={"campaignDetails", "categoryList", "publishDetails"}
     *   },
     *   filters={
     *      {"name"="start", "dataType"="integer", "required"=false, "description"="Set the record to start with."},
     *      {"name"="limit", "dataType"="integer", "required"=false, "description"="Limit the number of records to retrieve."},
     *      {"name"="filter", "dataType"="string", "required"=false, "description"="A string in which to filter the results by."},
     *      {"name"="published", "dataType"="integer", "required"=false, "description"="If set to one, will return only published items."},
     *      {"name"="orderBy", "dataType"="string", "required"=false, "pattern"="(id|name|isPublished)", "description"="Table column in which to sort the results by."},
     *      {"name"="orderByDir", "dataType"="string", "required"=false, "pattern"="(ASC|DESC)", "description"="Direction in which to sort results by."}
     *   }
     * )
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getEntitiesAction()
    {
        return parent::getEntitiesAction();
    }

    /**
     * Obtains a specific campaign
     *
     * @ApiDoc(
     *   section = "Campaigns",
     *   description = "Obtains a specific campaign",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     404 = "Returned if the campaign was not found"
     *   },
     *   output={
     *      "class"="Mautic\CampaignBundle\Entity\Campaign",
     *      "groups"={"campaignDetails", "categoryList", "publishDetails"}
     *   }
     * )
     *
     * @param int $id Campaign ID
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function getEntityAction($id)
    {
        return parent::getEntityAction($id);
    }

    /**
     * Adds a lead to a campaign
     *
     * @ApiDoc(
     *   section = "Campaigns",
     *   description = "Adds a lead to a campaign",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     404 = "Returned if the campaign or lead was not found"
     *   }
     * )
     *
     * @param int $id     Campaign ID
     * @param int $leadId Lead ID
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function addLeadAction($id, $leadId)
    {
        $entity = $this->model->getEntity($id);
        if (null !== $entity) {
            $leadModel = $this->factory->getModel('lead');
            $lead      = $leadModel->getEntity($leadId);

            if ($lead == null) {
                return $this->notFound();
            } elseif (!$this->security->hasEntityAccess('lead:leads:editown', 'lead:leads:editother', $lead->getOwner())) {
                return $this->accessDenied();
            }

            $this->model->addLead($entity, $leadId);

            $view = $this->view(array('success' => 1), Codes::HTTP_OK);

            return $this->handleView($view);

        }

        return $this->notFound();
    }

    /**
     * Removes given lead from a campaign
     *
     * @ApiDoc(
     *   section = "Campaigns",
     *   description = "Removes a lead from a campaign",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     404 = "Returned if the campaign or lead was not found"
     *   }
     * )
     *
     * @param int $id     Campaign ID
     * @param int $leadId Lead ID
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function removeLeadAction($id, $leadId)
    {
        $entity = $this->model->getEntity($id);
        if (null !== $entity) {
            $leadModel = $this->factory->getModel('lead');
            $lead      = $leadModel->getEntity($leadId);

            if ($lead == null) {
                return $this->notFound();
            } elseif (!$this->security->hasEntityAccess('lead:leads:editown', 'lead:leads:editother', $lead->getOwner())) {
                return $this->accessDenied();
            }

            $this->model->removeLead($entity, $leadId);

            $view = $this->view(array('success' => 1), Codes::HTTP_OK);

            return $this->handleView($view);

        }

        return $this->notFound();
    }
}
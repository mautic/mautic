<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Controller\Api;

use FOS\RestBundle\Util\Codes;
use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * Class EmailApiController
 *
 * @package Mautic\EmailBundle\Controller\Api
 */
class EmailApiController extends CommonApiController
{

    public function initialize(FilterControllerEvent $event)
    {
        parent::initialize($event);
        $this->model           = $this->factory->getModel('email');
        $this->entityClass     = 'Mautic\EmailBundle\Entity\Email';
        $this->entityNameOne   = 'email';
        $this->entityNameMulti = 'emails';
        $this->permissionBase  = 'email:emails';
        $this->serializerGroups = array("emailDetails", "categoryList", "publishDetails");
    }

    /**
     * Obtains a list of emails
     *
     * @ApiDoc(
     *   section = "Emails",
     *   description = "Obtains a list of emails",
     *   statusCodes = {
     *     200 = "Returned when successful"
     *   },
     *   output={
     *      "class"="Mautic\EmailBundle\Entity\Email",
     *      "groups"={"emailDetails", "categoryList", "publishDetails"}
     *   },
     *   filters={
     *      {"name"="start", "dataType"="integer", "required"=false, "description"="Set the record to start with."},
     *      {"name"="limit", "dataType"="integer", "required"=false, "description"="Limit the number of records to retrieve."},
     *      {"name"="filter", "dataType"="string", "required"=false, "description"="A string in which to filter the results by."},
     *      {"name"="published", "dataType"="integer", "required"=false, "description"="If set to one, will return only published items."},
     *      {"name"="orderBy", "dataType"="string", "required"=false, "pattern"="(id|subject|lang|readCount|sentCount|isPublished)", "description"="Table column in which to sort the results by."},
     *      {"name"="orderByDir", "dataType"="string", "required"=false, "pattern"="(ASC|DESC)", "description"="Direction in which to sort results by."}
     *   }
     * )
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getEntitiesAction()
    {
        if (!$this->security->isGranted('email:emails:viewother')) {
            $this->listFilters[] =
            array(
                'column' => 'e.createdBy',
                'expr'   => 'eq',
                'value'  => $this->factory->getUser()
            );
        }

        //get parent level only
        $this->listFilters[] = array(
            'column' => 'e.variantParent',
            'expr' => 'isNull'
        );

        return parent::getEntitiesAction();
    }

    /**
     * Obtains a specific email
     *
     * @ApiDoc(
     *   section = "Emails",
     *   description = "Obtains a specific email",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     404 = "Returned if the email was not found"
     *   },
     *   output={
     *      "class"="Mautic\EmailBundle\Entity\Email",
     *      "groups"={"emailDetails", "categoryList", "publishDetails"}
     *   }
     * )
     *
     * @param int $id Email ID
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function getEntityAction($id)
    {
        return parent::getEntityAction($id);
    }

    /**
     * Sends the email to it's assigned lists
     *
     * @ApiDoc(
     *   section = "Emails",
     *   description = "Sends the email to it's assigned lists",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     404 = "Returned if the email was not found"
     *   }
     * )
     *
     * @param int $id     Email ID
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function sendAction($id)
    {
        $entity = $this->model->getEntity($id);
        if (null !== $entity) {
            if (!$this->checkEntityAccess($entity, 'view')) {
                return $this->accessDenied();
            }

            $this->model->sendEmailToLists($entity);

            $view = $this->view(array('success' => 1), Codes::HTTP_OK);

            return $this->handleView($view);

        }

        return $this->notFound();
    }

    /**
     * Sends the email to a specific lead
     *
     * @ApiDoc(
     *   section = "Emails",
     *   description = "Sends the email to a specific lead",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     404 = "Returned if the email was not found"
     *   },
     *   parameters={
     *       {"name"="tokens", "dataType"="array", "required"=false, "description"="Array of tokens to search and replace with the assigned values"}
     *   }
     * )
     *
     * @param int $id     Email ID
     * @param int $leadId Lead ID
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function sendLeadAction($id, $leadId)
    {
        $entity = $this->model->getEntity($id);
        if (null !== $entity) {
            if (!$this->checkEntityAccess($entity, 'view')) {
                return $this->accessDenied();
            }

            $leadModel = $this->factory->getModel('lead');
            $lead      = $leadModel->getEntity($leadId);

            if ($lead == null) {
                return $this->notFound();
            } elseif (!$this->security->hasEntityAccess('lead:leads:viewown', 'lead:leads:viewother', $lead->getOwner())) {
                return $this->accessDenied();
            }

            $post   = $this->request->request->all();
            $tokens = (!empty($post['tokens'])) ? $post['tokens'] : array();

            $cleantokens = array_map(function($v) {
                return InputHelper::clean($v);
            }, $tokens);

            $leadFields = array_merge(array('id' => $leadId), $leadModel->flattenFields($lead->getFields()));

            $this->model->sendEmail ($entity, $leadFields, array(
                'source' => array('api', 0),
                'tokens' => $cleantokens
            ));

            $view = $this->view(array('success' => 1), Codes::HTTP_OK);

            return $this->handleView($view);
        }

        return $this->notFound();
    }
}
<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Controller\Api;

use FOS\RestBundle\Util\Codes;
use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\LeadBundle\Controller\LeadAccessTrait;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Class EmailApiController.
 */
class EmailApiController extends CommonApiController
{
    use LeadAccessTrait;

    public function initialize(FilterControllerEvent $event)
    {
        $this->model            = $this->getModel('email');
        $this->entityClass      = 'Mautic\EmailBundle\Entity\Email';
        $this->entityNameOne    = 'email';
        $this->entityNameMulti  = 'emails';
        $this->serializerGroups = ['emailDetails', 'categoryList', 'publishDetails', 'assetList', 'formList', 'leadListList'];
        $this->dataInputMasks   = [
            'customHtml'     => 'html',
            'dynamicContent' => [
                'content' => 'html',
                'filters' => [
                    'content' => 'html',
                ],
            ],
        ];

        parent::initialize($event);
    }

    /**
     * Obtains a list of emails.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getEntitiesAction()
    {
        //get parent level only
        $this->listFilters[] = [
            'column' => 'e.variantParent',
            'expr'   => 'isNull',
        ];

        return parent::getEntitiesAction();
    }

    /**
     * Sends the email to it's assigned lists.
     *
     * @param int $id Email ID
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function sendAction($id)
    {
        $entity = $this->model->getEntity($id);

        if (null !== $entity || !$entity->isPublished()) {
            return $this->notFound();
        }

        if (!$this->checkEntityAccess($entity, 'view')) {
            return $this->accessDenied();
        }

        $lists = $this->request->request->get('lists', null);
        $limit = $this->request->request->get('limit', null);

        list($count, $failed) = $this->model->sendEmailToLists($entity, $lists, $limit);

        $view = $this->view(
            [
                'success'          => 1,
                'sentCount'        => $count,
                'failedRecipients' => $failed,
            ],
            Codes::HTTP_OK
        );

        return $this->handleView($view);
    }

    /**
     * Sends the email to a specific lead.
     *
     * @param int $id     Email ID
     * @param int $leadId Lead ID
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function sendLeadAction($id, $leadId)
    {
        $entity = $this->model->getEntity($id);
        if (null !== $entity) {
            if (!$this->checkEntityAccess($entity, 'view')) {
                return $this->accessDenied();
            }

            /** @var Lead $lead */
            $lead = $this->checkLeadAccess($leadId, 'edit');
            if ($lead instanceof Response) {
                return $lead;
            }

            $post     = $this->request->request->all();
            $tokens   = (!empty($post['tokens'])) ? $post['tokens'] : [];
            $response = ['success' => false];

            $cleanTokens = [];

            foreach ($tokens as $token => $value) {
                $value = InputHelper::clean($value);
                if (!preg_match('/^{.*?}$/', $token)) {
                    $token = '{'.$token.'}';
                }

                $cleanTokens[$token] = $value;
            }

            $leadFields = array_merge(['id' => $leadId], $lead->getProfileFields());

            $result = $this->model->sendEmail(
                $entity,
                $leadFields,
                [
                    'source'        => ['api', 0],
                    'tokens'        => $cleanTokens,
                    'return_errors' => true,
                ]
            );

            if (is_bool($result)) {
                $response['success'] = $result;
            } else {
                $response['failed'] = $result;
            }

            $view = $this->view($response, Codes::HTTP_OK);

            return $this->handleView($view);
        }

        return $this->notFound();
    }
}

<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\Controller\Api;

use FOS\RestBundle\Util\Codes;
use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\LeadBundle\Controller\LeadAccessTrait;
use Mautic\SmsBundle\Model\SmsModel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Class SmsApiController.
 */
class SmsApiController extends CommonApiController
{
    use LeadAccessTrait;
    /**
     * @var SmsModel
     */
    protected $model;

    /**
     * {@inheritdoc}
     */
    public function initialize(FilterControllerEvent $event)
    {
        $this->model           = $this->getModel('sms');
        $this->entityClass     = 'Mautic\SmsBundle\Entity\Sms';
        $this->entityNameOne   = 'sms';
        $this->entityNameMulti = 'smses';

        parent::initialize($event);
    }

    /**
     * Obtains a list of emails.
     *
     * @return Response
     */
    public function receiveAction()
    {
        $body = $this->request->get('Body');
        $from = $this->request->get('From');

        if ($body === 'STOP' && $this->factory->getHelper('sms')->unsubscribe($from)) {
            return new Response('<Response><Sms>You have been unsubscribed.</Sms></Response>', 200, ['Content-Type' => 'text/xml; charset=utf-8']);
        }

        // Return an empty response
        return new Response();
    }

    /**
     * @param $id
     * @param $contactId
     *
     * @return JsonResponse|Response
     */
    public function sendAction($id, $contactId)
    {
        if (!$this->get('mautic.sms.transport_chain')->getEnabledTransports()) {
            return new JsonResponse(json_encode(['error' => ['message' => 'SMS transport is disabled.', 'code' => Codes::HTTP_EXPECTATION_FAILED]]));
        }

        $message  = $this->model->getEntity((int) $id);

        if (is_null($message)) {
            return $this->notFound();
        }

        $contact  = $this->checkLeadAccess($contactId, 'edit');

        if ($contact instanceof Response) {
            return $this->accessDenied();
        }

        $this->get('monolog.logger.mautic')->addDebug("Sending SMS #{$id} to contact #{$contactId}", ['originator'=>'api']);

        try {
            $response = $this->model->sendSms($message, $contact, ['channel' => 'api'])[$contact->getId()];
        } catch (\Exception $e) {
            $this->get('monolog.logger.mautic')->addError($e->getMessage(), ['error'=>(array) $e]);

            return new Response('Interval server error', Codes::HTTP_INTERNAL_SERVER_ERROR);
        }

        $success  = !empty($response['sent']);

        if (!$success) {
            $this->get('monolog.logger.mautic')->addError('Failed to send SMS.', ['error'=> $response['status']]);
        }

        $view = $this->view(
            [
                'success'           => $success,
                'status'            => $this->get('translator')->trans($response['status']),
                'result'            => $response,
                'errors'            => $success ? [] : [['message'=>$response['status']]],
            ],
            Codes::HTTP_OK  //  200 - is legacy, we cannot change it yet
        );

        return $this->handleView($view);
    }
}

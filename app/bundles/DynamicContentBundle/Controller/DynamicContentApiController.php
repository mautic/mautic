<?php

namespace Mautic\DynamicContentBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\DynamicContentBundle\Helper\DynamicContentHelper;
use Mautic\LeadBundle\Helper\ContactRequestHelper;
use Mautic\LeadBundle\Tracker\Service\DeviceTrackingService\DeviceTrackingServiceInterface;
use Mautic\PageBundle\Model\PageModel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DynamicContentApiController extends CommonController
{
    /**
     * @return mixed
     */
    public function processAction(Request $request, $objectAlias)
    {
        // Don't store a visitor with this request
        defined('MAUTIC_NON_TRACKABLE_REQUEST') || define('MAUTIC_NON_TRACKABLE_REQUEST', 1);

        $method = strtolower($request->getMethod());
        if (method_exists($this, $method.'Action')) {
            return $this->forwardWithPost(
                static::class.'::'.$method.'Action',
                $request->request->all(),
                [
                    'objectAlias' => $objectAlias,
                ],
                $request->query->all()
            );
        } else {
            throw new HttpException(Response::HTTP_FORBIDDEN, 'This endpoint is not able to process '.strtoupper($method).' requests.');
        }
    }

    public function getAction(
        Request $request,
        DynamicContentHelper $helper,
        DeviceTrackingServiceInterface $deviceTrackingService,
        ContactRequestHelper $contactRequestHelper,
        $objectAlias
    ): Response {
        /** @var PageModel $pageModel */
        $pageModel = $this->getModel('page');

        $lead          = $contactRequestHelper->getContactFromQuery($pageModel->getHitQuery($request));
        $content       = $helper->getDynamicContentForLead($objectAlias, $lead);
        $trackedDevice = $deviceTrackingService->getTrackedDevice();
        $deviceId      = (null === $trackedDevice ? null : $trackedDevice->getTrackingId());

        return empty($content)
            ? new Response('', Response::HTTP_NO_CONTENT)
            : new JsonResponse(
                [
                    'content'   => $content,
                    'id'        => $lead->getId(),
                    'sid'       => $deviceId,
                    'device_id' => $deviceId,
                ]
            );
    }
}

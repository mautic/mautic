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
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Service\Attribute\Required;

final class DynamicContentApiController extends CommonController
{
    private PageModel $pageModel;

    #[Required]
    public function autowireDynamicContentApiController(
        PageModel $pageModel,
    ): void {
        $this->pageModel = $pageModel;
    }

    #[Route(
        '/dwc/{objectAlias}',
        name: 'mautic_api_dynamicContent_action',
        priority: -219
    )]
    public function processAction(Request $request, $objectAlias): Response
    {
        // Don't store a visitor with this request
        defined('MAUTIC_NON_TRACKABLE_REQUEST') || define('MAUTIC_NON_TRACKABLE_REQUEST', 1);

        $method = strtolower($request->getMethod());
        if (method_exists($this, $method.'Action')) {
            return $this->forwardWithPost(
                self::class.'::'.$method.'Action',
                $request->request->all(),
                [
                    'objectAlias' => $objectAlias,
                ],
                $request->query->all()
            );
        }
        throw new HttpException(Response::HTTP_FORBIDDEN, 'This endpoint is not able to process '.strtoupper($method).' requests.');
    }

    #[Route(
        '/dwc',
        name: 'mautic_api_dynamicContent_index',
        priority: -218
    )]
    public function getAction(
        Request $request,
        DynamicContentHelper $helper,
        DeviceTrackingServiceInterface $deviceTrackingService,
        ContactRequestHelper $contactRequestHelper,
        string $objectAlias,
    ): Response {
        $lead          = $contactRequestHelper->getContactFromQuery($this->pageModel->getHitQuery($request));
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

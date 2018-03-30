<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DynamicContentBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\DynamicContentBundle\Helper\DynamicContentHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Tracker\Service\DeviceTrackingService\DeviceTrackingServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class DynamicContentApiController.
 */
class DynamicContentApiController extends CommonController
{
    /**
     * @param $objectAlias
     *
     * @return mixed
     */
    public function processAction($objectAlias)
    {
        $method = $this->request->getMethod();
        if (method_exists($this, $method.'Action')) {
            return $this->{$method.'Action'}($objectAlias);
        } else {
            throw new HttpException(Response::HTTP_FORBIDDEN, 'This endpoint is not able to process '.strtoupper($method).' requests.');
        }
    }

    public function getAction($objectAlias)
    {
        /** @var LeadModel $model */
        $model = $this->getModel('lead');
        /** @var DynamicContentHelper $helper */
        $helper = $this->get('mautic.helper.dynamicContent');
        /** @var DeviceTrackingServiceInterface $deviceTrackingService */
        $deviceTrackingService = $this->get('mautic.lead.service.device_tracking_service');

        /** @var Lead $lead */
        $lead    = $model->getContactFromRequest();
        $content = $helper->getDynamicContentForLead($objectAlias, $lead);

        if (empty($content)) {
            $content = $helper->getDynamicContentSlotForLead($objectAlias, $lead);
        }

        $trackedDevice = $deviceTrackingService->getTrackedDevice();
        $deviceId      = ($trackedDevice === null ? null : $trackedDevice->getTrackingId());

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

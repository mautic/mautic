<?php

namespace MauticPlugin\MauticFocusBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Helper\TrackingPixelHelper;
use Mautic\LeadBundle\Helper\ContactRequestHelper;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\LeadBundle\Tracker\Service\DeviceTrackingService\DeviceTrackingServiceInterface;
use Mautic\PageBundle\Model\PageModel;
use MauticPlugin\MauticFocusBundle\Entity\Stat;
use MauticPlugin\MauticFocusBundle\Event\FocusViewEvent;
use MauticPlugin\MauticFocusBundle\FocusEvents;
use MauticPlugin\MauticFocusBundle\Helper\FocusFilterHelper;
use MauticPlugin\MauticFocusBundle\Model\FocusModel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Service\Attribute\Required;

final class PublicController extends CommonController
{
    private FocusModel $focusModel;

    #[Required]
    public function autowirePublicController(
        FocusModel $focusModel,
    ): void {
        $this->focusModel = $focusModel;
    }

    /**
     * Returns filter-based focus items matching the tracked contact
     * so the tracking script can inject them into the page.
     */
    public function checkAction(
        Request $request,
        ContactRequestHelper $contactRequestHelper,
        DeviceTrackingServiceInterface $deviceTrackingService,
        FocusFilterHelper $focusFilterHelper,
        PageModel $pageModel,
    ): Response {
        // Don't store a visitor with this request
        defined('MAUTIC_NON_TRACKABLE_REQUEST') || define('MAUTIC_NON_TRACKABLE_REQUEST', 1);

        // Cheap existence check before running the contact tracking pipeline
        if (!$focusFilterHelper->hasFilterBasedItems()) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        $lead = $contactRequestHelper->getContactFromQuery($pageModel->getHitQuery($request));

        $focusItems = [];
        if ($lead && $lead->getId()) {
            foreach ($focusFilterHelper->getMatchingItemIds($lead) as $focusId) {
                $focusItems[] = [
                    'id'     => $focusId,
                    'js_url' => $this->generateUrl('mautic_focus_generate', ['id' => $focusId], UrlGeneratorInterface::ABSOLUTE_URL),
                ];
            }
        }

        if (empty($focusItems)) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        $trackedDevice = $deviceTrackingService->getTrackedDevice();
        $deviceId      = $trackedDevice?->getTrackingId();

        return new JsonResponse(
            [
                'focus_items' => $focusItems,
                'id'          => $lead->getId(),
                'sid'         => $deviceId,
                'device_id'   => $deviceId,
            ]
        );
    }

    public function generateAction($id): Response
    {
        // Don't store a visitor with this request
        defined('MAUTIC_NON_TRACKABLE_REQUEST') || define('MAUTIC_NON_TRACKABLE_REQUEST', 1);
        $focus = $this->focusModel->getEntity($id);

        if ($focus) {
            if (!$focus->isPublished()) {
                return new Response('', Response::HTTP_NOT_FOUND);
            }

            $content = $this->focusModel->generateJavascript($focus);

            return new Response($content, 200, ['Content-Type' => 'application/javascript']);
        }

        return new Response('', Response::HTTP_NOT_FOUND);
    }

    public function viewPixelAction(Request $request, ContactTracker $contactTracker): Response
    {
        $id = $request->get('id', false);
        if ($id) {
            $focus = $this->focusModel->getEntity($id);

            $lead = $contactTracker->getContact();

            if ($focus && $focus->isPublished() && $lead) {
                $stat = $this->focusModel->addStat($focus, Stat::TYPE_NOTIFICATION, $request, $lead);
                if ($stat && $this->dispatcher->hasListeners(FocusEvents::FOCUS_ON_VIEW)) {
                    $event = new FocusViewEvent($stat);
                    $this->dispatcher->dispatch($event, FocusEvents::FOCUS_ON_VIEW);
                    unset($event);
                }
            }
        }

        return TrackingPixelHelper::getResponse($request);
    }
}

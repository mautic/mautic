<?php

namespace MauticPlugin\MauticFocusBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Helper\TrackingPixelHelper;
use Mautic\LeadBundle\Tracker\ContactTracker;
use MauticPlugin\MauticFocusBundle\Entity\Stat;
use MauticPlugin\MauticFocusBundle\Event\FocusViewEvent;
use MauticPlugin\MauticFocusBundle\FocusEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicController extends CommonController
{
    /**
     * @return array|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function generateAction($id)
    {
        // Don't store a visitor with this request
        defined('MAUTIC_NON_TRACKABLE_REQUEST') || define('MAUTIC_NON_TRACKABLE_REQUEST', 1);

        /** @var \MauticPlugin\MauticFocusBundle\Model\FocusModel $model */
        $model = $this->getModel('focus');
        $focus = $model->getEntity($id);

        if ($focus) {
            if (!$focus->isPublished()) {
                return new Response('', Response::HTTP_NOT_FOUND);
            }

            $content  = $model->generateJavascript($focus, false, MAUTIC_ENV == 'dev');

            return new Response($content, 200, ['Content-Type' => 'application/javascript']);
        } else {
            return new Response('', Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * @return Response
     */
    public function viewPixelAction(Request $request, ContactTracker $contactTracker)
    {
        $id = $request->get('id', false);
        if ($id) {
            /** @var \MauticPlugin\MauticFocusBundle\Model\FocusModel $model */
            $model = $this->getModel('focus');
            $focus = $model->getEntity($id);

            $lead = $contactTracker->getContact();

            if ($focus && $focus->isPublished() && $lead) {
                $stat = $model->addStat($focus, Stat::TYPE_NOTIFICATION, $request, $lead);
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

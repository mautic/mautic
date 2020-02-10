<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticFocusBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Helper\TrackingPixelHelper;
use Mautic\LeadBundle\Tracker\ContactTracker;
use MauticPlugin\MauticFocusBundle\Entity\Stat;
use MauticPlugin\MauticFocusBundle\Event\FocusViewEvent;
use MauticPlugin\MauticFocusBundle\FocusEvents;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class class PublicController extends CommonController.
 */
class PublicController extends CommonController
{
    /**
     * @param $id
     *
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
                return new Response('', 200, ['Content-Type' => 'application/javascript']);
            }

            $content  = $model->generateJavascript($focus, false, (MAUTIC_ENV == 'dev'));

            return new Response($content, 200, ['Content-Type' => 'application/javascript']);
        } else {
            return new Response('', 200, ['Content-Type' => 'application/javascript']);
        }
    }

    /**
     * @return Response
     */
    public function viewPixelAction()
    {
        $id = $this->request->get('id', false);
        if ($id) {
            /** @var \MauticPlugin\MauticFocusBundle\Model\FocusModel $model */
            $model = $this->getModel('focus');
            $focus = $model->getEntity($id);

            /** @var ContactTracker $contactTracker */
            $contactTracker = $this->get('mautic.tracker.contact');
            $lead           = $contactTracker->getContact();

            if ($focus && $focus->isPublished() && $lead) {
                $stat = $model->addStat($focus, Stat::TYPE_NOTIFICATION, $this->request, $lead);
                if ($stat && $this->dispatcher->hasListeners(FocusEvents::FOCUS_ON_VIEW)) {
                    $event = new FocusViewEvent($stat);
                    $this->dispatcher->dispatch(FocusEvents::FOCUS_ON_VIEW, $event);
                    unset($event);
                }
            }
        }

        return TrackingPixelHelper::getResponse($this->request);
    }
}

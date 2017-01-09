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
use MauticPlugin\MauticFocusBundle\Entity\Stat;
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
        /** @var \MauticPlugin\MauticFocusBundle\Model\FocusModel $model */
        $model = $this->getModel('focus');
        $focus = $model->getEntity($id);

        if ($focus) {
            if (!$focus->isPublished()) {
                return new Response('');
            }

            $content  = (MAUTIC_ENV == 'dev') ? $model->generateJavascript($focus, false, true) : $model->getContent($focus);
            $response = new Response($content);

            return $response;
        } else {
            return new Response('');
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

            if ($focus && $focus->isPublished()) {
                $model->addStat($focus, Stat::TYPE_NOTIFICATION, $this->request, $this->getModel('lead')->getCurrentLead());
            }
        }

        $response = TrackingPixelHelper::getResponse($this->request);

        return $response;
    }
}

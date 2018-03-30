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
        // Don't store a visitor with this request
        defined('MAUTIC_NON_TRACKABLE_REQUEST') || define('MAUTIC_NON_TRACKABLE_REQUEST', 1);

        $method = $this->request->getMethod();
        if (method_exists($this, $method.'Action')) {
            return $this->{$method.'Action'}($objectAlias);
        } else {
            throw new HttpException(Response::HTTP_FORBIDDEN, 'This endpoint is not able to process '.strtoupper($method).' requests.');
        }
    }

    public function getAction($objectAlias)
    {
        // Don't store a visitor with this request
        defined('MAUTIC_NON_TRACKABLE_REQUEST') || define('MAUTIC_NON_TRACKABLE_REQUEST', 1);

        /** @var LeadModel $model */
        $model = $this->getModel('lead');
        /** @var DynamicContentHelper $helper */
        $helper = $this->get('mautic.helper.dynamicContent');
        /** @var Lead $lead */
        $lead    = $model->getContactFromRequest();
        $content = $helper->getDynamicContentForLead($objectAlias, $lead);

        if (empty($content)) {
            $content = $helper->getDynamicContentSlotForLead($objectAlias, $lead);
        }

        return empty($content)
            ? new Response('', Response::HTTP_NO_CONTENT)
            : new JsonResponse([
                'content' => $content,
                'id'      => $lead->getId(),
                'sid'     => $model->getTrackingCookie()[0],
            ]);
    }
}

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
use Mautic\DynamicContentBundle\Entity\DynamicContent;
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
        $lead    = $this->getModel('lead')->getCurrentLead();
        $content = $this->get('mautic.helper.dynamicContent')->getDynamicContentForLead($objectAlias, $lead);
        if (empty($content)) {
            $content = $this->get('mautic.helper.dynamicContent')->getDynamicContentSlotForLead($objectAlias, $lead);
        }

        return empty($content) ? new Response('', Response::HTTP_NO_CONTENT) : new Response($content);
    }
}

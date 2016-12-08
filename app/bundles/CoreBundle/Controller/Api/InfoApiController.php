<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;

/**
 * Class InfoApiController.
 */
class InfoApiController extends CommonApiController
{
    /**
     * Returns the Mautic version.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function versionAction()
    {
        $response = ['version' => $this->get('kernel')->getVersion()];

        return $this->handleView($this->view($response));
    }
}

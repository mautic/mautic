<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\Controller\Api;

use FOS\RestBundle\Util\Codes;
use Mautic\ApiBundle\Controller\CommonApiController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class PluginApiController.
 */
class PluginApiController extends CommonApiController
{
    /**
     * @return Response
     */
    public function reloadAction()
    {
        if (!$this->get('mautic.security')->isGranted('plugin:plugins:manage')) {
            return $this->accessDenied();
        }

        $view = $this->view(['response' => $this->get('mautic.plugin.facade.reload')->reloadPlugins()], Codes::HTTP_OK);

        return $this->handleView($view);
    }
}

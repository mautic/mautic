<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Controller\SubscribedEvents;

use Mautic\CoreBundle\Controller\AbstractStandardFormController;
use Mautic\CoreBundle\Helper\BuilderTokenHelper;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class BuilderTokenController.
 *
 * @deprecated 2.6.0 to be removed in 3.0
 */
abstract class BuilderTokenController extends AbstractStandardFormController
{
    /**
     * @param int $page
     *
     * @return JsonResponse
     */
    public function indexAction($page = 1)
    {
        $tokenHelper = new BuilderTokenHelper(
            $this->factory,
            $this->getModelName(),
            $this->getPermissionBase(),
            $this->getBundleName(),
            $this->getTranslationBase()
        );

        if ($permissionSet = $this->getPermissionSet()) {
            $tokenHelper->setPermissionSet($permissionSet);
        }

        $arguments = $this->getEntityArguments();

        $dataArray = [
            'newContent'    => $tokenHelper->getTokenContent($page, $arguments),
            'mauticContent' => 'builder',
        ];

        $response = new JsonResponse($dataArray);

        return $response;
    }

    protected function getBundleName()
    {
        return null;
    }

    protected function getPermissionSet()
    {
        return null;
    }

    /**
     * @return array
     */
    protected function getEntityArguments()
    {
        return [];
    }

    /**
     * @deprecated 2.6.0 to be removed in 3.0; use getPermissionBase() instead
     *
     * @return mixed
     */
    protected function getViewPermissionBase()
    {
        return null;
    }

    /**
     * @return string
     */
    public function getPermissionBase()
    {
        return $this->getViewPermissionBase();
    }

    /**
     * @deprecated 2.6.0 to be removed in 3.0; use getTranslationBase() instead
     */
    protected function getLangVar()
    {
        return null;
    }

    protected function getTranslationBase()
    {
        return $this->getLangVar();
    }
}

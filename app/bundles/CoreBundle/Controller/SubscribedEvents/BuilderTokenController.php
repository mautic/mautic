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

use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Helper\BuilderTokenHelper;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class BuilderTokenController.
 */
abstract class BuilderTokenController extends FormController
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
            $this->getViewPermissionBase(),
            $this->getBundleName(),
            $this->getLangVar()
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

    /**
     * @return mixed
     */
    abstract protected function getModelName();

    /**
     * @return mixed
     */
    protected function getViewPermissionBase()
    {
        return null;
    }

    protected function getBundleName()
    {
        return null;
    }

    protected function getLangVar()
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
}

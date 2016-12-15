<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticFocusBundle\Controller\SubscribedEvents;

/**
 * Class BuilderTokenController.
 */
class BuilderTokenController extends \Mautic\CoreBundle\Controller\SubscribedEvents\BuilderTokenController
{
    /**
     * @return string
     */
    protected function getModelName()
    {
        return 'focus';
    }

    /**
     * @return mixed
     */
    protected function getViewPermissionBase()
    {
        return 'plugin:focus:items';
    }

    protected function getBundleName()
    {
        return 'MauticFocusBundle';
    }

    protected function getLangVar()
    {
        return 'mautic.focus';
    }
}

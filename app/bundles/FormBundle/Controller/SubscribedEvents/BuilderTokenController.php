<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Controller\SubscribedEvents;

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
        return 'form';
    }
}

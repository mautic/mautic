<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Swiftmailer\Transport;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\HttpFoundation\Request;

/**
 * Interface InterfaceCallbackTransport
 */
interface InterfaceCallbackTransport
{
    /**
     * Returns a "transport" string to match the URL path /mailer/{transport}/callback.
     *
     * @return mixed
     */
    public function getCallbackPath();

    /**
     * Handle response.
     *
     * @param Request       $request
     * @param MauticFactory $factory
     *
     * @return array array('bounces' => array('hashID' => 'reason', ...));
     */
    public function handleCallbackResponse(Request $request, MauticFactory $factory);
}

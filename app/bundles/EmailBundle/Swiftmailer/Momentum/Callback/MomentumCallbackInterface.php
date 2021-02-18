<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Swiftmailer\Momentum\Callback;

use Symfony\Component\HttpFoundation\Request;

/**
 * Interface MomentumCallbackInterface.
 */
interface MomentumCallbackInterface
{
    public function processCallbackRequest(Request $request);

    /**
     * @return mixed
     */
    public function processImmediateFeedback(\Swift_Mime_SimpleMessage $message, array $response);
}

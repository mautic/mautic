<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Swiftmailer\Mailgun\Callback;

use Mautic\EmailBundle\Model\TransportCallback;
use Symfony\Component\HttpFoundation\Request;

class MailgunCallback
{
    /**
     * @var TransportCallback
     */
    private $transportCallback;

    public function __construct(TransportCallback $transportCallback)
    {
        $this->transportCallback = $transportCallback;
    }

    public function processCallbackRequest(Request $request)
    {
    }
}

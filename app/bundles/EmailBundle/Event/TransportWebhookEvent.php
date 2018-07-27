<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Event;

use Mautic\EmailBundle\Swiftmailer\Transport\CallbackTransportInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

/**
 * Event triggered when a transport service send Mautic a webhook request.
 */
class TransportWebhookEvent extends Event
{
    /**
     * @var CallbackTransportInterface
     */
    private $transport;

    /**
     * @var Request
     */
    private $request;

    /**
     * @param CallbackTransportInterface $transport
     * @param Request                    $request
     */
    public function __construct(CallbackTransportInterface $transport, Request $request)
    {
        $this->transport = $transport;
        $this->request   = $request;
    }

    /**
     * @return CallbackTransportInterface
     */
    public function getTransport()
    {
        return $this->transport;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        $this->request = $request;
    }
}

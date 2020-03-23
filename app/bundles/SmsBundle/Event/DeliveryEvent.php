<?php

/*
 * @copyright   2020 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\Event;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\SmsBundle\Callback\Event\DeliveryCallbackEvent;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Response;

class DeliveryEvent extends Event
{
    /**
     * @var Response
     */
    private $response;

    /**
     * @var Lead
     */
    private $contact;

    /**
     * @var DeliveryCallbackEvent
     */
    private $deliveryCallbackEvent;

    /**
     * DeliveryEvent constructor.
     *
     * @param Lead                  $contact
     * @param DeliveryCallbackEvent $deliveryCallbackEvent
     */
    public function __construct(Lead $contact, DeliveryCallbackEvent $deliveryCallbackEvent)
    {
        $this->contact               = $contact;
        $this->deliveryCallbackEvent = $deliveryCallbackEvent;
    }

    /**
     * @param Response $response
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;
    }

    /**
     * @return null|Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @return Lead
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * @param Lead $contact
     */
    public function setContact($contact)
    {
        $this->contact = $contact;
    }

    /**
     * @return DeliveryCallbackEvent
     */
    public function getDeliveryCallbackEvent()
    {
        return $this->deliveryCallbackEvent;
    }

    /**
     * @param DeliveryCallbackEvent $deliveryCallbackEvent
     */
    public function setDeliveryCallbackEvent($deliveryCallbackEvent)
    {
        $this->deliveryCallbackEvent = $deliveryCallbackEvent;
    }
}

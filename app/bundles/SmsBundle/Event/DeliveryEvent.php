<?php

/*
 * @copyright   2019 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\Event;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\SmsBundle\Callback\DAO\DeliveryStatusDAO;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Response;

class DeliveryEvent extends Event
{
    /**
     * @var Response
     */
    private $response;

    /**
     * @var string
     */
    private $trackingHash;

    /**
     * @var Lead
     */
    private $contact;

    /**
     * @var DeliveryStatusDAO
     */
    private $deliveryStatusDAO;

    /**
     * DeliveryEvent constructor.
     *
     * @param Lead              $contact
     * @param DeliveryStatusDAO $deliveryStatusDAO
     */
    public function __construct(Lead $contact, DeliveryStatusDAO $deliveryStatusDAO)
    {
        $this->contact           = $contact;
        $this->deliveryStatusDAO = $deliveryStatusDAO;
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
     * @return DeliveryStatusDAO
     */
    public function getDeliveryStatusDAO()
    {
        return $this->deliveryStatusDAO;
    }

    /**
     * @param DeliveryStatusDAO $deliveryStatusDAO
     */
    public function setDeliveryStatusDAO($deliveryStatusDAO)
    {
        $this->deliveryStatusDAO = $deliveryStatusDAO;
    }
}

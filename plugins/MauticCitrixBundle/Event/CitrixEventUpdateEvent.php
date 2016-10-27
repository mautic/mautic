<?php
/**
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCitrixBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;

/**
 * Class CitrixEventUpdateEvent.
 */
class CitrixEventUpdateEvent extends CommonEvent
{
    /**
     * @var
     */
    private $product;
    /**
     * @var
     */
    private $eventName;
    /**
     * @var
     */
    private $eventType;
    /**
     * @var
     */
    private $email;


    /**
     * CitrixEventUpdateEvent constructor.
     *
     * @param $product
     * @param $eventName
     * @param $eventType
     * @param $email
     */
    public function __construct($product, $eventName, $eventType, $email)
    {
        $this->product = $product;
        $this->eventName = $eventName;
        $this->eventType = $eventType;
        $this->email = $email;
    }

    /**
     * @return mixed
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @return mixed
     */
    public function getEventName()
    {
        return $this->eventName;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return mixed
     */
    public function getEventType()
    {
        return $this->eventType;
    }

}

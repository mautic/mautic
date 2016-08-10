<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\NotificationBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\EmailBundle\Entity\Email;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\NotificationBundle\Entity\Notification;

/**
 * Class NotificationSendEvent
 *
 * @package Mautic\NotificationBundle\Event
 */
class NotificationSendEvent extends CommonEvent
{
    /**
     * @var Lead
     */
    private $lead;

    /**
     * @var Notification
     */
    protected $entity;

    /**
     * @param array      $args
     */
    public function __construct($args = array())
    {
        if (isset($args['lead'])) {
            $this->lead = $args['lead'];
        }
    }

    /**
     * Returns the Email entity
     *
     * @return Notification
     */
    public function getNotification()
    {
        return $this->entity;
    }

    /**
     * @return array
     */
    public function getLead()
    {
        return $this->lead;
    }
}

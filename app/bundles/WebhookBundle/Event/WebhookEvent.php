<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\WebhookBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\WebhookBundle\Entity\Webhook;

/**
 * Class WebhookEvent.
 */
class WebhookEvent extends CommonEvent
{
    /**
     * @param Webhook $webhook
     * @param bool    $isNew
     */
    public function __construct(Webhook &$webhook, $isNew = false)
    {
        $this->entity = &$webhook;
        $this->isNew  = $isNew;
    }

    /**
     * Returns the Webhook entity.
     *
     * @return Webhook
     */
    public function getWebhook()
    {
        return $this->entity;
    }

    /**
     * Sets the Webhook entity.
     *
     * @param Webhook $webhook
     */
    public function setWebhook(Webhook $webhook)
    {
        $this->entity = $webhook;
    }
}

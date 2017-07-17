<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\WebhookBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;

/**
 * Class WebhookSubscriberBase.
 */
class WebhookSubscriberBase extends CommonSubscriber
{
    use WebhookModelTrait;

    /**
     * WebhookSubscriberBase constructor.
     */
    public function __construct()
    {
        @trigger_error(self::class.' has been deprecated as of 2.7.1; use trait '.WebhookModelTrait::class.' instead', E_USER_DEPRECATED);
    }

    /**
     * Look up list of webhooks using the event type as an identifer.
     *
     * @param $type string
     *
     * @return array
     */
    public function getEventWebooksByType($type)
    {
        return $this->webhookModel->getEventWebooksByType($type);
    }
}

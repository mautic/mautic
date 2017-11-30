<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\WebhookBundle\EventListener;

use Mautic\WebhookBundle\Model\WebhookModel;

/**
 * Class WebhookModelTrait.
 */
trait WebhookModelTrait
{
    /**
     * @var WebhookModel
     */
    protected $webhookModel;

    /**
     * @param WebhookModel $webhookModel
     */
    public function setWebhookModel(WebhookModel $webhookModel)
    {
        $this->webhookModel = $webhookModel;
    }
}

<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\WebhookBundle\EventListener;

use Doctrine\ORM\NoResultException;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\WebhookBundle\Model\WebhookModel;
use Mautic\CoreBundle\Factory\MauticFactory;
use JMS\Serializer\SerializationContext;

/**
 * Class LeadSubscriber
 *
 * @package Mautic\Webhook\EventListener
 */
class WebhookSubscriberBase extends CommonSubscriber
{
    /** @var \Mautic\WebhookBundle\Model\WebhookModel $model */
    protected $webhookModel;

    protected $serializerGroups = array();

    public function __construct(MauticFactory $factory)
    {
        parent::__construct($factory);
        $this->webhookModel = $this->factory->getModel('webhook.webhook');
    }

    /**
     * {@inheritdoc}
     */
    static public function getSubscribedEvents() {
        return array();
    }

    /*
     *
     */
    public function getEventWebooksByType($type)
    {
        $eventWebhooks = $this->webhookModel->getEventWebooksByType($type);

        return $eventWebhooks;
    }

    /*
     *
     */
    public function serializeData($entity)
    {
        $context = SerializationContext::create();
        if (!empty($this->serializerGroups)) {
            $context->setGroups($this->serializerGroups);
        }

        //Only include FormEntity properties for the top level entity and not the associated entities
        $context->addExclusionStrategy(
        // Can Use this; just adding full namespace to show
            new \Mautic\ApiBundle\Serializer\Exclusion\PublishDetailsExclusionStrategy()
        );

        //include null values
        $context->setSerializeNull(true);

        // serialize the data and send it as a payload
        $payload = $this->serializer->serialize($entity, 'json', $context);

        return $payload;
    }
}
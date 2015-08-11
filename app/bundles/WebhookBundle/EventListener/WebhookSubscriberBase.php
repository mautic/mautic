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

    public function initialize($model, $entityClass, $entityNameOne, $entityNameMulti)
    {
        $this->model            = $this->factory->getModel($model);
        $this->entityClass      = $entityClass;
        $this->entityNameOne    = $entityNameOne;
        $this->entityNameMulti  = $entityNameMulti;
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

    public function serializeData($entity)
    {
        $context = SerializationContext::create();
        if (!empty($this->serializerGroups)) {
            $context->setGroups($this->serializerGroups);
        }

        $serializer = $this->factory->getSerializer();

        //Only include FormEntity properties for the top level entity and not the associated entities
        $context->addExclusionStrategy(
        // Can Use this; just adding full namespace to show
            new \Mautic\ApiBundle\Serializer\Exclusion\PublishDetailsExclusionStrategy()
        );

        //include null values
        $context->setSerializeNull(true);
        $context->setVersion('1.0');

        $object = new \stdClass();
        $object->hello = 'world';

        $content = $serializer->serialize($object, 'json');

        var_dump($serializer);
        var_dump($content);
        var_dump($entity);
        var_dump($context);
        exit();

        return $content;
    }
}
<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      @dragan-mf
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\RabbitMQBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\Event as Events;
use Mautic\LeadBundle\LeadEvents;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use PhpAmqpLib\Connection\AMQPSSLConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Class LeadSubscriber.
 */
class LeadSubscriber extends CommonSubscriber
{
    /**
     * @var IntegrationHelper
     */
    protected $integrationHelper;

    /**
     * CampaignSubscriber constructor.
     *
     * @param IntegrationHelper $integrationHelper
     */
    public function __construct(IntegrationHelper $integrationHelper)
    {
        $this->integrationHelper = $integrationHelper;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            LeadEvents::LEAD_POST_SAVE      => ['onLeadPostSave', 0],
            LeadEvents::LEAD_POST_DELETE     => ['onLeadPostDelete', 0]
        ];
    }

    /**
     * @param Events\LeadEvent $event
     */
    public function onLeadPostSave(Events\LeadEvent $event)
    {
        $integrationObject = $this->integrationHelper->getIntegrationObject('RabbitMQ');
        $lead = $event->getLead()->convertToArray();
        $settings = $integrationObject->getIntegrationSettings();


        if (false === $integrationObject || !$settings->getIsPublished()) {
            return;
        }


        // The main array contains only the defaults fields, the custom ones will be listed in the 'field' key
        $leadData = array();
        foreach ($lead['fields'] as $group) {
            foreach ($group as $key => $value) {
                $leadData[$key] = $value['value'];
            }
        }

        //Adding stage name field separated because stage is merged to lead trough stage_id
        if(!empty($lead['stage']))
            $leadData['stage'] = $lead['stage']->getName();
        else
            $leadData['stage'] = '';

        $leadData = $integrationObject->formatData($leadData);

        // There is a solution for sending only the changed data.
        // $changes = $event->getChanges();

        // if(isset($changes['fields']) && !empty($changes['fields'])){
        //     $leadData = $integrationObject->formatData($changes['fields']);
        //     if(!isset($leadData['email'])){
        //         $leadData['email'] = $lead->getEmail();
        //     }
        // } else {
        //     // There were no changes. Abort mission.
        //     return;
        // }

        // Email is primary key, so if its not set don't send anything to RabbitMQ. (Helps with some unexpected event triggering)
        if(!empty($leadData['email'])){
            $data = json_encode([
                "source" => "mautic",
                "entity" => "contact",
                "operation" => $event->isNew() ? 'new' : 'update',
                "data" => $leadData
            ]);

            $this->publish($data);
        }
    }

    /**
     * @param Events\LeadEvent $event
     */
    public function onLeadPostDelete(Events\LeadEvent $event)
    {
        $lead = $event->getLead();
        // Email is primary key, so if its not set don't send anything to RabbitMQ. (Helps with some unexpected event triggering)
        if(!empty($lead->getEmail())){
            $data = json_encode([
                "source" => "mautic",
                "entity" => "contact",
                "operation" => "delete",
                "data" => [
                    'email' => $lead->getEmail()
                ]
            ]);

            $this->publish($data);
        }
    }

    /**
     * @param array $data The data/message to be sent.
     */
    private function publish($data){
        $integrationObject = $this->integrationHelper->getIntegrationObject('RabbitMQ');
        $settings = $integrationObject->getIntegrationSettings();

        if (false === $integrationObject || !$settings->getIsPublished()) {
            return;
        }

        $connection = new AMQPSSLConnection(
            $integrationObject->getLocation(),
            5672,
            $integrationObject->getUser(),
            $integrationObject->getPassword(),
            '/',
            [
                'cafile'=>getenv("RABBITMQ_SSL_CACERT_FILE"),
                'local_cert'=>getenv("RABBITMQ_SSL_CERT_FILE"),
                'local_pk'=>getenv("RABBITMQ_SSL_KEY_FILE"),
                'verify_peer_name'=>false,
            ]);
        $channel = $connection->channel();

        // exchange, type, passive, durable, auto_delete
        $channel->exchange_declare('kiazaki', 'topic', false, true, false);

        $msg = new AMQPMessage($data);

        $channel->basic_publish($msg, 'kiazaki', 'mautic.contact');

        $channel->close();
        $connection->close();
    }
}

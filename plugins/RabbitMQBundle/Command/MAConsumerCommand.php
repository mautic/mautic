<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      @dragan-mf
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\RabbitMQBundle\Command;

use Mautic\CoreBundle\Command\ModeratedCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * CLI Command : RabbitMQ consumer.
 *
 * php app/console rabbitmq:consumer:mautic
 */
class MAConsumerCommand extends ModeratedCommand
{
    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure()
    {
        $this->setName('rabbitmq:consumer:mautic')
            ->setDescription('RabbitMQ Mautic consumer.');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $integrationHelper = $container->get('mautic.helper.integration');
        $integrationObject = $integrationHelper->getIntegrationObject('RabbitMQ');
        $settings = $integrationObject->getIntegrationSettings();
        $field_map =  $integrationObject->getFieldMap();

        // Do not proceed if the integration is not enabled.
        if (false === $integrationObject || !$settings->getIsPublished()) {
            $output->writeln("<error>Integration not enabled, check the plugin settings.</error>");
            return;
        }

        if (empty($integrationObject->getLocation())) {
            $output->writeln("<error>RabbitMQ server location not set, check the plugin settings.</error>");
            return;
        }

        if (empty($integrationObject->getUser())) {
            $output->writeln("<error>RabbitMQ user not set, check the plugin settings.</error>");
            return;
        }

        if (empty($integrationObject->getPassword())) {
            $output->writeln("<error>RabbitMQ password not set, check the plugin settings.</error>");
            return;
        }

        $connection = new AMQPStreamConnection($integrationObject->getLocation(), 5672, $integrationObject->getUser(), $integrationObject->getPassword());
        $channel = $connection->channel();

        // exchange, type, passive, durable, auto_delete
        $channel->exchange_declare('kiazaki', 'direct', false, true, false);

        // queue, passive, durable, exclusive, auto_delete
    	$channel->queue_declare('mautic', false, true, false, false);

        // Declare the route_keys to listen to
        $routing_keys = ['mailengine', 'salesforce'];

        foreach($routing_keys as $routing_key) {
            $channel->queue_bind('mautic', 'kiazaki', $routing_key);
        }

        $leadModel = $container->get('mautic.lead.model.lead');
        $fieldModel = $container->get('mautic.lead.model.field');

        $output->writeln('<info>[*] Waiting for messages. To exit press CTRL+C</info>');

        $callback = function($msg) use ($output, $integrationObject, $leadModel, $fieldModel) {
            $output->writeln("<info>[x] " . date("Y-m-d H:i:s") . " Received message from '" . $msg->delivery_info['routing_key'] . "': " . $msg->body . "</info>");

            $lead = new Lead();
            $lead->setNewlyCreated(true);

            // Decode the message.
            $leadFields = json_decode($msg->body, true);

            // Check if the data is set.
            if(isset($leadFields['data'])){
                $data = $leadFields['data'];
            } else {
                $output->writeln("<error>[!] Message is missing the 'data' part!</error>");
                $msg->delivery_info['channel']->basic_nack($msg->delivery_info['delivery_tag']);
                return;
            }

            // Check if the data is set.
            if(isset($leadFields['operation'])){
                $operation = $leadFields['operation'];
            } else {
                $output->writeln("<error>[!] Message is missing the 'operation' part!</error>");
                $msg->delivery_info['channel']->basic_nack($msg->delivery_info['delivery_tag']);
                return;
            }

            // Get the MA unique fields.
            $uniqueLeadFields = array_keys($fieldModel->getUniqueIdentiferFields());

            // Convert the data from the standardized format.
            $data = $integrationObject->formatData($data, false);

            // Check if the data contains the unique fields.
            $checkContactWithData = array();
            foreach ($uniqueLeadFields as $field) {
                if(isset($data[$field])) {
                    $checkContactWithData[$field] = $data[$field];
                } else {    
                    $output->writeln("<error>[!] '$field' field is not defined but is marked as unique!</error>");
                    $msg->delivery_info['channel']->basic_nack($msg->delivery_info['delivery_tag']);
                    return;
                }
            }

            // Check if a lead exists by the unique fields.
            $existingLead = $leadModel->getRepository()->getLeadsByUniqueFields($checkContactWithData);

            if ($operation == 'delete') {
                if(!empty($existingLead)){
                    $leadModel->deleteEntity(reset($existingLead), false);
                }
            } else {
                if(!empty($existingLead)){
                    $lead = $leadModel->mergeLeads($lead, reset($existingLead), true, false);
                }
                // Save the lead.
                $leadModel->setFieldValues($lead, $data);
                $leadModel->saveEntity($lead, true, false);
            }
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        };

        $channel->basic_consume('mautic', '', false, false, false, false, $callback);

        while(count($channel->callbacks)) {
            $channel->wait();
        }
    }
}

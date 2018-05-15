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
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListModel;
use Mautic\StageBundle\Entity\Stage;
use Mautic\StageBundle\Model\StageModel;
use PhpAmqpLib\Connection\AMQPSSLConnection;
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
            ],
            [
                "heartbeat"=>1
            ]);
        
        $channel = $connection->channel();

        // exchange, type, passive, durable, auto_delete
        $channel->exchange_declare('kiazaki', 'topic', false, true, false);

        // queue, passive, durable, exclusive, auto_delete
        $channel->queue_declare('mautic.contact', false, true, false, false);

        // Declare the route_keys to listen to
        $routing_keys = ['mailengine.contact', 'salesforce.contact','kiazaki_ws.*'];

        foreach($routing_keys as $routing_key) {
            $channel->queue_bind('mautic.contact', 'kiazaki', $routing_key);
        }

        $leadModel = $container->get('mautic.lead.model.lead');
        $fieldModel = $container->get('mautic.lead.model.field');

        $stageModel = $container->get('mautic.stage.model.stage');
        $listModel = $container->get('mautic.lead.model.list');

        $output->writeln('<info>[*] Waiting for messages. To exit press CTRL+C</info>');

        $callback = function($msg) use ($output, $integrationObject, $leadModel, $fieldModel, $stageModel, $listModel) {
            $output->writeln("<info>[x] " . date("Y-m-d H:i:s") . " Received message from '" . $msg->delivery_info['routing_key'] . "': " . $msg->body . "</info>");

            // Decode the message.
            $leadFields = json_decode($msg->body, true);

            // Checking entity to see what to update
            if($leadFields['entity']=='geofence'){
                // Geofence as segment
                $list;
                $gAlias = $leadFields['data']['id'];
                $gName = $leadFields['data']['name'];

                if($leadFields['operation']=='new' || $leadFields['operation']=='update'){
                    $list = $listModel->getRepository()->findOneBy(['alias'=>$gAlias]);
                    if($list===null)
                        $list = new leadList();
                    $list->setName($gName);
                    $list->setDescription($gName);
                    $list->setAlias($gAlias);
                    $listModel->saveEntity($list);
                }else if($leadFields['operation']=='delete'){
                    $list = $listModel->getRepository()->findOneBy(['alias'=>$gAlias]);
                    if($list!==null)
                        $listModel->deleteEntity($list);
                }else{

                }
            }else{
                /* If entity is not geofence than update contact. */
                $lead = new Lead();
                $lead->setNewlyCreated(true);

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

                    // Adding stage to lead
                    if(isset($data['stage'])){
                        $stage = $stageModel->getRepository()->find($data['stage']);
                        $lead->setStage($stage);
                    }

                    $leadModel->saveEntity($lead, true, false);

                    // Adding lead to segments (fences)
                    $fenceIds = [];
                    $fenceNames = [];
                    if(isset($leadFields['data']['in_fence']) && count($leadFields['data']['in_fence'])>0 ){
                        foreach ($leadFields['data']['in_fence'] as $key => $value) {
                            $fenceNames[] = $value;
                        }
                        //Getting lead segments
                        $leadSegments = $leadModel->getLists($lead);
                        //Removing all lead semgnts
                        foreach ($leadSegments as $key => $value) {
                            $fenceIds[] = $value->getId();
                        }
                        $leadModel->removeFromLists($lead, $fenceIds);
                        //Reseting array
                        $fenceIds = [];
                        //Selecting new segments
                        $fences = $listModel->getRepository()->findBy(['name'=>$fenceNames]);
                        //Getting segments ids for adding leads to them
                        foreach ($fences as $key => $value) {
                            $fenceIds[] = $value->getId();
                        }
                        //Adding new segments to lead
                        $leadModel->addToLists($lead, $fenceIds);
                    }
                }

            }
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        };

        $channel->basic_consume('mautic.contact', '', false, false, false, false, $callback);

        while(count($channel->callbacks)) {
            $channel->wait();
        }
    }
}

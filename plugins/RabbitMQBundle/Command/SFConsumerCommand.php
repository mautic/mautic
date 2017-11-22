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
 * php app/console rabbitmq:consumer:salesforce
 */
class SFConsumerCommand extends ModeratedCommand
{
	private $token = null;
	private $client_id;
	private $client_secret;
	private $username;
	private $password;

    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure()
    {
        $this->setName('rabbitmq:consumer:salesforce')
            ->setDescription('RabbitMQ Salesforce consumer.');

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
        $this->client_id = $integrationObject->getSFClientId();
        $this->client_secret = $integrationObject->getSFClientSecret();
        $this->username = $integrationObject->getSFUsername();
        $this->password = $integrationObject->getSFPassword();

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

        if (empty($this->client_id)) {
            $output->writeln("<error>SalesForce client id not set, check the plugin settings.</error>");
            return;
        }

        if (empty($this->client_secret)) {
            $output->writeln("<error>SalesForce client secret not set, check the plugin settings.</error>");
            return;
        }

        if (empty($this->username)) {
            $output->writeln("<error>SalesForce ussername not set, check the plugin settings.</error>");
            return;
        }

        if (empty($this->password)) {
            $output->writeln("<error>SalesForce password not set, check the plugin settings.</error>");
            return;
        }

        $this->_checkToken();
        if(isset($this->token['access_token']) && !empty($this->token['access_token'])){
            $output->writeln("<info>Got SalesForce token.</info>");
        }

	    $connection = new AMQPStreamConnection($integrationObject->getLocation(), 5672, $integrationObject->getUser(), $integrationObject->getPassword());
	    $channel = $connection->channel();

	    // exchange, type, passive, durable, auto_delete
	    $channel->exchange_declare('kiazaki', 'direct', false, true, false);

	    // queue, passive, durable, exclusive, auto_delete
		$channel->queue_declare('salesforce', false, true, false, false);

	    // Declare the route_keys to listen to
	    $routing_keys = ['mailengine', 'mautic'];

	    foreach($routing_keys as $routing_key) {
	        $channel->queue_bind('salesforce', 'kiazaki', $routing_key);
	    }

	    $output->writeln('<info>[*] Waiting for messages. To exit press CTRL+C</info>');

        $callback = function($msg) use ($output, $integrationObject) {
            $output->writeln("<info>[x] " . date("Y-m-d H:i:s") . " Received message from '" . $msg->delivery_info['routing_key'] . "': " . $msg->body . "</info>");

            // Decode the message.
            $leadFields = json_decode(unserialize($msg->body), true);

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

            // Convert the data from the standardized format.
            $data = $integrationObject->formatData($data, false, false);

            // Check if the data contains the unique fields.
            $uniqueLeadFields = ['Email'];
            $checkContactWithData = [];
            foreach ($uniqueLeadFields as $field) {
                if(isset($data[$field])) {
                    $checkContactWithData[$field] = $data[$field];
                } else {    
                    $output->writeln("<error>[!] '$field' field is not defined but is marked as unique!</error>");
                    $msg->delivery_info['channel']->basic_nack($msg->delivery_info['delivery_tag']);
                    return;
                }
            }
            $data["Company"] = "TODO"; // TEMP FIX

            $existingLead = $this->_getLead($data['Email']);
            $existingLead = json_decode($existingLead, true);
            $id = reset($existingLead['records'])['Id'];

            if ($operation == 'delete') {
                if(!empty($id)){
                    $output->writeln("<info>Deleting lead.</info>");
                    $response = $this->_deleteLead($id);
                    $response = json_decode($response, true);
                    if(is_array($response)){
                        $response = reset($response);
                    }

                    if(isset($response['errorCode']) && !empty($response['errorCode'])) {
                        $msg->delivery_info['channel']->basic_nack($msg->delivery_info['delivery_tag']);
                        $output->writeln("<error>[!] " . $response['errorCode'] . ": " . $response['message'] ."</error>");
                    } else {
                        $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
                    }
                }
            } else {
                if(!empty($id)){
                    $output->writeln("<info>Updating lead.</info>");
                    $response = $this->_updateLead($id, $data);
                    $response = json_decode($response, true);
                    if(is_array($response)){
                        $response = reset($response);
                    }

                    if(isset($response['errorCode']) && !empty($response['errorCode'])) {
                        $msg->delivery_info['channel']->basic_nack($msg->delivery_info['delivery_tag']);
                        $output->writeln("<error>[!] " . $response['errorCode'] . ": " . $response['message'] ."</error>");
                    } else {
                        $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
                    }
                } else {
                    $output->writeln("<info>Pushing lead.</info>");
                    $response = $this->_pushLead($data);
                    $response = json_decode($response, true);
                    if(is_array($response)){
                        $response = reset($response);
                    }

                    if(isset($response['errorCode']) && !empty($response['errorCode'])) {
                        $msg->delivery_info['channel']->basic_nack($msg->delivery_info['delivery_tag']);
                        $output->writeln("<error>[!] " . $response['errorCode'] . ": " . $response['message'] ."</error>");
                    } else {
                        $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
                    }
                }
            }
	    };

	    $channel->basic_consume('salesforce', '', false, false, false, false, $callback);

	    while(count($channel->callbacks)) {
	        $channel->wait();
	    }
    }

    private function _getLead ($email) {
        $this->_checkToken();

        return $this->_request($this->token['instance_url']."/services/data/v20.0/query/?q=SELECT+ID+from+Lead+where+email+=+'$email'+limit+1", [], true, "", "GET");
    }

    private function _pushLead ($data) {
    	$this->_checkToken();

    	return $this->_request($this->token['instance_url']."/services/data/v20.0/sobjects/Lead", $data, true, "JSON", "POST");
    }

    private function _updateLead ($id, $data) {
        $this->_checkToken();

        return $this->_request($this->token['instance_url']."/services/data/v20.0/sobjects/Lead/$id", $data, true, "JSON", "PATCH");
    }

    private function _deleteLead ($id) {
        $this->_checkToken();

        return $this->_request($this->token['instance_url']."/services/data/v20.0/sobjects/Lead/$id", [], true, "", "DELETE");
    }

    private function _checkToken () {

        // No token, get one.
        if(is_null($this->token)){
            echo "Getting new token.\n";

            $result = $this->_request("https://login.salesforce.com/services/oauth2/token", [
                "grant_type" => "password",
                "client_id" => $this->client_id,
                "client_secret" => $this->client_secret,
                "username" => $this->username,
                "password" => $this->password
            ]);

            $this->token = json_decode($result, true);
        } 
        // Token close to expiring, refresh it. Refresh it 5 mins before expiring.
        elseif(intval($this->token['issued_at']) + 7200 - 300 < time()) {
            echo "Refreshing token.\n";
            $result = $this->_request("https://login.salesforce.com/services/oauth2/token", [
                "grant_type" => "refresh_token",
                "refresh_toke" => $this->token['access_token'],
                "client_id" => $this->client_id,
                "client_secret" => $this->client_secret,
            ]);

            $this->token = json_decode($result, true);
        }

        if(isset($this->token['error'])){
            echo "Token error: " . $this->token['error'] . " (" . $this->token['error_description'] . ")\n";
            exit;
        }
    }

    private function _request ($url, $data, $authorize = false, $type = "", $request_type = "POST") {
    	$ch = curl_init($url);

    	$header = array();
    	if($authorize){
    		$header[] = "Authorization: Bearer ".$this->token['access_token'];
    	}

    	if($type == "JSON"){
    		$header[] = "Content-Type: application/json";
    		$data = json_encode($data);
    	}

    	if(!empty($header)){
    		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    	}

    	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request_type);
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    	$result = curl_exec($ch);

    	if(curl_errno($ch)){
    	    return "{'error': '".curl_error($ch)."'}";
    	}

    	curl_close($ch);

    	return $result;
    }
}

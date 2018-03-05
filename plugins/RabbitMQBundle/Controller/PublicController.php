<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\RabbitMQBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class PublicController extends CommonController
{
    private $token = 'TXUhcWFbWGzjGWLAmgposiJmLwQRqJcvVtSOqNcpyCEawUSQJcKh1kccGFqZXGJF';

    /**
     * This action will receive a POST when the session status changes.
     * A POST will also be made when a customer joins the session and when the session ends
     * (whether or not a customer joined).
     *
     * @param Request $request
     *
     * @return array|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     */
    public function insertAction(Request $request)
    {
        if($request->headers->get('Authorization') != "Basic ".$this->token){
            return new Response('', 401);
        }

        $data = json_decode($request->getContent(), true);

        return $this->_process($data, "new");
    }

    /**
     * This action will receive a POST when the session status changes.
     * A POST will also be made when a customer joins the session and when the session ends
     * (whether or not a customer joined).
     *
     * @param Request $request
     *
     * @return array|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     */
    public function updateAction(Request $request)
    {
        if($request->headers->get('Authorization') != "Basic ".$this->token){
            return new Response('', 401);
        }

        $data = json_decode($request->getContent(), true);

        return $this->_process($data, "update");
    }

    /**
     * This action will receive a POST when the session status changes.
     * A POST will also be made when a customer joins the session and when the session ends
     * (whether or not a customer joined).
     *
     * @param Request $request
     *
     * @return array|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     */
    public function deleteAction(Request $request)
    {
        if($request->headers->get('Authorization') != "Basic ".$this->token){
            return new Response('', 401);
        }

        $data = json_decode($request->getContent(), true);

        return $this->_process($data, "delete");
    }

    /**
     * Process the message.
     * @param  array $data Data from SF.
     * @param  string insert|update|delete $action Which action was triggered.
     * @return [type]         [description]
     */
    private function _process($data, $action){
        if(empty($data)){
            return new Response('', 400);
        }

        $integrationHelper = $this->get('mautic.helper.integration');
        $integrationObject = $integrationHelper->getIntegrationObject('RabbitMQ');

        if($action == "delete"){
            if(isset($data['old'])){
                $data = reset($data['old']);
                $data = $integrationObject->formatData($data, true, false);

                if(isset($data['email'])){
                    $lead = json_encode([
                        "source" => "salesforce",
                        "entity" => "contact",
                        "operation" => "delete",
                        "data" => [
                            'email' => $data['email']
                        ]
                    ]);

                    $this->_publish($lead);

                    return new Response('', 200);
                } else {
                    return new Response('', 400);
                }
            } else {
                return new Response('', 400);
            }
        }

        if($action == "new" || $action == "update"){
            if(isset($data['new'])){
                $data = reset($data['new']);
                $data = $integrationObject->formatData($data, true, false);

                if(isset($data['email'])){
                    $lead = json_encode([
                        "source" => "salesforce",
                        "entity" => "contact",
                        "operation" => $action,
                        "data" => $data
                    ]);

                    $this->_publish($lead);

                    return new Response('', 200);
                } else {
                    return new Response('', 400);
                }
            } else {
                return new Response('', 400);
            }
        }
    }

    private function _publish($data){
        $integrationHelper = $this->get('mautic.helper.integration');
        $integrationObject = $integrationHelper->getIntegrationObject('RabbitMQ');
        $settings = $integrationObject->getIntegrationSettings();

        if (false === $integrationObject || !$settings->getIsPublished()) {
            return;
        }

        $connection = new AMQPStreamConnection($integrationObject->getLocation(), 5672, $integrationObject->getUser(), $integrationObject->getPassword());
        $channel = $connection->channel();

        // exchange, type, passive, durable, auto_delete
        $channel->exchange_declare('kiazaki', 'topic', false, true, false);

        $msg = new AMQPMessage($data);

        $channel->basic_publish($msg, 'kiazaki', 'salesforce.contact');

        $channel->close();
        $connection->close();
    }
}

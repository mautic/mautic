<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\Helper;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\SmsBundle\Callback\CallbackInterface;
use Mautic\SmsBundle\Callback\DAO\DeliveryStatusDAO;
use Mautic\SmsBundle\Callback\DAO\ReplyDAO;
use Mautic\SmsBundle\Callback\ResponseInterface;
use Mautic\SmsBundle\Event\DeliveryEvent;
use Mautic\SmsBundle\Event\ReplyEvent;
use Mautic\SmsBundle\Exception\NumberNotFoundException;
use Mautic\SmsBundle\SmsEvents;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class ReplyHelper.
 */
class ReplyHelper
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ContactTracker
     */
    private $contactTracker;

    /**
     * ReplyHelper constructor.
     *
     * @param EventDispatcherInterface $eventDispatcher
     * @param LoggerInterface          $logger
     * @param ContactTracker           $contactTracker
     */
    public function __construct(EventDispatcherInterface $eventDispatcher, LoggerInterface $logger, ContactTracker $contactTracker)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->logger          = $logger;
        $this->contactTracker  = $contactTracker;
    }

    /**
     * @param string $pattern
     * @param string $replyBody
     *
     * @return bool
     */
    public static function matches($pattern, $replyBody)
    {
        return fnmatch($pattern, $replyBody, FNM_CASEFOLD);
    }

    /**
     * @param CallbackInterface $handler
     * @param Request           $request
     *
     * @return Response
     *
     * @throws \Exception
     */
    public function handleRequest(CallbackInterface $handler, Request $request)
    {
        // Set the default response
        $response = $this->getDefaultResponse($handler);

        try {
            $messages = $this->getMessages($handler->getMessage($request), $handler);

            $eventResponse = null;
            foreach ($messages as $message) {
                if ($message instanceof DeliveryStatusDAO) {
                    $eventResponse = $this->handleRequestDelivery($message);
                } elseif ($message instanceof ReplyDAO) {
                    $eventResponse =  $this->handleRequestReply($message);
                }
            }

            if ($eventResponse instanceof Response) {
                $response = $eventResponse;
            }
        } catch (BadRequestHttpException $exception) {
            return new Response('invalid request', 400);
        } catch (NotFoundHttpException $exception) {
            return new Response('', 404);
        } catch (NumberNotFoundException $exception) {
            $this->logger->debug(
                sprintf(
                    '%s: %s was not found. The message sent was "%s"',
                    $handler->getTransportName(),
                    $exception->getNumber(),
                    isset($message) ? $message : 'unknown'
                )
            );
        }

        return $response;
    }

    /**
     * @param string|ReplyDAO|array $message
     * @param CallbackInterface     $handler
     *
     * @return array|ReplyDAO
     *
     * @throws NumberNotFoundException
     */
    private function getMessages($message, CallbackInterface $handler)
    {
        if (is_string($message)) {
            $object = new ReplyDAO();
            $object->setMessage($message);
            $object->setContacts($handler->getContacts());
            $message = $object;
        }

        if (!is_array($message)) {
            $message = [$message];
        }

        return $message;
    }

    /**
     * @param DeliveryStatusDAO $deliveryStatusDAO
     *
     * @return Response|null
     */
    private function handleRequestDelivery(DeliveryStatusDAO  $deliveryStatusDAO)
    {
        $contacts = $deliveryStatusDAO->getContacts();

        $this->logger->debug(sprintf('SMS DELIVERY: Processing delivery callback'));
        $this->logger->debug(sprintf('SMS DELIVERY: Found IDs %s', implode(',', $contacts->getKeys())));
        $eventResponse  = null;
        foreach ($contacts as $contact) {
            $this->contactTracker->setSystemContact($contact);
            $eventResponse     = $this->dispatchDeliveryEvent($contact, $deliveryStatusDAO);
        }

        return $eventResponse;
    }

    /**
     * @param ReplyDAO $replyDAO
     *
     * @return Response|null
     */
    private function handleRequestReply(ReplyDAO $replyDAO)
    {
        $message  = $replyDAO->getMessage();
        $contacts = $replyDAO->getContacts();

        $this->logger->debug(sprintf('SMS REPLY: Processing message "%s"', $message));
        $this->logger->debug(sprintf('SMS REPLY: Found IDs %s', implode(',', $contacts->getKeys())));
        $eventResponse  = null;
        foreach ($contacts as $contact) {
            $this->contactTracker->setSystemContact($contact);
            $eventResponse = $this->dispatchReplyEvent($contact, $message);
        }

        return $eventResponse;
    }

    /**
     * @param Lead   $contact
     * @param string $message
     *
     * @return null|\Symfony\Component\HttpFoundation\Response
     */
    public function dispatchReplyEvent(Lead $contact, $message)
    {
        $replyEvent = new ReplyEvent($contact, trim($message));

        $this->eventDispatcher->dispatch(SmsEvents::ON_REPLY, $replyEvent);

        return $replyEvent->getResponse();
    }

    /**
     * @param Lead              $contact
     * @param DeliveryStatusDAO $deliveryStatus
     *
     * @return Response|null
     */
    public function dispatchDeliveryEvent(Lead $contact, DeliveryStatusDAO $deliveryStatus)
    {
        $deliveryEvent = new DeliveryEvent($contact, $deliveryStatus);

        $this->eventDispatcher->dispatch(SmsEvents::ON_DELIVERY, $deliveryEvent);

        return $deliveryEvent->getResponse();
    }

    /**
     * @param CallbackInterface $handler
     *
     * @return Response
     *
     * @throws \Exception
     */
    private function getDefaultResponse(CallbackInterface $handler)
    {
        if ($handler instanceof ResponseInterface) {
            $response = $handler->getResponse();

            if (!$response instanceof Response) {
                throw new \Exception('getResponse must return a Symfony\Component\HttpFoundation\Response object');
            }

            return $response;
        }

        return new Response();
    }
}

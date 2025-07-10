<?php

namespace Mautic\SmsBundle\Helper;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\SmsBundle\Callback\CallbackInterface;
use Mautic\SmsBundle\Event\ReplyEvent;
use Mautic\SmsBundle\Exception\NumberNotFoundException;
use Mautic\SmsBundle\SmsEvents;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ReplyHelper
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger,
        private ContactTracker $contactTracker,
    ) {
    }

    /**
     * @param string $pattern
     * @param string $replyBody
     */
    public static function matches($pattern, $replyBody): bool
    {
        return fnmatch($pattern, $replyBody, FNM_CASEFOLD);
    }

    /**
     * @return Response
     *
     * @throws \Exception
     */
    public function handleRequest(CallbackInterface $handler, Request $request)
    {
        // Set the default response
        $response = new Response();

        try {
            $message  = $handler->getMessage($request);
            $contacts = $handler->getContacts($request);

            $this->logger->debug(sprintf('SMS REPLY: Processing message "%s"', $message));
            $this->logger->debug(sprintf('SMS REPLY: Found IDs %s', implode(',', $contacts->getKeys())));

            foreach ($contacts as $contact) {
                // Set the contact for campaign decisions
                $this->contactTracker->setSystemContact($contact);

                $eventResponse = $this->dispatchReplyEvent($contact, $message);

                if ($eventResponse instanceof Response) {
                    // Last one wins
                    $response = $eventResponse;
                }
            }
        } catch (BadRequestHttpException) {
            return new Response('invalid request', 400);
        } catch (NotFoundHttpException) {
            return new Response('', 404);
        } catch (NumberNotFoundException $exception) {
            $this->logger->debug(
                sprintf(
                    '%s: %s was not found. The message sent was "%s"',
                    $handler->getTransportName(),
                    $exception->getNumber(),
                    !empty($message) ? $message : 'unknown'
                )
            );
        }

        return $response;
    }

    private function dispatchReplyEvent(Lead $contact, string $message): ?Response
    {
        $replyEvent = new ReplyEvent($contact, trim($message));

        $this->eventDispatcher->dispatch($replyEvent, SmsEvents::ON_REPLY);

        return $replyEvent->getResponse();
    }
}

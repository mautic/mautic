<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\EventSubscriber;

use Mautic\ApiBundle\Helper\RequestHelper;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event as Events;
use MauticPlugin\GrapesJsBuilderBundle\Integration\Config;
use MauticPlugin\GrapesJsBuilderBundle\Model\GrapesJsBuilderModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class EmailSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Config $config,
        private GrapesJsBuilderModel $grapesJsBuilderModel
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EmailEvents::EMAIL_POST_SAVE   => ['onEmailPostSave', 0],
            EmailEvents::EMAIL_POST_DELETE => ['onEmailDelete', 0],
            KernelEvents::RESPONSE         => ['onKernelResponse', 0],
        ];
    }

    /**
     * Add an entry.
     */
    public function onEmailPostSave(Events\EmailEvent $event): void
    {
        if (!$this->config->isPublished()) {
            return;
        }

        $this->grapesJsBuilderModel->addOrEditEntity($event->getEmail());
    }

    /**
     * Delete an entry.
     */
    public function onEmailDelete(Events\EmailEvent $event): void
    {
        if (!$this->config->isPublished()) {
            return;
        }

        $email           = $event->getEmail();
        $grapesJsBuilder = $this->grapesJsBuilderModel->getRepository()->findOneBy(['email' => $email]);

        if ($grapesJsBuilder) {
            $this->grapesJsBuilderModel->getRepository()->deleteEntity($grapesJsBuilder);
        }
    }

    /**
     * Add customMjml property to Serialization.
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        // Early return if not an API request
        if (!RequestHelper::isApiRequest($request)) {
            return;
        }

        $response = $event->getResponse();
        $content  = $response->getContent();

        // Check for status code is 200 or error in response.
        if (200 !== $response->getStatusCode() || str_contains($content, 'error')) {
            return;
        }

        $data = json_decode($content, true);
        // Early return if no email data is present
        if (!isset($data['email'])) {
            return;
        }

        $email = reset($data);
        // Early return if error is found in email data
        if (isset($email['error'])) {
            return;
        }

        // Get and update the responses for the customMjml from the GrapesJS Builder.
        $grapesJsBuilder = $this->grapesJsBuilderModel->getRepository()->findOneBy(['email' => $email['id']]);
        if ($grapesJsBuilder && $customMjml = $grapesJsBuilder->getCustomMjml()) {
            $email['grapesjsbuilder']['customMjml'] = $customMjml;
            $response->setContent(json_encode(['email' => $email]));
            $event->setResponse($response);
        }
    }
}

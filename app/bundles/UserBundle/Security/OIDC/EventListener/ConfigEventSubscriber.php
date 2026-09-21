<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Security\OIDC\EventListener;

use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigEvent;
use Mautic\UserBundle\Security\OIDC\ClientCredentials;
use Mautic\UserBundle\Security\OIDC\Factory\ClientFactoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ConfigEventSubscriber implements EventSubscriberInterface
{
    private TranslatorInterface $translator;
    private LoggerInterface $logger;
    private ClientFactoryInterface $clientFactory;

    public function __construct(ClientFactoryInterface $clientFactory, TranslatorInterface $translator, LoggerInterface $logger)
    {
        $this->clientFactory     = $clientFactory;
        $this->translator        = $translator;
        $this->logger            = $logger;
    }

    public static function getSubscribedEvents()
    {
        return [
            ConfigEvents::CONFIG_PRE_SAVE => ['onConfigPreSave', 0],
        ];
    }

    public function onConfigPreSave(ConfigEvent $event): void
    {
        if (1 !== $event->getConfig()['userconfig']['open_id_is_enabled']) {
            return;
        }

        $credentials = new ClientCredentials(
            $event->getConfig()['userconfig']['open_id_client_url'],
            $event->getConfig()['userconfig']['open_id_client_id'],
            $event->getConfig()['userconfig']['open_id_client_secret'],
            $event->getConfig()['userconfig']['open_id_mapping_field']
        );
        $client      = $this->clientFactory->create($credentials);

        if ($error = $client->testConnection()) {
            $message = $this->translator->trans('mautic.open_id.config.exception.test_connection_failed');
            $this->logger->debug($message, ['error' => $error]);
            $event->setError($message, [], 'userconfig', 'open_id_is_enabled');
        }
    }
}

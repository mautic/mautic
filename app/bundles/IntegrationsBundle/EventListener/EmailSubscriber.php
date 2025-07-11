<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\EventListener;

use Doctrine\ORM\EntityNotFoundException;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailBuilderEvent;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\IntegrationsBundle\DTO\IntegrationObjectToken as Token;
use Mautic\IntegrationsBundle\Entity\ObjectMappingRepository;
use Mautic\IntegrationsBundle\Event\MappedIntegrationObjectTokenEvent;
use Mautic\IntegrationsBundle\Helper\TokenParser;
use Mautic\IntegrationsBundle\IntegrationEvents;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * This class subscribes to events related to building and providing
 * tokens for emails, particularly the IntegrationObjectToken.
 */
class EmailSubscriber implements EventSubscriberInterface
{
    public function __construct(
        protected TranslatorInterface $translator,
        protected EventDispatcherInterface $eventDispatcher,
        protected TokenParser $tokenParser,
        protected ObjectMappingRepository $objectMappingRepository,
        protected IntegrationHelper $integrationHelper,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EmailEvents::EMAIL_ON_BUILD   => ['onEmailBuild', 0],
            EmailEvents::EMAIL_ON_SEND    => ['decodeTokens', 0],
            EmailEvents::EMAIL_ON_DISPLAY => ['decodeTokens', 0],
        ];
    }

    public function onEmailBuild(EmailBuilderEvent $event): void
    {
        $tokens = [];

        $mappedObjectTokens = new MappedIntegrationObjectTokenEvent();
        $this->eventDispatcher->dispatch(
            $mappedObjectTokens,
            IntegrationEvents::INTEGRATION_OBJECT_TOKEN_EVENT
        );

        foreach ($mappedObjectTokens->getTokens() as $integration => $t) {
            foreach ($t as $integrationObject => $objectData) {
                $token = $this->tokenParser->buildTokenWithDefaultOptions(
                    $integrationObject,
                    $integration,
                    $objectData['default'],
                    $objectData['link_text'],
                    $objectData['base_url']
                );

                $tokens[$token] = $objectData['token_title'];
            }
        }

        if ($event->tokensRequested(array_keys($tokens))) {
            $event->addTokens(
                $event->filterTokens($tokens)
            );
        }
    }

    public function decodeTokens(EmailSendEvent $event): void
    {
        $tokens = $this->tokenParser->findTokens($event->getContent());

        if (0 === $tokens->count()) {
            return;
        }

        $tokens->map(function (Token $token) use ($event): void {
            try {
                $integrationObject = $this->objectMappingRepository->getIntegrationObject(
                    $token->getIntegration(),
                    'lead',
                    $event->getLead()['id'],
                    $token->getObjectName()
                );

                $url  = $token->getBaseURL().'/'.$integrationObject['integration_object_id'];
                $link = "<a href=\"{$url}\" >".$token->getLinkText().'</a>';
                $event->addToken($token->getToken(), $link);
            } catch (EntityNotFoundException) {
                return;
            }
        });
    }
}

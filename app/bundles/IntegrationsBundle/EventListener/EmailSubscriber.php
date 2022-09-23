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
use Symfony\Component\Translation\TranslatorInterface;

/**
 * This class subscribes to events related to building and providing
 * tokens for emails, particularly the IntegrationObjectToken.
 *
 * Class EmailSubscriber
 */
class EmailSubscriber implements EventSubscriberInterface
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var TokenParser
     */
    protected $tokenParser;

    /**
     * @var ObjectMappingRepository
     */
    protected $objectMappingRepository;

    /**
     * @var IntegrationHelper
     */
    protected $integrationHelper;

    public function __construct(
        TranslatorInterface $translator,
        EventDispatcherInterface $eventDispatcher,
        TokenParser $tokenParser,
        ObjectMappingRepository $objectMappingRepository,
        IntegrationHelper $integrationHelper
    ) {
        $this->translator              = $translator;
        $this->eventDispatcher         = $eventDispatcher;
        $this->tokenParser             = $tokenParser;
        $this->objectMappingRepository = $objectMappingRepository;
        $this->integrationHelper       = $integrationHelper;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
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
            IntegrationEvents::INTEGRATION_OBJECT_TOKEN_EVENT,
            $mappedObjectTokens
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

                $url = $token->getBaseURL().'/'.$integrationObject['integration_object_id'];
                $link = "<a href=\"{$url}\" >".$token->getLinkText().'</a>';
                $event->addToken($token->getToken(), $link);
            } catch (EntityNotFoundException $e) {
                return;
            }
        });
    }
}

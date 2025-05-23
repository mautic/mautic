<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\EventListener;

use Doctrine\DBAL\Connection;
use Mautic\CoreBundle\Helper\BuilderTokenHelperFactory;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailBuilderEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class DwcTokensSubscriber implements EventSubscriberInterface
{
    public const DWCTOKENREGEX = '{dwc=(.*?)}';

    public function __construct(
        private BuilderTokenHelperFactory $builderTokenHelperFactory,
        private Connection $connection,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EmailEvents::EMAIL_ON_BUILD   => ['onEmailBuild', 0],
        ];
    }

    public function onEmailBuild(EmailBuilderEvent $event): void
    {
        if ($event->tokensRequested([static::DWCTOKENREGEX])) {
            $dwcTokenHelper = $this->builderTokenHelperFactory->getBuilderTokenHelper(
                'dynamicContent',
                'dynamiccontent:dynamiccontents'
            );

            $expr = $this->connection->createExpressionBuilder()
                ->and('e.is_campaign_based <> 1 and e.slot_name is not null');

            $tokens = $dwcTokenHelper->getTokens(
                static::DWCTOKENREGEX,
                '',
                'name',
                'slot_name',
                $expr
            );
            $event->addTokens(is_array($tokens) ? $tokens : []);
        }
    }
}

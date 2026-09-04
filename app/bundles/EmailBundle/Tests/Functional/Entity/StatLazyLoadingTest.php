<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Functional\Entity;

use Doctrine\DBAL\Logging\DebugStack;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Stat;
use PHPUnit\Framework\Assert;

final class StatLazyLoadingTest extends MauticMysqlTestCase
{
    public function testLoadingAStatDoesNotEagerlyJoinStatData(): void
    {
        $stat = new Stat();
        $stat->setEmailAddress('john@doe.cz');
        $stat->setDateSent(new \DateTime());
        $stat->setTokens(['{token}' => 'value']);

        $this->em->persist($stat);
        $this->em->flush();

        $statId = $stat->getId();
        Assert::assertNotNull($statId);

        // Detach everything so the next find() is a genuine fresh hydration from the DB,
        // not a return of the already-in-memory managed instance.
        $this->em->clear();

        // Middleware (DebugStack's replacement) wraps the driver at connection-construction
        // time, too late for an already-open connection, so DebugStack is used deliberately.
        // @phpstan-ignore new.deprecatedClass, method.deprecatedClass
        $logger = new DebugStack();
        // @phpstan-ignore method.deprecated
        $this->connection->getConfiguration()->setSQLLogger($logger);

        $freshStat = $this->em->getRepository(Stat::class)->find($statId);

        // @phpstan-ignore property.deprecatedClass
        foreach ($logger->queries as $query) {
            Assert::assertStringNotContainsStringIgnoringCase(
                'email_stats_data',
                $query['sql'],
                'Loading a Stat must not eagerly join or select email_stats_data.'
            );
        }

        // Only once getTokens() is actually called should a query touch email_stats_data.
        $tokens = $freshStat->getTokens();
        // @phpstan-ignore method.deprecated
        $this->connection->getConfiguration()->setSQLLogger(null);

        Assert::assertSame(['{token}' => 'value'], $tokens);
        Assert::assertTrue(
            // @phpstan-ignore property.deprecatedClass
            (bool) array_filter($logger->queries, static fn ($q) => str_contains(strtolower($q['sql']), 'email_stats_data')),
            'getTokens() should have triggered a query against email_stats_data.'
        );
    }
}

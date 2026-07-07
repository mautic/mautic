<?php

declare(strict_types=1);

namespace Mautic\WebhookBundle\Tests\Entity;

use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CoreBundle\Test\Doctrine\RepositoryConfiguratorTrait;
use Mautic\WebhookBundle\Entity\Webhook;
use Mautic\WebhookBundle\Entity\WebhookRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class WebhookRepositoryTest extends TestCase
{
    use RepositoryConfiguratorTrait;

    private function getRepository(): WebhookRepository
    {
        $repository = $this->configureRepository(Webhook::class);
        $this->connection->method('createQueryBuilder')->willReturnCallback(fn (): QueryBuilder => new QueryBuilder($this->connection));

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(static fn (string $id): string => match ($id) {
            'mautic.core.searchcommand.name' => 'name',
            'mautic.core.searchcommand.ispublished' => 'is:published',
            'mautic.core.searchcommand.isunpublished' => 'is:unpublished',
            'mautic.core.searchcommand.isuncategorized' => 'is:uncategorized',
            'mautic.core.searchcommand.ismine' => 'is:mine',
            'mautic.core.searchcommand.category' => 'category',
            default => $id,
        });
        $repository->setTranslator($translator);

        return $repository;
    }

    public function testGetSearchCommandsContainsNameAndStandardCommands(): void
    {
        $commands = $this->getRepository()->getSearchCommands();

        self::assertContains('mautic.core.searchcommand.name', $commands);
        self::assertContains('mautic.core.searchcommand.ispublished', $commands);
        self::assertContains('mautic.core.searchcommand.ismine', $commands);
        self::assertContains('mautic.core.searchcommand.ids', $commands);
    }

    public function testAddSearchCommandWhereClauseHandlesNameFilter(): void
    {
        $repository = $this->getRepository();
        $qb         = $this->connection->createQueryBuilder();
        $filter     = (object) ['command' => 'name', 'string' => 'Order webhook', 'not' => false, 'strict' => false];

        $method = new \ReflectionMethod(WebhookRepository::class, 'addSearchCommandWhereClause');

        [$expr, $params] = $method->invoke($repository, $qb, $filter);

        self::assertStringContainsString('e.name', (string) $expr);
        self::assertCount(1, $params);
    }

    public function testAddSearchCommandWhereClauseHandlesPublishedFilter(): void
    {
        $repository = $this->getRepository();
        $qb         = $this->connection->createQueryBuilder();
        $filter     = (object) ['command' => 'is:published', 'string' => '', 'not' => false, 'strict' => false];

        $method = new \ReflectionMethod(WebhookRepository::class, 'addSearchCommandWhereClause');

        [$expr, $params] = $method->invoke($repository, $qb, $filter);

        self::assertStringContainsString('e.is_published', (string) $expr);
        self::assertSame(['par0' => true], $params);
    }
}

<?php

declare(strict_types=1);

namespace Mautic\ApiBundle\Tests\Entity\oAuth2;

use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\ApiBundle\Entity\oAuth2\Client;
use Mautic\ApiBundle\Entity\oAuth2\ClientRepository;
use Mautic\CoreBundle\Test\Doctrine\RepositoryConfiguratorTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ClientRepositoryTest extends TestCase
{
    use RepositoryConfiguratorTrait;

    private function getRepository(): ClientRepository
    {
        $repository = $this->configureRepository(Client::class);
        $this->connection->method('createQueryBuilder')->willReturnCallback(fn (): QueryBuilder => new QueryBuilder($this->connection));

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(static fn (string $id): string => match ($id) {
            'mautic.core.searchcommand.name' => 'name',
            'mautic.api.client.searchcommand.callback' => 'callback',
            'mautic.api.client.searchcommand.redirecturi' => 'redirecturi',
            default => $id,
        });
        $repository->autowireCommonRepository($translator);

        return $repository;
    }

    public function testGetSearchCommandsContainsClientCommands(): void
    {
        $commands = $this->getRepository()->getSearchCommands();

        $this->assertContains('mautic.core.searchcommand.name', $commands);
        $this->assertContains('mautic.api.client.searchcommand.callback', $commands);
        $this->assertContains('mautic.api.client.searchcommand.redirecturi', $commands);
        $this->assertContains('mautic.core.searchcommand.ids', $commands);
    }

    #[DataProvider('dataSearchCommandFilters')]
    public function testAddSearchCommandWhereClauseHandlesClientFilters(string $command, string $expectedColumn): void
    {
        $repository = $this->getRepository();
        $qb         = $this->connection->createQueryBuilder();
        $filter     = (object) ['command' => $command, 'string' => 'test', 'not' => false, 'strict' => false];

        $method = new \ReflectionMethod(ClientRepository::class, 'addSearchCommandWhereClause');

        [$expr, $params] = $method->invoke($repository, $qb, $filter);

        $this->assertStringContainsString($expectedColumn, (string) $expr);
        $this->assertCount(1, $params);
    }

    /**
     * @return iterable<array{0: string, 1: string}>
     */
    public static function dataSearchCommandFilters(): iterable
    {
        yield ['name', 'c.name'];
        yield ['callback', 'c.redirectUris'];
        yield ['redirecturi', 'c.redirectUris'];
    }
}

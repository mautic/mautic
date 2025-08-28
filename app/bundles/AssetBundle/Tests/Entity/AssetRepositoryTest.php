<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Tests\Entity;

use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\AssetBundle\Entity\Asset;
use Mautic\AssetBundle\Entity\AssetRepository;
use Mautic\CoreBundle\Test\Doctrine\RepositoryConfiguratorTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class AssetRepositoryTest extends TestCase
{
    use RepositoryConfiguratorTrait;

    private function getRepository(): AssetRepository
    {
        $repository = $this->configureRepository(Asset::class);
        $this->connection->method('createQueryBuilder')->willReturnCallback(fn (): QueryBuilder => new QueryBuilder($this->connection));

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(fn (string $id): string => match ($id) {
            'mautic.asset.asset.searchcommand.isexpired'  => 'is:expired',
            'mautic.asset.asset.searchcommand.ispending'  => 'is:pending',
            'mautic.asset.asset.searchcommand.isimage'    => 'is:image',
            'mautic.asset.asset.searchcommand.isvideo'    => 'is:video',
            'mautic.asset.asset.searchcommand.isaudio'    => 'is:audio',
            'mautic.asset.asset.searchcommand.isdocument' => 'is:document',
            default                                       => $id,
        });
        $repository->autowireCommonRepository($translator);

        return $repository;
    }

    #[DataProvider('dataExpirationFilters')]
    public function testAddSearchCommandWhereClauseHandlesExpirationFilters(string $command, string $expected): void
    {
        $repository = $this->getRepository();
        $qb         = $this->connection->createQueryBuilder();
        $filter     = (object) ['command' => $command, 'string' => '', 'not' => false, 'strict' => false];

        $method = new \ReflectionMethod(AssetRepository::class, 'addSearchCommandWhereClause');

        [$expr, $params] = $method->invoke($repository, $qb, $filter);

        $this->assertSame($expected, (string) $expr);
        $this->assertSame(['par1' => true], $params);
    }

    /**
     * @return iterable<array{0: string, 1: string}>
     */
    public static function dataExpirationFilters(): iterable
    {
        yield ['is:expired', "(a.isPublished = :par1 AND a.publishDown IS NOT NULL AND a.publishDown <> '' AND a.publishDown < CURRENT_TIMESTAMP())"];
        yield ['is:pending', "(a.isPublished = :par1 AND a.publishUp IS NOT NULL AND a.publishUp <> '' AND a.publishUp > CURRENT_TIMESTAMP())"];
    }

    public function testGetSearchCommandsContainsExpirationFilters(): void
    {
        $repository = $this->getRepository();
        $commands   = $repository->getSearchCommands();
        $this->assertContains('mautic.asset.asset.searchcommand.isexpired', $commands);
        $this->assertContains('mautic.asset.asset.searchcommand.ispending', $commands);
    }

    /**
     * @return iterable<array{0: string, 1: string}>
     */
    public static function mimeCommandProvider(): iterable
    {
        yield ['is:image', 'image/%'];
        yield ['is:video', 'video/%'];
        yield ['is:audio', 'audio/%'];
    }

    #[DataProvider('mimeCommandProvider')]
    public function testAddSearchCommandWhereClauseForMimeCommands(string $command, string $expectedValue): void
    {
        $repository = $this->getRepository();
        $qb         = $this->connection->createQueryBuilder();
        $filter     = (object) ['command' => $command, 'string' => '', 'not' => false, 'strict' => false];

        $method = new \ReflectionMethod(AssetRepository::class, 'addSearchCommandWhereClause');

        [$expr, $parameters] = $method->invoke($repository, $qb, $filter);

        $this->assertCount(1, $parameters);
        $paramName = array_key_first($parameters);
        $this->assertSame($expectedValue, $parameters[$paramName]);
        $this->assertSame("a.mime LIKE :$paramName", (string) $expr);
    }

    public function testAddSearchCommandWhereClauseForDocument(): void
    {
        $repository = $this->getRepository();
        $qb         = $this->connection->createQueryBuilder();
        $filter     = (object) ['command' => 'is:document', 'string' => '', 'not' => false, 'strict' => false];

        $method = new \ReflectionMethod(AssetRepository::class, 'addSearchCommandWhereClause');

        [$expr, $parameters] = $method->invoke($repository, $qb, $filter);

        $this->assertCount(2, $parameters);
        $keys = array_keys($parameters);
        $this->assertSame('application/%', $parameters[$keys[0]]);
        $this->assertSame('text/%', $parameters[$keys[1]]);
        $this->assertSame(
            sprintf('(a.mime LIKE :%s OR a.mime LIKE :%s)', $keys[0], $keys[1]),
            (string) $expr
        );
    }
}

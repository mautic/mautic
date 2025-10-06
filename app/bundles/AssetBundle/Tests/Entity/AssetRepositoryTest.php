<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Tests\Entity;

use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\AssetBundle\Entity\Asset;
use Mautic\AssetBundle\Entity\AssetRepository;
use Mautic\CoreBundle\Test\Doctrine\RepositoryConfiguratorTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class AssetRepositoryTest extends TestCase
{
    use RepositoryConfiguratorTrait;

    private function getRepository(): AssetRepository
    {
        $repository = $this->configureRepository(Asset::class);
        $this->connection->method('createQueryBuilder')->willReturnCallback(fn () => new QueryBuilder($this->connection));

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(fn ($id) => match ($id) {
            'mautic.asset.asset.searchcommand.isexpired' => 'is:expired',
            'mautic.asset.asset.searchcommand.ispending' => 'is:pending',
            default                                      => $id,
        });
        $repository->setTranslator($translator);

        return $repository;
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataExpirationFilters')]
    public function testAddSearchCommandWhereClauseHandlesExpirationFilters(string $command, string $expected): void
    {
        $repository = $this->getRepository();
        $qb         = $this->connection->createQueryBuilder();
        $filter     = (object) ['command' => $command, 'string' => '', 'not' => false, 'strict' => false];

        $method = new \ReflectionMethod(AssetRepository::class, 'addSearchCommandWhereClause');
        $method->setAccessible(true);

        [$expr, $params] = $method->invoke($repository, $qb, $filter);

        self::assertSame($expected, (string) $expr);
        self::assertSame(['par1' => true], $params);
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
        self::assertContains('mautic.asset.asset.searchcommand.isexpired', $commands);
        self::assertContains('mautic.asset.asset.searchcommand.ispending', $commands);
    }
}

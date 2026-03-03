<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Entity;

use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CoreBundle\Test\Doctrine\RepositoryConfiguratorTrait;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Entity\PageRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class PageRepositoryTest extends TestCase
{
    use RepositoryConfiguratorTrait;

    private function getRepository(): PageRepository
    {
        $repository = $this->configureRepository(Page::class);
        $this->connection->method('createQueryBuilder')->willReturnCallback(fn () => new QueryBuilder($this->connection));

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(fn ($id) => match ($id) {
            'mautic.page.searchcommand.isexpired' => 'is:expired',
            'mautic.page.searchcommand.ispending' => 'is:pending',
            default                               => $id,
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

        $method = new \ReflectionMethod(PageRepository::class, 'addSearchCommandWhereClause');
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
        yield ['is:expired', "(p.isPublished = :par1 AND p.publishDown IS NOT NULL AND p.publishDown <> '' AND p.publishDown < CURRENT_TIMESTAMP())"];
        yield ['is:pending', "(p.isPublished = :par1 AND p.publishUp IS NOT NULL AND p.publishUp <> '' AND p.publishUp > CURRENT_TIMESTAMP())"];
    }

    public function testGetSearchCommandsContainsExpirationFilters(): void
    {
        $repository = $this->getRepository();
        $commands   = $repository->getSearchCommands();
        self::assertContains('mautic.page.searchcommand.isexpired', $commands);
        self::assertContains('mautic.page.searchcommand.ispending', $commands);
    }
}

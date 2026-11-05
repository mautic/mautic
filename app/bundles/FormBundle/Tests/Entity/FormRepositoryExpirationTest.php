<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Entity;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder as OrmQueryBuilder;
use Mautic\CoreBundle\Test\Doctrine\RepositoryConfiguratorTrait;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\FormRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class FormRepositoryExpirationTest extends TestCase
{
    use RepositoryConfiguratorTrait;

    private FormRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->configureRepository(Form::class);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(fn (string $id): string => match ($id) {
            'mautic.form.form.searchcommand.isexpired' => 'is:expired',
            'mautic.form.form.searchcommand.ispending' => 'is:pending',
            default                                    => $id,
        });

        $this->repository->autowireCommonRepository($translator);
    }

    #[DataProvider('ormSearchCommandDataProvider')]
    public function testAddSearchCommandWhereClauseBuildsFiltersWithOrmQueryBuilder(string $command, string $expected): void
    {
        $qb     = new OrmQueryBuilder($this->createMock(EntityManagerInterface::class));
        $filter = (object) ['command' => $command, 'string' => '', 'not' => false, 'strict' => false];

        $method = new \ReflectionMethod(FormRepository::class, 'addSearchCommandWhereClause');

        [$expr, $params] = $method->invoke($this->repository, $qb, $filter);

        $this->assertStringContainsString($expected, (string) $expr);
        $this->assertSame(['par1' => true], $params);
    }

    /**
     * @return iterable<array{0: string, 1: string}>
     */
    public static function ormSearchCommandDataProvider(): iterable
    {
        yield ['is:expired', 'f.publishDown < CURRENT_TIMESTAMP()'];
        yield ['is:pending', 'f.publishUp > CURRENT_TIMESTAMP()'];
    }
}

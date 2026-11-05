<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Entity;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Mautic\CoreBundle\Test\Doctrine\RepositoryConfiguratorTrait;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\FormRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class FormRepositoryTest extends TestCase
{
    use RepositoryConfiguratorTrait;

    private FormRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->configureRepository(Form::class);

        $this->entityManager->method('getExpressionBuilder')->willReturn(new Expr());
        $this->entityManager->method('createQueryBuilder')->willReturnCallback(fn (): QueryBuilder => new QueryBuilder($this->entityManager));

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(
            fn (string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string => match ($id) {
                'mautic.form.form.searchcommand.hasactions' => 'has:actions',
                'mautic.form.form.searchcommand.hasresults' => 'has:results',
                default                                     => $id,
            }
        );

        $this->repository->autowireCommonRepository($translator);
    }

    public function testAddSearchCommandWhereClauseHandlesHasActionsFilter(): void
    {
        $qb     = new QueryBuilder($this->entityManager);
        $filter = (object) ['command' => 'has:actions', 'string' => '', 'not' => false, 'strict' => false];

        $method = new \ReflectionMethod(FormRepository::class, 'addSearchCommandWhereClause');

        [$expr] = $method->invoke($this->repository, $qb, $filter);

        $this->assertSame('(SELECT count(a.id) FROM Mautic\\FormBundle\\Entity\\Action a WHERE a.form = f) > 0', (string) $expr);
    }

    public function testGetSearchCommandsContainsHasActions(): void
    {
        $commands = $this->repository->getSearchCommands();

        $this->assertContains('mautic.form.form.searchcommand.hasactions', $commands);
    }
}

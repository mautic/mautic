<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Entity;

use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CoreBundle\Test\Doctrine\RepositoryConfiguratorTrait;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Entity\UserRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class UserRepositoryTest extends TestCase
{
    use RepositoryConfiguratorTrait;

    private function getRepository(): UserRepository
    {
        $repository = $this->configureRepository(User::class);
        $this->connection->method('createQueryBuilder')->willReturnCallback(fn (): QueryBuilder => new QueryBuilder($this->connection));

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(fn (string $id): string => match ($id) {
            'mautic.user.user.searchcommand.neverloggedin' => 'is:never_logged_in',
            default                                       => $id,
        });
        $repository->setTranslator($translator);

        return $repository;
    }

    public function testNeverLoggedInFilterChecksLastLogin(): void
    {
        $repository = $this->getRepository();
        $queryBuilder = $this->connection->createQueryBuilder();
        $filter = (object) ['command' => 'is:never_logged_in', 'string' => '', 'not' => false, 'strict' => false];

        $method = new \ReflectionMethod(UserRepository::class, 'addSearchCommandWhereClause');

        [$expression, $parameters] = $method->invoke($repository, $queryBuilder, $filter);

        $this->assertSame('u.lastLogin IS NULL', (string) $expression);
        $this->assertSame([], $parameters);
    }

    public function testSearchCommandsContainNeverLoggedInFilter(): void
    {
        $this->assertContains('mautic.user.user.searchcommand.neverloggedin', $this->getRepository()->getSearchCommands());
    }
}

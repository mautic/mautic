<?php

namespace Mautic\ApiBundle\Tests\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\ApiBundle\Helper\EntityResultHelper;
use Mautic\CampaignBundle\Tests\CampaignTestAbstract;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\AppVersion;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Model\AbstractCommonModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Entity\UserRepository;
use Mautic\UserBundle\Model\UserModel;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class CommonApiControllerTest extends CampaignTestAbstract
{
    public function testAddAliasIfNotPresentWithOneColumnWithoutAlias(): void
    {
        $result = $this->getResultFromProtectedMethod('addAliasIfNotPresent', ['dateAdded', 'f']);

        $this->assertEquals('f.dateAdded', $result);
    }

    public function testAddAliasIfNotPresentWithOneColumnWithDifferentAlias(): void
    {
        $result = $this->getResultFromProtectedMethod('addAliasIfNotPresent', ['s.date_submitted', 'fs']);

        $this->assertEquals('s.date_submitted', $result);
    }

    public function testAddAliasIfNotPresentWithOneColumnWithAlias(): void
    {
        $result = $this->getResultFromProtectedMethod('addAliasIfNotPresent', ['f.dateAdded', 'f']);

        $this->assertEquals('f.dateAdded', $result);
    }

    public function testAddAliasIfNotPresentWithTwoColumnsWithAlias(): void
    {
        $result = $this->getResultFromProtectedMethod('addAliasIfNotPresent', ['f.dateAdded, f.dateModified', 'f']);

        $this->assertEquals('f.dateAdded,f.dateModified', $result);
    }

    public function testAddAliasIfNotPresentWithTwoColumnsWithoutAlias(): void
    {
        $result = $this->getResultFromProtectedMethod('addAliasIfNotPresent', ['dateAdded, dateModified', 'f']);

        $this->assertEquals('f.dateAdded,f.dateModified', $result);
    }

    public function testgetWhereFromRequestWithNoWhere(): void
    {
        $result = $this->getResultFromProtectedMethod('getWhereFromRequest', [new Request()]);

        $this->assertEquals([], $result);
    }

    public function testgetWhereFromRequestWithSomeWhere(): void
    {
        $where = [
            [
                'col'  => 'id',
                'expr' => 'eq',
                'val'  => 5,
            ],
        ];

        $request = new Request(['where' => $where]);
        $result  = $this->getResultFromProtectedMethod('getWhereFromRequest', [$request]);

        $this->assertEquals($where, $result);
    }

    /** @param array<int, mixed> $args */
    protected function getResultFromProtectedMethod(string $method, array $args): mixed
    {
        $controller = new CommonApiController(
            $this->createStub(CorePermissions::class),
            $this->createStub(Translator::class),
            $this->createStub(EntityResultHelper::class),
            $this->createStub(Router::class),
            $this->createStub(FormFactoryInterface::class),
            $this->createStub(AppVersion::class),
            $this->createStub(RequestStack::class),
            $this->createStub(ManagerRegistry::class),
            $this->createStub(ModelFactory::class),
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(CoreParametersHelper::class)
        );

        $controllerReflection = new \ReflectionClass(CommonApiController::class);
        $methodReflection     = $controllerReflection->getMethod($method);

        return $methodReflection->invokeArgs($controller, $args);
    }

    /**
     * Top-level 'internal' entries must be stripped from the where clause.
     */
    public function testSanitizeWhereClauseRemovesTopLevelInternalFlag(): void
    {
        $where = [
            ['col' => 'id', 'expr' => 'eq', 'val' => 1],
            ['internal' => true, 'expr' => 'formula', 'val' => '1=1'],
        ];

        $this->invokeProtectedSanitize($where);

        $this->assertCount(1, $where);
        $this->assertSame('eq', array_values($where)[0]['expr']);
    }

    /**
     * SECURITY: 'internal' entries nested inside andX must be stripped.
     * Before the fix, the copy-by-value bug in foreach allowed these to survive
     * sanitization, enabling SQL injection via the formula expression type.
     */
    public function testSanitizeWhereClauseRemovesNestedInternalFlagInsideAndX(): void
    {
        $where = [
            [
                'expr' => 'andX',
                'val'  => [
                    ['internal' => true, 'expr' => 'formula', 'val' => '1=1 UNION SELECT password FROM users--'],
                    ['col' => 'firstname', 'expr' => 'eq', 'val' => 'test'],
                ],
            ],
        ];

        $this->invokeProtectedSanitize($where);

        $nested = $where[0]['val'];
        $this->assertCount(1, $nested, 'Internal formula entry must be removed from nested andX group');
        $this->assertSame('eq', array_values($nested)[0]['expr']);
    }

    /**
     * SECURITY: 'internal' entries nested inside orX must also be stripped.
     */
    public function testSanitizeWhereClauseRemovesNestedInternalFlagInsideOrX(): void
    {
        $where = [
            [
                'expr' => 'orX',
                'val'  => [
                    ['internal' => true, 'expr' => 'formula', 'val' => 'injected SQL'],
                    ['col' => 'email', 'expr' => 'like', 'val' => '%@example.com'],
                ],
            ],
        ];

        $this->invokeProtectedSanitize($where);

        $nested = $where[0]['val'];
        $this->assertCount(1, $nested, 'Internal formula entry must be removed from nested orX group');
        $this->assertSame('like', array_values($nested)[0]['expr']);
    }

    /**
     * SECURITY: 'internal' entries must be stripped at arbitrary nesting depth.
     */
    public function testSanitizeWhereClauseRemovesDeeplyNestedInternalFlag(): void
    {
        $where = [
            [
                'expr' => 'andX',
                'val'  => [
                    [
                        'expr' => 'orX',
                        'val'  => [
                            ['internal' => true, 'expr' => 'formula', 'val' => 'injected SQL'],
                            ['col' => 'id', 'expr' => 'gt', 'val' => 0],
                        ],
                    ],
                ],
            ],
        ];

        $this->invokeProtectedSanitize($where);

        $inner = $where[0]['val'][0]['val'];
        $this->assertCount(1, $inner, 'Internal formula entry must be removed at depth > 1');
        $this->assertSame('gt', array_values($inner)[0]['expr']);
    }

    /**
     * Normal (non-internal) clauses at any level must be left untouched.
     */
    public function testSanitizeWhereClausePreservesNonInternalClauses(): void
    {
        $where = [
            ['col' => 'id', 'expr' => 'eq', 'val' => 5],
            [
                'expr' => 'andX',
                'val'  => [
                    ['col' => 'firstname', 'expr' => 'eq', 'val' => 'John'],
                ],
            ],
        ];

        $original = $where;
        $this->invokeProtectedSanitize($where);

        $this->assertSame($original, $where);
    }

    /**
     * Helper: invoke the protected sanitizeWhereClauseArrayFromRequest method,
     * passing $where by reference so mutations are visible to the caller.
     *
     * @param array<mixed> $where
     */
    private function invokeProtectedSanitize(array &$where): void
    {
        $controller = new CommonApiController(
            $this->createStub(CorePermissions::class),
            $this->createStub(Translator::class),
            $this->createStub(EntityResultHelper::class),
            $this->createStub(Router::class),
            $this->createStub(FormFactoryInterface::class),
            $this->createStub(AppVersion::class),
            $this->createStub(RequestStack::class),
            $this->createStub(ManagerRegistry::class),
            $this->createStub(ModelFactory::class),
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(CoreParametersHelper::class)
        );

        $reflection = new \ReflectionClass(CommonApiController::class);
        $method     = $reflection->getMethod('sanitizeWhereClauseArrayFromRequest');
        $method->invokeArgs($controller, [&$where]);
    }

    public function testGetBatchEntities(): void
    {
        $controller = new class($this->createStub(CorePermissions::class), $this->createStub(Translator::class), new EntityResultHelper(), $this->createStub(Router::class), $this->createStub(FormFactoryInterface::class), $this->createStub(AppVersion::class), $this->createStub(RequestStack::class), $this->createStub(ManagerRegistry::class), $this->createStub(ModelFactory::class), $this->createStub(EventDispatcherInterface::class), $this->createStub(CoreParametersHelper::class)) extends CommonApiController {
            /**
             * @param mixed[]                   $parameters
             * @param mixed[]                   $errors
             * @param AbstractCommonModel<User> $model
             *
             * @return mixed[]
             */
            public function testGetBatchEntities(array $parameters, array $errors, AbstractCommonModel $model): array
            {
                return $this->getBatchEntities($parameters, $errors, false, 'id', $model);
            }
        };

        $errors     = [];
        $parameters = [
            [
                'id'            => 3,
                'username'      => 'API_0YjVvxlg',
                'firstName'     => 'APIAPI_0YjVvxlg',
                'lastName'      => 'TestAPI_0YjVvxlg',
                'email'         => 'API_0YjVvxlg@email.com',
                'plainPassword' => [
                    'password' => 'topSecret007',
                    'confirm'  => 'topSecret007',
                ],
                'role' => 1,
            ],
            1 => [
                'id'            => 4,
                'username'      => 'API_PlEiXJyp',
                'firstName'     => 'APIAPI_PlEiXJyp',
                'lastName'      => 'TestAPI_PlEiXJyp',
                'email'         => 'API_PlEiXJyp@email.com',
                'plainPassword' => [
                    'password' => 'topSecret007',
                    'confirm'  => 'topSecret007',
                ],
                'role' => 1,
            ],
            2 => [
                'id'            => 5,
                'username'      => 'API_AfhKVHTr',
                'firstName'     => 'APIAPI_AfhKVHTr',
                'lastName'      => 'TestAPI_AfhKVHTr',
                'email'         => 'API_AfhKVHTr@email.com',
                'plainPassword' => [
                    'password' => 'topSecret007',
                    'confirm'  => 'topSecret007',
                ],
                'role' => 1,
            ],
        ];

        $users = [];
        foreach ([3, 5, 4] as $userId) {
            $user = $this->createMock(User::class);
            $user->expects($this->any())
                ->method('getId')
                ->willReturn($userId);
            $users[] = $user;
        }

        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->once())
            ->method('getTableAlias')
            ->willReturn('user');
        $model = $this->createMock(UserModel::class);
        $model->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);
        $model->expects($this->once())
            ->method('getEntities')
            ->willReturn($users);
        $entities   = $controller->testGetBatchEntities($parameters, $errors, $model);
        $this->assertSame(3, $entities[0]->getId());
        $this->assertSame(4, $entities[1]->getId());
        $this->assertSame(5, $entities[2]->getId());
    }
}

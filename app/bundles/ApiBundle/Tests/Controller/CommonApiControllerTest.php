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

    protected function getResultFromProtectedMethod($method, array $args)
    {
        $controller = new CommonApiController(
            $this->createMock(CorePermissions::class),
            $this->createMock(Translator::class),
            $this->createMock(EntityResultHelper::class),
            $this->createMock(Router::class),
            $this->createMock(FormFactoryInterface::class),
            $this->createMock(AppVersion::class),
            $this->createMock(RequestStack::class),
            $this->createMock(ManagerRegistry::class),
            $this->createMock(ModelFactory::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(CoreParametersHelper::class)
        );

        $controllerReflection = new \ReflectionClass(CommonApiController::class);
        $method               = $controllerReflection->getMethod($method);
        $method->setAccessible(true);

        return $method->invokeArgs($controller, $args);
    }

    public function testGetBatchEntities(): void
    {
        $controller = new class($this->createMock(CorePermissions::class), $this->createMock(Translator::class), new EntityResultHelper(), $this->createMock(Router::class), $this->createMock(FormFactoryInterface::class), $this->createMock(AppVersion::class), $this->createMock(RequestStack::class), $this->createMock(ManagerRegistry::class), $this->createMock(ModelFactory::class), $this->createMock(EventDispatcherInterface::class), $this->createMock(CoreParametersHelper::class)) extends CommonApiController {
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

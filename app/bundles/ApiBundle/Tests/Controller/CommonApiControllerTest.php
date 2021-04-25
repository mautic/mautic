<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Tests\Controller;

use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\ApiBundle\Helper\EntityResultHelper;
use Mautic\CampaignBundle\Tests\CampaignTestAbstract;
use Mautic\CoreBundle\Model\AbstractCommonModel;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Entity\UserRepository;
use Mautic\UserBundle\Model\UserModel;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class CommonApiControllerTest extends CampaignTestAbstract
{
    public function testAddAliasIfNotPresentWithOneColumnWithoutAlias()
    {
        $result = $this->getResultFromProtectedMethod('addAliasIfNotPresent', ['dateAdded', 'f']);

        $this->assertEquals('f.dateAdded', $result);
    }

    public function testAddAliasIfNotPresentWithOneColumnWithDifferentAlias()
    {
        $result = $this->getResultFromProtectedMethod('addAliasIfNotPresent', ['s.date_submitted', 'fs']);

        $this->assertEquals('s.date_submitted', $result);
    }

    public function testAddAliasIfNotPresentWithOneColumnWithAlias()
    {
        $result = $this->getResultFromProtectedMethod('addAliasIfNotPresent', ['f.dateAdded', 'f']);

        $this->assertEquals('f.dateAdded', $result);
    }

    public function testAddAliasIfNotPresentWithTwoColumnsWithAlias()
    {
        $result = $this->getResultFromProtectedMethod('addAliasIfNotPresent', ['f.dateAdded, f.dateModified', 'f']);

        $this->assertEquals('f.dateAdded,f.dateModified', $result);
    }

    public function testAddAliasIfNotPresentWithTwoColumnsWithoutAlias()
    {
        $result = $this->getResultFromProtectedMethod('addAliasIfNotPresent', ['dateAdded, dateModified', 'f']);

        $this->assertEquals('f.dateAdded,f.dateModified', $result);
    }

    public function testgetWhereFromRequestWithNoWhere()
    {
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $result = $this->getResultFromProtectedMethod('getWhereFromRequest', [], $request);

        $this->assertEquals([], $result);
    }

    public function testgetWhereFromRequestWithSomeWhere()
    {
        $where = [
            [
                'col'  => 'id',
                'expr' => 'eq',
                'val'  => 5,
            ],
        ];

        $request = $this->getMockBuilder(Request::class)
            ->setMethods(['get'])
            ->disableOriginalConstructor()
            ->getMock();

        $request->method('get')
            ->willReturn($where);

        $result = $this->getResultFromProtectedMethod('getWhereFromRequest', [], $request);

        $this->assertEquals($where, $result);
    }

    protected function getResultFromProtectedMethod($method, array $args, Request $request = null)
    {
        $controller = new CommonApiController();

        if ($request) {
            $controller->setRequest($request);
        }

        $controllerReflection = new \ReflectionClass(CommonApiController::class);
        $method               = $controllerReflection->getMethod($method);
        $method->setAccessible(true);

        return $method->invokeArgs($controller, $args);
    }

    public function testGetBatchEntities(): void
    {
        $controller = new class() extends CommonApiController {
            public function testGetBatchEntities(array $parameters, array $errors, AbstractCommonModel $model): ?array
            {
                return $this->getBatchEntities($parameters, $errors, false, 'id', $model);
            }
        };

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->with('mautic.api.helper.entity_result')
            ->willReturn(new EntityResultHelper());
        $controller->setContainer($container);

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
        $model      = $this->createMock(UserModel::class);
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

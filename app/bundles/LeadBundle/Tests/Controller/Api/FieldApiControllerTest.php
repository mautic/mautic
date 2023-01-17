<?php

namespace Mautic\LeadBundle\Tests\Controller\Api;

use Mautic\ApiBundle\Helper\EntityResultHelper;
use Mautic\CampaignBundle\Tests\CampaignTestAbstract;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Controller\Api\FieldApiController;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;

class FieldApiControllerTest extends CampaignTestAbstract
{
    private $defaultWhere = [
        [
            'col'  => 'object',
            'expr' => 'eq',
            'val'  => null,
        ],
    ];

    public function testgetWhereFromRequestWithNoWhere()
    {
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $result = $this->getResultFromProtectedMethod('getWhereFromRequest', [], $request);

        $this->assertEquals($this->defaultWhere, $result);
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
            ->onlyMethods(['get'])
            ->disableOriginalConstructor()
            ->getMock();

        $request->method('get')
            ->willReturn($where);

        $result = $this->getResultFromProtectedMethod('getWhereFromRequest', [], $request);

        $this->assertEquals(array_merge($where, $this->defaultWhere), $result);
    }

    protected function getResultFromProtectedMethod($method, array $args, Request $request = null)
    {
        $controller = new FieldApiController(
            $this->createMock(CorePermissions::class),
            $this->createMock(Translator::class),
            $this->createMock(EntityResultHelper::class),
            $this->createMock(Router::class),
            $this->createMock(FormFactoryInterface::class)
        );

        if ($request) {
            $controller->setRequest($request);
        }

        $controllerReflection = new \ReflectionClass(FieldApiController::class);
        $method               = $controllerReflection->getMethod($method);
        $method->setAccessible(true);

        return $method->invokeArgs($controller, $args);
    }
}

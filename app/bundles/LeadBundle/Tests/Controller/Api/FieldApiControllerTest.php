<?php

namespace Mautic\LeadBundle\Tests\Controller\Api;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\ApiBundle\Helper\EntityResultHelper;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\AppVersion;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Controller\Api\FieldApiController;
use Mautic\LeadBundle\Entity\LeadFieldRepository;
use Mautic\LeadBundle\Model\FieldModel;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class FieldApiControllerTest extends TestCase
{
    private $defaultWhere = [
        [
            'col'  => 'object',
            'expr' => 'eq',
            'val'  => null,
        ],
    ];

    public function testgetWhereFromRequestWithNoWhere(): void
    {
        $request = new Request();
        $result  = $this->getResultFromProtectedMethod('getWhereFromRequest', [$request], $request);

        $this->assertEquals($this->defaultWhere, $result);
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
        $result  = $this->getResultFromProtectedMethod('getWhereFromRequest', [$request], $request);

        $this->assertEquals(array_merge($where, $this->defaultWhere), $result);
    }

    protected function getResultFromProtectedMethod($method, array $args, Request $request)
    {
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getCurrentRequest')
            ->willReturn($request);

        $fieldRepository = $this->createMock(LeadFieldRepository::class);
        $fieldModel      = $this->createMock(FieldModel::class);
        $fieldModel->method('getRepository')
            ->willReturn($fieldRepository);
        $modelFactory = $this->createMock(ModelFactory::class);
        $modelFactory->method('getModel')
            ->with('lead.field')
            ->willReturn($fieldModel);
        $controller   = new FieldApiController(
            $this->createMock(CorePermissions::class),
            $this->createMock(Translator::class),
            $this->createMock(EntityResultHelper::class),
            $this->createMock(Router::class),
            $this->createMock(FormFactoryInterface::class),
            $this->createMock(AppVersion::class),
            $requestStack,
            $this->createMock(ManagerRegistry::class),
            $modelFactory,
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(CoreParametersHelper::class),
        );

        $controllerReflection = new \ReflectionClass(FieldApiController::class);
        $method               = $controllerReflection->getMethod($method);
        $method->setAccessible(true);

        return $method->invokeArgs($controller, $args);
    }
}

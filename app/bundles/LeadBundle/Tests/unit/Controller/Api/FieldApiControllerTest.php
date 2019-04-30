<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Controller\Api;

use Mautic\CampaignBundle\Test\CampaignModelTestCase;
use Mautic\LeadBundle\Controller\Api\FieldApiController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class FieldApiControllerTest.
 */
class FieldApiControllerTest extends CampaignModelTestCase
{
    private $defaultWhere = [
        [
            'col'  => 'object',
            'expr' => 'eq',
            'val'  => null,
        ],
    ];

    public function testGetWhereFromRequestWithNoWhere()
    {
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $result = $this->getResultFromProtectedMethod('getWhereFromRequest', [], $request);

        $this->assertEquals($this->defaultWhere, $result);
    }

    public function testGetWhereFromRequestWithSomeWhere()
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

        $this->assertEquals(array_merge($where, $this->defaultWhere), $result);
    }

    /**
     * @param $method
     * @param array        $args
     * @param Request|null $request
     *
     * @return mixed
     *
     * @throws \ReflectionException
     */
    protected function getResultFromProtectedMethod($method, array $args, Request $request = null)
    {
        $controller = new FieldApiController();

        if ($request) {
            $controller->setRequest($request);
        }

        $controllerReflection = new \ReflectionClass(FieldApiController::class);
        $method               = $controllerReflection->getMethod($method);
        $method->setAccessible(true);

        return $method->invokeArgs($controller, $args);
    }
}

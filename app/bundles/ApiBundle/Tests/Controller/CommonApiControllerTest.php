<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Tests\Model;

use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\CampaignBundle\Tests\CampaignTestAbstract;

class CommonApiControllerTest extends CampaignTestAbstract
{
    public function testAddAliasIfNotPresentWithOneColumnWithoutAlias()
    {
        $result = $this->getResultFromProtectedMethod('addAliasIfNotPresent', ['dateAdded', 'f']);

        $this->assertEquals('f.dateAdded', $result);
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

    protected function getResultFromProtectedMethod($method, array $args)
    {
        $controller           = new CommonApiController();
        $controllerReflection = new \ReflectionClass(CommonApiController::class);
        $method               = $controllerReflection->getMethod($method);
        $method->setAccessible(true);

        return $method->invokeArgs($controller, $args);
    }
}

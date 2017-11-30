<?php
/**
 * Created by PhpStorm.
 * User: Werner
 * Date: 6/20/2017
 * Time: 8:10 AM.
 */

namespace MauticCrmBundle\Integration;

use MauticPlugin\MauticCrmBundle\Integration\DynamicsIntegration;

class DynamicsIntegrationTest extends \PHPUnit_Framework_TestCase
{
    /** @var DynamicsIntegration */
    private $integration;

    protected function setUp()
    {
        parent::setUp();

        $this->integration = new DynamicsIntegration();
    }

    public function testIntegration()
    {
        $this->assertSame('Dynamics', $this->integration->getName());
    }
}

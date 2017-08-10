<?php
/**
 * Created by PhpStorm.
 * User: Werner
 * Date: 6/20/2017
 * Time: 7:43 AM.
 */

namespace MauticCrmBundle\Api;

use MauticPlugin\MauticCrmBundle\Api\DynamicsApi;
use MauticPlugin\MauticCrmBundle\Integration\DynamicsIntegration;

class DynamicsApiTest extends \PHPUnit_Framework_TestCase
{
    /** @var DynamicsApi */
    private $api;

    /** @var DynamicsIntegration */
    private $integration;

    protected function setUp()
    {
        parent::setUp();

        $this->integration = new DynamicsIntegration();
        $this->api         = new DynamicsApi($this->integration);
    }

    public function testIntegration()
    {
        $this->assertSame('Dynamics', $this->integration->getName());
    }

    public function testGetLeads()
    {
    }

    public function testGetLeadFields()
    {
    }

    public function testCompanies()
    {
    }

    public function testCreateLead()
    {
    }

    public function testRequest()
    {
    }
}

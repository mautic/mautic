<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\Tests;

use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PluginBundle\Helper\IntegrationHelper;

class IntegrationTest extends \PHPUnit_Framework_TestCase
{
    public function testAmendLeadDataBeforeMauticPopulate()
    {
        $integrationObject = $this->getIntegrationObject();

        //data object
        $object = 'contact';
        $data   = ['first_name' => 'first_name', 'last_name' => 'last_name', 'email' => 'email'];

        $object = 'company';
        $data   = ['company_name' => 'company_name', 'email' => 'company_email'];

        $count = $integrationObject->amendLeadDataBeforeMauticPopulate($data, $object);

        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testGetMauticLead()
    {
        $integrationObject = $this->getIntegrationObject();

        $data = ['first_name' => 'first_name', 'last_name' => 'last_name', 'email' => 'email'];

        $lead = $integrationObject->getMauticLead($data);
        $this->assertInstanceOf(new Lead(), $lead);
    }

    public function testGetMauticCompany()
    {
        $integrationObject = $this->getIntegrationObject();

        $data = ['company_name' => 'company_name', 'email' => 'company_email'];

        $company = $integrationObject->getMauticCompany($data);
        $this->assertInstanceOf(new Company(), $company);
    }

    public function getIntegrationObject()
    {
        //create an integration object
        return $this->getMockBuilder(IntegrationHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}

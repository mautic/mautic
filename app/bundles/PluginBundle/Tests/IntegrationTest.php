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

class IntegrationTest extends \PHPUnit_Framework_TestCase
{
    public function testAmendLeadDataBeforeMauticPopulate()
    {
        //data object

        //create an integration object
        $this->getIntegrationObject();

        //use the getLead() command to test contacts

        //use the getCompany() command to test companies
    }

    public function getIntegrationObject()
    {
    }
}

<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\IpLookup;

use Mautic\CoreBundle\IpLookup\GeobytesLookup;

/**
 * Class GeobytesLookupTest.
 */
class GeobytesLookupTest extends \PHPUnit_Framework_TestCase
{
    public function testIpLookupSuccessful()
    {
        // Mock http connector
        $mockHttp = $this->getMockBuilder('Joomla\Http\Http')
            ->disableOriginalConstructor()
            ->getMock();

        // Mock a successful response
        $mockResponse = $this->getMockBuilder('Joomla\Http\Response')
            ->getMock();
        $mockResponse->code = 200;
        $mockResponse->body = '{"geobytesforwarderfor":"","geobytesremoteip":"1.2.3.4","geobytesipaddress":"192.30.252.131","geobytescertainty":"100","geobytesinternet":"US","geobytescountry":"United States","geobytesregionlocationcode":"USMD","geobytesregion":"Maryland","geobytescode":"MD","geobyteslocationcode":"USMDKNOX","geobytescity":"Knoxville","geobytescityid":"29706","geobytesfqcn":"Knoxville, MD, United States","geobyteslatitude":"39.352600","geobyteslongitude":"-77.664101","geobytescapital":"Washington, DC","geobytestimezone":"-05:00","geobytesnationalitysingular":"American","geobytespopulation":"278058881","geobytesnationalityplural":"Americans","geobytesmapreference":"North America ","geobytescurrency":"US Dollar","geobytescurrencycode":"USD","geobytestitle":"The United States"}';

        $mockHttp->expects($this->once())
            ->method('get')
            ->willReturn($mockResponse);

        $ipService = new GeobytesLookup(null, null, __DIR__.'/../../../../cache/test', null, $mockHttp);

        $details = $ipService->setIpAddress('192.30.252.131')->getDetails();

        $this->assertEquals('Knoxville', $details['city']);
        $this->assertEquals('Maryland', $details['region']);
        $this->assertEquals('United States', $details['country']);
        $this->assertEquals('', $details['zipcode']);
        $this->assertEquals('39.352600', $details['latitude']);
        $this->assertEquals('-77.664101', $details['longitude']);
        $this->assertEquals('-05:00', $details['timezone']);
    }
}

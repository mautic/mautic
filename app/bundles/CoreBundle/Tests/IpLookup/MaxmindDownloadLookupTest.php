<?php
/**
 * @package     Mautic
 * @copyright   2015 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\IpLookup;

/**
 * Class MaxmindDownloadTest
 */
class MaxmindDownloadLookupTest extends IpLookup
{
    public function testDownloadDataStore()
    {
        $ipFactory = $this->container->get('mautic.ip_lookup.factory');

        /** @var \Mautic\CoreBundle\IpLookup\MaxmindDownloadLookup $service */
        $service = $ipFactory->getService('maxmind_download');
        $result  = $service->downloadRemoteDataStore();

        $this->assertTrue($result);
    }

    public function testIpLookupSuccessful()
    {
        $this->isIpLookupSuccessful('maxmind_download');
    }
}
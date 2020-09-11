<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\IntegrationsBundle\Tests\Unit\Sync\DAO\Sync\Order;

use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\ObjectChangeDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\OrderDAO;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

final class OrderDAOTest extends TestCase
{
    /**
     * Test that the retry object is removed from the synced objects and the success object is present.
     */
    public function testGetSuccessfullySyncedObjects()
    {
        $orderDAO      = new OrderDAO(new \DateTimeImmutable(), false, 0);
        $successObject = new ObjectChangeDAO('IntegrationA', 'Contact', 'integration-id-1', 'lead', 123);
        $retryObject   = new ObjectChangeDAO('IntegrationA', 'Contact', 'integration-id-2', 'lead', 456);

        $orderDAO->addObjectChange($successObject);
        $orderDAO->addObjectChange($retryObject);
        $orderDAO->retrySyncLater($retryObject);

        Assert::assertSame(
            [$successObject],
            $orderDAO->getSuccessfullySyncedObjects()
        );
    }
}

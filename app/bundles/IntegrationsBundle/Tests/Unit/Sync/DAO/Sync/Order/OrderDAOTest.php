<?php

declare(strict_types=1);

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
    public function testGetSuccessfullySyncedObjects(): void
    {
        $orderDAO      = new OrderDAO(new \DateTimeImmutable(), false, 'IntegrationA');
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

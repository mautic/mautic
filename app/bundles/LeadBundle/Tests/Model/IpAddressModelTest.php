<?php

namespace Mautic\LeadBundle\Tests\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\DriverException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\IpAddressModel;
use Psr\Log\LoggerInterface;

class IpAddressModelTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var EntityManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $entityManager;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|LoggerInterface
     */
    private $logger;

    /**
     * @var IpAddressModel
     */
    private $ipAddressModel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager  = $this->createMock(EntityManager::class);
        $this->logger         = $this->createMock(LoggerInterface::class);
        $this->ipAddressModel = new IpAddressModel($this->entityManager, $this->logger);
    }

    /**
     * This test ensures it won't fail if there are no IP addresses.
     */
    public function testSaveIpAddressReferencesForContactWhenNoIps(): void
    {
        $this->entityManager->expects($this->never())
            ->method('getConnection');

        $this->ipAddressModel->saveIpAddressesReferencesForContact(new Lead());
    }

    public function testSaveIpAddressReferencesForContactThatHasIpsButNoChanges(): void
    {
        $contact      = $this->createMock(Lead::class);
        $ipAddress    = $this->createMock(IpAddress::class);
        $ipAddresses  = new ArrayCollection(['1.2.3.4' => $ipAddress]);
        $connection   = $this->createMock(Connection::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $contact->expects($this->exactly(1))
            ->method('getIpAddresses')
            ->willReturn($ipAddresses);

        $this->entityManager->expects($this->never())
            ->method('getConnection');

        $this->ipAddressModel->saveIpAddressesReferencesForContact($contact);
    }

    public function testSaveIpAddressReferencesForContactThatHasIpsWithSomeAdded(): void
    {
        $contact        = $this->createMock(Lead::class);
        $ipAddressAdded = $this->createMock(IpAddress::class);
        $ipAddressOld   = $this->createMock(IpAddress::class);
        $ipAddresses    = new ArrayCollection(['1.2.3.999' => $ipAddressOld, '1.2.3.4' => $ipAddressAdded]);
        $connection     = $this->createMock(Connection::class);
        $queryBuilder   = $this->createMock(QueryBuilder::class);

        $contact->expects($this->exactly(2))
            ->method('getIpAddresses')
            ->willReturn($ipAddresses);

        $contact->expects($this->exactly(2))
            ->method('getId')
            ->willReturn(55);

        $contact->expects($this->exactly(2))
            ->method('getChanges')
            ->willReturn(['ipAddressList' => ['1.2.3.4' => $ipAddressAdded]]);

        $ipAddressAdded->expects($this->exactly(2))
            ->method('getId')
            ->willReturn(44);

        $ipAddressAdded->expects($this->once())
            ->method('getIpAddress')
            ->willReturn('1.2.3.4');

        $ipAddressOld->expects($this->never())
            ->method('getId');

        $ipAddressOld->expects($this->once())
            ->method('getIpAddress')
            ->willReturn('1.2.3.999');

        $queryBuilder->expects($this->once())
            ->method('execute');

        $connection->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $this->entityManager->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection);

        $this->ipAddressModel->saveIpAddressesReferencesForContact($contact);

        $this->assertCount(2, $contact->getIpAddresses());
    }

    public function testSaveIpAddressReferencesForContactWhenSomeIpsIfTheReferenceExistsAlready(): void
    {
        $contact      = $this->createMock(Lead::class);
        $ipAddress    = $this->createMock(IpAddress::class);
        $ipAddresses  = new ArrayCollection(['1.2.3.4' => $ipAddress]);
        $connection   = $this->createMock(Connection::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $contact->expects($this->exactly(2))
            ->method('getIpAddresses')
            ->willReturn($ipAddresses);

        $contact->expects($this->exactly(3))
            ->method('getId')
            ->willReturn(55);

        $contact->expects($this->once())
            ->method('getChanges')
            ->willReturn(['ipAddressList' => ['1.2.3.4' => $ipAddress]]);

        $ipAddress->expects($this->exactly(3))
            ->method('getId')
            ->willReturn(44);

        $ipAddress->expects($this->once())
            ->method('getIpAddress')
            ->willReturn('1.2.3.4');

        $queryBuilder->expects($this->once())
            ->method('execute')
            ->willThrowException(new UniqueConstraintViolationException('', $this->createMock(DriverException::class)));

        $connection->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $this->entityManager->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection);

        $this->ipAddressModel->saveIpAddressesReferencesForContact($contact);

        $this->assertCount(1, $contact->getIpAddresses());
    }
}

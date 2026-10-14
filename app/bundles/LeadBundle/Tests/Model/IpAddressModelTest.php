<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Model;

use Doctrine\DBAL\Connection;

use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Entity\IpAddressRepository;
use Mautic\CoreBundle\Test\ReflectionHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\IpAddressModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class IpAddressModelTest extends TestCase
{
    /**
     * @var MockObject&EntityManager
     */
    private MockObject $entityManager;

    private IpAddressModel $ipAddressModel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager  = $this->createMock(EntityManager::class);
        $this->ipAddressModel = new IpAddressModel($this->entityManager, $this->createStub(LoggerInterface::class), $this->createStub(IpAddressRepository::class));
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
        $contact      = new Lead();
        $ipAddress    = $this->createStub(IpAddress::class);
        $contact->getIpAddresses()->set('1.2.3.4', $ipAddress);

        $this->entityManager->expects($this->never())
            ->method('getConnection');

        $this->ipAddressModel->saveIpAddressesReferencesForContact($contact);
    }

    public function testSaveIpAddressReferencesForContactThatHasIpsWithSomeAdded(): void
    {
        $contact        = new Lead();
        $contact->setId(55);
        $ipAddressOld   = new IpAddress('1.2.3.999');
        $contact->getIpAddresses()->set('1.2.3.999', $ipAddressOld);
        $ipAddressAdded = new IpAddress('1.2.3.4');
        ReflectionHelper::setEntityId($ipAddressAdded, 44);
        $contact->addIpAddress($ipAddressAdded);
        $connection     = $this->createMock(Connection::class);
        $queryBuilder   = $this->createMock(QueryBuilder::class);

        $queryBuilder->expects($this->once())
            ->method('executeStatement');

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
        $contact      = new Lead();
        $contact->setId(55);
        $ipAddress    = new IpAddress('1.2.3.4');
        ReflectionHelper::setEntityId($ipAddress, 44);
        $contact->addIpAddress($ipAddress);
        $connection   = $this->createMock(Connection::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $queryBuilder->expects($this->once())
            ->method('executeStatement')
            ->willThrowException(new UniqueConstraintViolationException($this->createStub(DriverException::class), null));

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

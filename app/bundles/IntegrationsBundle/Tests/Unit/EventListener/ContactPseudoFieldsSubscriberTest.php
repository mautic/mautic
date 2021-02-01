<?php

declare(strict_types=1);

/*
 * @copyright   2021 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\IntegrationsBundle\Tests\Unit\EventListener;

use Doctrine\DBAL\Connection;
use Mautic\IntegrationsBundle\Event\InternalContactProcessPseudFieldsEvent;
use Mautic\IntegrationsBundle\EventListener\ContactPseudoFieldsSubscriber;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\FieldDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectHelper\ContactObjectHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Model\DoNotContact;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ContactPseudoFieldsSubscriberTest extends TestCase
{
    /**
     * @var LeadModel|\PHPUnit\Framework\MockObject\MockObject
     */
    private $leadModel;
    /**
     * @var LeadRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $leadRepository;
    /**
     * @var Connection|\PHPUnit\Framework\MockObject\MockObject
     */
    private $connection;
    /**
     * @var FieldModel|\PHPUnit\Framework\MockObject\MockObject
     */
    private $fieldModel;
    /**
     * @var DoNotContact|\PHPUnit\Framework\MockObject\MockObject
     */
    private $dncModel;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|EventDispatcherInterface
     */
    private $dispatcher;

    protected function setUp(): void
    {
        $this->leadModel      = $this->createMock(LeadModel::class);
        $this->leadRepository = $this->createMock(LeadRepository::class);
        $this->connection     = $this->createMock(Connection::class);
        $this->fieldModel     = $this->createMock(FieldModel::class);
        $this->dncModel       = $this->createMock(DoNotContact::class);
        $this->dispatcher     = $this->createMock(EventDispatcherInterface::class);
    }

    public function testProcessPseudoFieldsOwnerId(): void
    {
        $lead   = new Lead();
        $fields = [
            'owner_id'                  => new FieldDAO('owner_id', new NormalizedValueDAO(NormalizedValueDAO::INT_TYPE, 1)),
        ];

        $internalContactProcessPseudFieldsEvent =  new InternalContactProcessPseudFieldsEvent($lead, $fields, 'TestIntegration');

        $this->leadModel
            ->expects($this->once())
            ->method('updateLeadOwner')
            ->willReturn(true);

        $this->dncModel
            ->expects($this->exactly(0))
            ->method('removeDncForContact')
            ->willReturn(true);

        $this->dncModel
            ->expects($this->exactly(0))
            ->method('addDncForContact')
            ->willReturn(true);

        $contactObjectHelper = new ContactObjectHelper($this->leadModel, $this->leadRepository, $this->connection, $this->fieldModel, $this->dncModel, $this->dispatcher);

        $contactPseudoFieldsSubscriber = new ContactPseudoFieldsSubscriber($contactObjectHelper);
        $contactPseudoFieldsSubscriber->processPseudoFields($internalContactProcessPseudFieldsEvent);
    }

    public function testProcessPseudoFieldsDncTrue()
    {
        $lead   = new Lead();
        $fields = [
            'mautic_internal_dnc_email' => new FieldDAO('mautic_internal_dnc_email', new NormalizedValueDAO(NormalizedValueDAO::BOOLEAN_TYPE, true)),
        ];
        $internalContactProcessPseudFieldsEvent =  new InternalContactProcessPseudFieldsEvent($lead, $fields, 'TestIntegration');

        $this->leadModel
            ->expects($this->exactly(0))
            ->method('updateLeadOwner')
            ->willReturn(true);

        $this->dncModel
            ->expects($this->exactly(0))
            ->method('removeDncForContact')
            ->willReturn(true);

        $this->dncModel
            ->expects($this->exactly(1))
            ->method('addDncForContact')
            ->willReturn(true);

        $contactObjectHelper = new ContactObjectHelper($this->leadModel, $this->leadRepository, $this->connection, $this->fieldModel, $this->dncModel, $this->dispatcher);

        $contactPseudoFieldsSubscriber = new ContactPseudoFieldsSubscriber($contactObjectHelper);
        $contactPseudoFieldsSubscriber->processPseudoFields($internalContactProcessPseudFieldsEvent);
    }

    public function testProcessPseudoFieldsDncFalse()
    {
        $lead   = new Lead();
        $fields = [
            'mautic_internal_dnc_email' => new FieldDAO('mautic_internal_dnc_email', new NormalizedValueDAO(NormalizedValueDAO::BOOLEAN_TYPE, false)),
        ];
        $internalContactProcessPseudFieldsEvent =  new InternalContactProcessPseudFieldsEvent($lead, $fields, 'TestIntegration');

        $this->leadModel
            ->expects($this->exactly(0))
            ->method('updateLeadOwner')
            ->willReturn(true);

        $this->dncModel
            ->expects($this->exactly(1))
            ->method('removeDncForContact')
            ->willReturn(true);

        $this->dncModel
            ->expects($this->exactly(0))
            ->method('addDncForContact')
            ->willReturn(true);

        $contactObjectHelper = new ContactObjectHelper($this->leadModel, $this->leadRepository, $this->connection, $this->fieldModel, $this->dncModel, $this->dispatcher);

        $contactPseudoFieldsSubscriber = new ContactPseudoFieldsSubscriber($contactObjectHelper);
        $contactPseudoFieldsSubscriber->processPseudoFields($internalContactProcessPseudFieldsEvent);
    }
}

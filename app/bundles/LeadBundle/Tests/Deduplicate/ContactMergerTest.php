<?php

namespace Mautic\LeadBundle\Tests\Deduplicate;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\LeadBundle\Deduplicate\ContactMerger;
use Mautic\LeadBundle\Deduplicate\Exception\SameContactException;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Entity\MergeRecordRepository;
use Mautic\LeadBundle\Entity\Tag;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\UserBundle\Entity\User;
use Monolog\Logger;
use Symfony\Component\EventDispatcher\EventDispatcher;

class ContactMergerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|LeadModel
     */
    private \PHPUnit\Framework\MockObject\MockObject $leadModel;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&LeadRepository
     */
    private \PHPUnit\Framework\MockObject\MockObject $leadRepo;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|MergeRecordRepository
     */
    private \PHPUnit\Framework\MockObject\MockObject $mergeRecordRepo;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|EventDispatcher
     */
    private \PHPUnit\Framework\MockObject\MockObject $dispatcher;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Logger
     */
    private \PHPUnit\Framework\MockObject\MockObject $logger;

    protected function setUp(): void
    {
        $this->leadModel       = $this->createMock(LeadModel::class);
        $this->leadRepo        = $this->createMock(LeadRepository::class);
        $this->mergeRecordRepo = $this->createMock(MergeRecordRepository::class);
        $this->dispatcher      = $this->createMock(EventDispatcher::class);
        $this->logger          = $this->createMock(Logger::class);

        $this->leadModel->method('getRepository')->willReturn($this->leadRepo);
    }

    public function testMergeTimestamps(): void
    {
        $oldestDateTime = new \DateTime('-60 minutes');
        $latestDateTime = new \DateTime('-30 minutes');

        $winner = new Lead();
        $winner->setLastActive($oldestDateTime);
        $winner->setDateIdentified($latestDateTime);

        $loser  = new Lead();
        $loser->setLastActive($latestDateTime);
        $loser->setDateIdentified($oldestDateTime);

        $this->getMerger()->mergeTimestamps($winner, $loser);

        $this->assertEquals($latestDateTime, $winner->getLastActive());
        $this->assertEquals($oldestDateTime, $winner->getDateIdentified());

        // Test with null date identified loser
        $winner->setDateIdentified($latestDateTime);
        $loser->setDateIdentified(null);

        $this->getMerger()->mergeTimestamps($winner, $loser);

        $this->assertEquals($latestDateTime, $winner->getDateIdentified());

        // Test with null date identified winner
        $winner->setDateIdentified(null);
        $loser->setDateIdentified($latestDateTime);

        $this->getMerger()->mergeTimestamps($winner, $loser);

        $this->assertEquals($latestDateTime, $winner->getDateIdentified());
    }

    public function testMergeIpAddresses(): void
    {
        $winner = new Lead();
        $winner->addIpAddress((new IpAddress('1.2.3.4'))->setIpDetails(['extra' => 'from winner']));
        $winner->addIpAddress((new IpAddress('4.3.2.1'))->setIpDetails(['extra' => 'from winner']));
        $winner->addIpAddress((new IpAddress('5.6.7.8'))->setIpDetails(['extra' => 'from winner']));

        $loser = new Lead();
        $loser->addIpAddress((new IpAddress('5.6.7.8'))->setIpDetails(['extra' => 'from loser']));
        $loser->addIpAddress((new IpAddress('8.7.6.5'))->setIpDetails(['extra' => 'from loser']));

        $this->getMerger()->mergeIpAddressHistory($winner, $loser);

        $ipAddresses = $winner->getIpAddresses();
        $this->assertCount(4, $ipAddresses);

        $ipAddressArray = $ipAddresses->toArray();

        $expectedIpAddressArray = [
            '1.2.3.4' => ['extra' => 'from winner'],
            '4.3.2.1' => ['extra' => 'from winner'],
            '5.6.7.8' => ['extra' => 'from winner'],
            '8.7.6.5' => ['extra' => 'from loser'],
        ];

        foreach ($expectedIpAddressArray as $ipAddress => $ipId) {
            $this->assertSame($ipAddress, $ipAddressArray[$ipAddress]->getIpAddress());
            $this->assertSame($ipId, $ipAddressArray[$ipAddress]->getIpDetails());
        }
    }

    public function testMergeFieldDataWithLoserAsNewlyUpdated(): void
    {
        $winner = $this->getMockBuilder(Lead::class)
            ->getMock();
        $winner->expects($this->once())
            ->method('getProfileFields')
            ->willReturn(
                [
                    'id'     => 1,
                    'points' => 10,
                    'email'  => 'winner@test.com',
                ]
            );

        $loser = $this->getMockBuilder(Lead::class)
            ->getMock();
        $loser->expects($this->once())
            ->method('getProfileFields')
            ->willReturn(
                [
                    'id'     => 2,
                    'points' => 20,
                    'email'  => 'loser@test.com',
                ]
            );

        $merger = $this->getMerger();

        $winnerDateModified = new \DateTime('-30 minutes');
        $loserDateModified  = new \DateTime();
        $winner->expects($this->exactly(1))
            ->method('getDateModified')
            ->willReturn($winnerDateModified);
        $loser->expects($this->exactly(1))
            ->method('getDateModified')
            ->willReturn($loserDateModified);
        $winner->expects($this->once())
            ->method('getFieldValue')
            ->with('email')
            ->willReturn('winner@test.com');

        $winner->expects($this->once())
            ->method('getField')
            ->with('email')
            ->willReturn([
                'value'         => 'winner@test.com',
                'id'            => 22,
                'label'         => 'Email',
                'alias'         => 'email',
                'type'          => 'email',
                'group'         => 'core',
                'object'        => 'lead',
                'is_fixed'      => true,
                'default_value' => null,
            ]);

        $winner->expects($this->exactly(3))
            ->method('getId')
            ->willReturn(1);

        $loser->expects($this->exactly(2))
            ->method('getId')
            ->willReturn(2);

        // Loser values are newest so should be kept
        // id and points should not be set addUpdatedField should only be called once for email
        $winner->expects($this->once())
            ->method('addUpdatedField')
            ->with('email', 'loser@test.com');

        $merger->mergeFieldData($winner, $loser);
    }

    public function testMergeFieldDataWithWinnerAsNewlyUpdated(): void
    {
        $winner = $this->getMockBuilder(Lead::class)
            ->getMock();
        $winner->expects($this->once())
            ->method('getProfileFields')
            ->willReturn(
                [
                    'id'     => 1,
                    'points' => 10,
                    'email'  => 'winner@test.com',
                ]
            );

        $loser = $this->getMockBuilder(Lead::class)
            ->getMock();
        $loser->expects($this->once())
            ->method('getProfileFields')
            ->willReturn(
                [
                    'id'     => 2,
                    'points' => 20,
                    'email'  => 'loser@test.com',
                ]
            );

        $merger = $this->getMerger();

        $winnerDateModified = new \DateTime();
        $loserDateModified  = new \DateTime('-30 minutes');
        $winner->expects($this->exactly(1))
            ->method('getDateModified')
            ->willReturn($winnerDateModified);

        $winner->expects($this->once())
            ->method('getField')
            ->with('email')
            ->willReturn([
                'value'         => 'winner@test.com',
                'id'            => 22,
                'label'         => 'Email',
                'alias'         => 'email',
                'type'          => 'email',
                'group'         => 'core',
                'object'        => 'lead',
                'is_fixed'      => true,
                'default_value' => null,
            ]);

        $winner->expects($this->once())
            ->method('getFieldValue')
            ->with('email')
            ->willReturn('winner@test.com');

        $loser->expects($this->exactly(1))
            ->method('getDateModified')
            ->willReturn($loserDateModified);

        $winner->expects($this->exactly(4))
            ->method('getId')
            ->willReturn(1);

        $loser->expects($this->once())
            ->method('getId');

        // Winner values are newest so should be kept
        // addUpdatedField should never be called as they aren't different values
        $winner->expects($this->never())
            ->method('addUpdatedField');

        $merger->mergeFieldData($winner, $loser);
    }

    public function testMergeFieldDataWithLoserAsNewlyCreated(): void
    {
        $winner = $this->getMockBuilder(Lead::class)
            ->getMock();
        $winner->expects($this->once())
            ->method('getProfileFields')
            ->willReturn(
                [
                    'id'     => 1,
                    'points' => 10,
                    'email'  => 'winner@test.com',
                ]
            );

        $loser = $this->getMockBuilder(Lead::class)
            ->getMock();
        $loser->expects($this->once())
            ->method('getProfileFields')
            ->willReturn(
                [
                    'id'     => 2,
                    'points' => 20,
                    'email'  => 'loser@test.com',
                ]
            );

        $merger = $this->getMerger();

        $winnerDateModified = new \DateTime('-30 minutes');
        $loserDateModified  = new \DateTime();
        $winner->expects($this->exactly(1))
            ->method('getDateModified')
            ->willReturn($winnerDateModified);

        $winner->expects($this->once())
            ->method('getField')
            ->with('email')
            ->willReturn([
                'value'         => 'winner@test.com',
                'id'            => 22,
                'label'         => 'Email',
                'alias'         => 'email',
                'type'          => 'email',
                'group'         => 'core',
                'object'        => 'lead',
                'is_fixed'      => true,
                'default_value' => null,
            ]);

        $winner->expects($this->once())
            ->method('getFieldValue')
            ->with('email')
            ->willReturn('winner@test.com');

        $loser->expects($this->exactly(1))
            ->method('getDateModified')
            ->willReturn(null);
        $loser->expects($this->once())
            ->method('getDateAdded')
            ->willReturn($loserDateModified);

        $winner->expects($this->exactly(3))
            ->method('getId')
            ->willReturn(1);

        $loser->expects($this->exactly(2))
            ->method('getId')
            ->willReturn(2);

        // Loser values are newest so should be kept
        // id and points should not be set addUpdatedField should only be called once for email
        $winner->expects($this->once())
            ->method('addUpdatedField')
            ->with('email', 'loser@test.com');

        $merger->mergeFieldData($winner, $loser);
    }

    public function testMergeFieldDataWithWinnerAsNewlyCreated(): void
    {
        $winner = $this->getMockBuilder(Lead::class)
            ->getMock();
        $winner->expects($this->once())
            ->method('getProfileFields')
            ->willReturn(
                [
                    'id'     => 1,
                    'points' => 10,
                    'email'  => 'winner@test.com',
                ]
            );

        $loser = $this->getMockBuilder(Lead::class)
            ->getMock();
        $loser->expects($this->once())
            ->method('getProfileFields')
            ->willReturn(
                [
                    'id'     => 2,
                    'points' => 20,
                    'email'  => 'loser@test.com',
                ]
            );

        $merger = $this->getMerger();

        $winnerDateModified = new \DateTime();
        $loserDateModified  = new \DateTime('-30 minutes');
        $winner->expects($this->once())
            ->method('getDateModified')
            ->willReturn(null);
        $winner->expects($this->once())
            ->method('getDateAdded')
            ->willReturn($winnerDateModified);

        $winner->expects($this->once())
            ->method('getField')
            ->with('email')
            ->willReturn([
                'value'         => 'winner@test.com',
                'id'            => 22,
                'label'         => 'Email',
                'alias'         => 'email',
                'type'          => 'email',
                'group'         => 'core',
                'object'        => 'lead',
                'is_fixed'      => true,
                'default_value' => null,
            ]);

        $winner->expects($this->once())
            ->method('getFieldValue')
            ->with('email')
            ->willReturn('winner@test.com');

        $loser->expects($this->exactly(1))
            ->method('getDateModified')
            ->willReturn($loserDateModified);

        $winner->expects($this->exactly(4))
            ->method('getId')
            ->willReturn(1);

        $loser->expects($this->once())
            ->method('getId');

        // Winner values are newest so should be kept
        // addUpdatedField should never be called as they aren't different values
        $winner->expects($this->never())
            ->method('addUpdatedField');

        $merger->mergeFieldData($winner, $loser);
    }

    /**
     * Scenario: A contact clicks on a tracked email link that goes to a tracked page.
     * The browser must contain no Mautic cookies. A new contact is created with only default values.
     * If default values from the new contact overwrite the values of the original contact then data are lost.
     */
    public function testMergeFieldDataWithDefaultValues(): void
    {
        $winner = $this->createMock(Lead::class);
        $loser  = $this->createMock(Lead::class);
        $merger = $this->getMerger();

        $winnerDateModified = new \DateTime('-30 minutes');
        $loserDateModified  = new \DateTime();

        $winner->expects($this->once())
            ->method('getProfileFields')
            ->willReturn([
                'id'      => 1,
                'email'   => 'winner@test.com',
                'consent' => 'Yes',
                'boolean' => 1,
            ]);

        $loser->expects($this->once())
            ->method('getProfileFields')
            ->willReturn([
                'id'      => 2,
                'email'   => null,
                'consent' => 'No',
                'boolean' => 0,
            ]);

        $winner->method('getDateModified')->willReturn($winnerDateModified);
        $winner->method('getId')->willReturn(1);

        $loser->method('getDateModified')->willReturn($loserDateModified);
        $loser->method('getId')->willReturn(2);
        $loser->method('isAnonymous')->willReturn(true);

        $winner->expects($this->exactly(3))
            ->method('getFieldValue')
            ->withConsecutive(['email'], ['consent'], ['boolean'])
            ->will($this->onConsecutiveCalls('winner@test.com', 'Yes', 1));

        $winner->expects($this->exactly(3))
            ->method('getField')
            ->withConsecutive(['email'], ['consent'], ['boolean'])
            ->will($this->onConsecutiveCalls([
                'id'            => 22,
                'label'         => 'Email',
                'alias'         => 'email',
                'type'          => 'email',
                'group'         => 'core',
                'object'        => 'lead',
                'is_fixed'      => true,
                'default_value' => null,
            ], [
                'id'            => 44,
                'label'         => 'Email Consent',
                'alias'         => 'consent',
                'type'          => 'select',
                'group'         => 'core',
                'object'        => 'lead',
                'is_fixed'      => true,
                'default_value' => 'No',
            ], [
                'id'            => 45,
                'label'         => 'Boolean Field',
                'alias'         => 'boolean',
                'type'          => 'boolean',
                'group'         => 'core',
                'object'        => 'lead',
                'is_fixed'      => true,
                'default_value' => 0,
            ]));

        $winner->expects($this->exactly(3))
            ->method('addUpdatedField')
            ->withConsecutive(
                ['email', 'winner@test.com'],
                ['consent', 'Yes'],
                ['boolean', 1]
            );

        $merger->mergeFieldData($winner, $loser);
    }

    public function testMergeOwners(): void
    {
        $winner = new Lead();
        $loser  = new Lead();

        $winnerOwner = new User();
        $winnerOwner->setUsername('bob');
        $winner->setOwner($winnerOwner);

        $loserOwner = new User();
        $loserOwner->setUsername('susan');
        $loser->setOwner($loserOwner);

        // Should not have been merged due to winner already having one
        $this->getMerger()->mergeOwners($winner, $loser);
        $this->assertEquals($winnerOwner->getUserIdentifier(), $winner->getOwner()->getUserIdentifier());

        $winner->setOwner(null);
        $this->getMerger()->mergeOwners($winner, $loser);

        // Should be set to loser owner since winner owner was null
        $this->assertEquals($loserOwner->getUserIdentifier(), $winner->getOwner()->getUserIdentifier());
    }

    public function testMergePoints(): void
    {
        $winner = new Lead();
        $loser  = new Lead();

        $winner->setPoints(100);
        $loser->setPoints(50);

        $this->getMerger()->mergePoints($winner, $loser);

        $this->assertEquals(150, $winner->getPoints());
    }

    public function testMergeTags(): void
    {
        $winner = new Lead();
        $loser  = new Lead();
        $loser->addTag(new Tag('loser'));
        $loser->addTag(new Tag('loser2'));

        $this->leadModel->expects($this->once())
            ->method('modifyTags')
            ->with($winner, ['loser', 'loser2'], null, false);

        $this->getMerger()->mergeTags($winner, $loser);
    }

    public function testFullMergeThrowsSameContactException(): void
    {
        $winner = $this->getMockBuilder(Lead::class)
            ->getMock();
        $winner->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $loser = $this->getMockBuilder(Lead::class)
            ->getMock();
        $loser->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $this->expectException(SameContactException::class);

        $this->getMerger()->merge($winner, $loser);
    }

    public function testFullMerge(): void
    {
        $winner = $this->getMockBuilder(Lead::class)
            ->getMock();
        $winner->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $winner->expects($this->once())
            ->method('getProfileFields')
            ->willReturn(
                [
                    'id'     => 1,
                    'points' => 10,
                    'email'  => 'winner@test.com',
                ]
            );
        $winner->expects($this->exactly(1))
            ->method('getDateModified')
            ->willReturn(new \DateTime('-30 minutes'));

        $loser = $this->getMockBuilder(Lead::class)
            ->getMock();
        $loser->expects($this->any())
            ->method('getId')
            ->willReturn(2);
        $loser->expects($this->once())
            ->method('getProfileFields')
            ->willReturn(
                [
                    'id'     => 2,
                    'points' => 20,
                    'email'  => 'loser@test.com',
                ]
            );
        $loser->expects($this->exactly(1))
            ->method('getDateModified')
            ->willReturn(new \DateTime());

        // updateMergeRecords
        $this->mergeRecordRepo->expects($this->once())
            ->method('moveMergeRecord')
            ->with(2, 1);

        // mergeIpAddresses
        $ip = new IpAddress('1.2.3..4');
        $loser->expects($this->once())
            ->method('getIpAddresses')
            ->willReturn(new ArrayCollection([$ip]));
        $winner->expects($this->once())
            ->method('addIpAddress')
            ->with($ip);

        // mergeFieldData
        $winner->expects($this->once())
            ->method('getFieldValue')
            ->with('email')
            ->willReturn('winner@test.com');

        $winner->expects($this->once())
            ->method('getField')
            ->with('email')
            ->willReturn([
                'value'         => 'winner@test.com',
                'id'            => 22,
                'label'         => 'Email',
                'alias'         => 'email',
                'type'          => 'email',
                'group'         => 'core',
                'object'        => 'lead',
                'is_fixed'      => true,
                'default_value' => null,
            ]);

        $winner->expects($this->once())
            ->method('addUpdatedField')
            ->with('email', 'loser@test.com');

        // mergeOwners
        $winner->expects($this->never())
            ->method('setOwner');

        // mergePoints
        $loser->expects($this->once())
            ->method('getPoints')
            ->willReturn(100);
        $winner->expects($this->once())
            ->method('adjustPoints')
            ->with(100);

        // mergeTags
        $loser->expects($this->once())
            ->method('getTags')
            ->willReturn(new ArrayCollection());
        $this->leadModel->expects($this->once())
            ->method('modifyTags')
            ->with($winner, [], null, false);

        $this->getMerger()->merge($winner, $loser);
    }

    public function testMergeFieldWithEmptyFieldData(): void
    {
        $loser  = $this->createMock(Lead::class);
        $winner = $this->createMock(Lead::class);

        $loser->expects($this->exactly(1))
            ->method('getDateModified')
            ->willReturn(new \DateTime('-10 minutes'));

        $winner->expects($this->exactly(1))
            ->method('getDateModified')
            ->willReturn(new \DateTime());

        $winner->expects($this->exactly(4))
            ->method('getId')
            ->willReturn(1);

        $loser->expects($this->once())
            ->method('getId')
            ->willReturn(2);

        $winner->expects($this->once())
            ->method('getProfileFields')
            ->willReturn([
                'email'  => 'winner@test.com',
            ]);

        $winner->expects($this->once())
            ->method('getField')
            ->with('email')
            ->willReturn(false);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('CONTACT: email is not mergeable for 1 - ');

        $this->getMerger()->mergeFieldData($winner, $loser);
    }

    /**
     * @return ContactMerger
     */
    private function getMerger()
    {
        return new ContactMerger(
            $this->leadModel,
            $this->mergeRecordRepo,
            $this->dispatcher,
            $this->logger
        );
    }
}

<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional\Command;

use Doctrine\DBAL\Schema\Column;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Field\Command\CreateCustomFieldCommand;
use Mautic\LeadBundle\Field\Notification\CustomFieldNotification;
use Mautic\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;

final class CreateCustomFieldCommandTest extends MauticMysqlTestCase
{
    private const ADMIN_USER = 'admin';

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->isMysqlPlatform()) {
            $this->useCleanupRollback = false;
        }
    }

    public function testWithIdAndUserArgs(): void
    {
        $userCreator = $this->getUser(self::ADMIN_USER);

        $leadField = new LeadField();
        $leadField->setLabel('Custom Field 1');
        $leadField->setAlias('custom_field_1');
        $leadField->setObject('lead');
        $leadField->setColumnIsNotCreated();
        $leadField->setDateAdded(new \DateTime());
        $leadField->setCreatedBy($userCreator->getId());
        $this->em->persist($leadField);
        $this->em->flush();

        $kernel = self::getContainer()->get(KernelInterface::class);
        $this->assertInstanceOf(KernelInterface::class, $kernel);

        $expectedUserId          = $userCreator->getId();
        $customFieldNotification = $this->createMock(CustomFieldNotification::class);
        $customFieldNotification
            ->expects($this->once())
            ->method('customFieldWasCreated')
            ->with(self::isInstanceOf(LeadField::class), self::equalTo($expectedUserId));
        $kernel->getContainer()->set('mautic.lead.field.notification.custom_field', $customFieldNotification);

        $application   = new Application($kernel);
        $application->setAutoExit(false);
        $command       = $application->find(CreateCustomFieldCommand::COMMAND_NAME);
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            '--user' => $userCreator->getId(),
            '--id'   => $leadField->getId(),
        ]);

        $this->assertSame(0, $commandTester->getStatusCode(), $commandTester->getDisplay());

        $leadTableName = $this->em->getClassMetadata(Lead::class)->getTableName();
        $columnsSchema = $this->em->getConnection()->createSchemaManager()->listTableColumns($leadTableName);
        $columnNames   = array_map(
            static fn (Column $column) => $column->getName(),
            $columnsSchema
        );

        $this->assertContains('custom_field_1', $columnNames);
    }

    public function testWithNoArgs(): void
    {
        $userCreator = $this->getUser(self::ADMIN_USER);

        $leadField1 = new LeadField();
        $leadField1->setLabel('Custom Field 1');
        $leadField1->setAlias('custom_field_1');
        $leadField1->setObject('lead');
        $leadField1->setColumnIsNotCreated();
        $leadField1->setDateAdded(new \DateTime());
        $leadField1->setCreatedBy($userCreator->getId());

        $leadField2 = new LeadField();
        $leadField2->setLabel('Custom Field 2');
        $leadField2->setAlias('custom_field_2');
        $leadField2->setObject('lead');
        $leadField2->setColumnIsNotCreated();
        $leadField2->setDateAdded(new \DateTime());
        $leadField2->setCreatedBy($userCreator->getId());

        $this->em->persist($leadField1);
        $this->em->persist($leadField2);
        $this->em->flush();

        $kernel = self::getContainer()->get(KernelInterface::class);
        $this->assertInstanceOf(KernelInterface::class, $kernel);

        $expectedUserId          = $userCreator->getId();
        $customFieldNotification = $this->createMock(CustomFieldNotification::class);
        $customFieldNotification
            ->expects($this->exactly(2))
            ->method('customFieldWasCreated')
            ->with(self::isInstanceOf(LeadField::class), self::equalTo($expectedUserId));
        $kernel->getContainer()->set('mautic.lead.field.notification.custom_field', $customFieldNotification);

        $application   = new Application($kernel);
        $application->setAutoExit(false);
        $command       = $application->find(CreateCustomFieldCommand::COMMAND_NAME);
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertSame(0, $commandTester->getStatusCode(), $commandTester->getDisplay());

        $leadTableName = $this->em->getClassMetadata(Lead::class)->getTableName();
        $columnsSchema = $this->em->getConnection()->createSchemaManager()->listTableColumns($leadTableName);
        $columnNames   = array_map(
            static fn (Column $column) => $column->getName(),
            $columnsSchema
        );

        $this->assertContains('custom_field_1', $columnNames);
        $this->assertContains('custom_field_2', $columnNames);
    }

    private function getUser(string $username): User
    {
        $repository = $this->em->getRepository(User::class);

        return $repository->findOneBy(['username' => $username]);
    }
}

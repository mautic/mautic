<?php

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

class CreateCustomFieldCommandTest extends MauticMysqlTestCase
{
    private const ADMIN_USER = 'admin';

    public function setUp(): void
    {
        parent::setUp();

        // Removed: $this->useCleanupRollback = false;
        // This enables transaction rollback for cleanup, which works for PostgreSQL
        // as DDL statements are transactional there. The entire test operations,
        // including schema changes (ADD COLUMN), will be rolled back in tearDown,
        // undoing the added columns without needing a full database reset.
        // This avoids calling resetDatabase() and the external psql process,
        // preventing the timeout issue caused by lock contention.
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

        $kernel = static::getContainer()->get('kernel');
        \assert($kernel instanceof KernelInterface);

        $expectedUserId          = $userCreator->getId();
        $customFieldNotification = self::createMock(CustomFieldNotification::class);
        $customFieldNotification
            ->expects(self::once())
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

        self::assertEquals(0, $commandTester->getStatusCode(), $commandTester->getDisplay());

        $leadTableName = $this->em->getClassMetadata(Lead::class)->getTableName();
        $columnsSchema = $this->em->getConnection()->createSchemaManager()->listTableColumns($leadTableName);
        $columnNames   = array_map(
            static fn (Column $column) => $column->getName(),
            $columnsSchema
        );

        self::assertContains('custom_field_1', $columnNames);
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

        $kernel = static::getContainer()->get('kernel');
        \assert($kernel instanceof KernelInterface);

        $expectedUserId          = $userCreator->getId();
        $customFieldNotification = self::createMock(CustomFieldNotification::class);
        $customFieldNotification
            ->expects(self::exactly(2))
            ->method('customFieldWasCreated')
            ->with(self::isInstanceOf(LeadField::class), self::equalTo($expectedUserId));
        $kernel->getContainer()->set('mautic.lead.field.notification.custom_field', $customFieldNotification);

        $application   = new Application($kernel);
        $application->setAutoExit(false);
        $command       = $application->find(CreateCustomFieldCommand::COMMAND_NAME);
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        self::assertEquals(0, $commandTester->getStatusCode(), $commandTester->getDisplay());

        $leadTableName = $this->em->getClassMetadata(Lead::class)->getTableName();
        $columnsSchema = $this->em->getConnection()->createSchemaManager()->listTableColumns($leadTableName);
        $columnNames   = array_map(
            static fn (Column $column) => $column->getName(),
            $columnsSchema
        );

        self::assertContains('custom_field_1', $columnNames);
        self::assertContains('custom_field_2', $columnNames);
    }

    private function getUser(string $username): User
    {
        $repository = $this->em->getRepository(User::class);

        return $repository->findOneBy(['username' => $username]);
    }
}

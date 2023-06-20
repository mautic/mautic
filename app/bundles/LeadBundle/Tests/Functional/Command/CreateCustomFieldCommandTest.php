<?php

namespace Mautic\LeadBundle\Tests\Functional\Command;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Field\Command\CreateCustomFieldCommand;
use Mautic\LeadBundle\Field\Notification\CustomFieldNotification;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;

class CreateCustomFieldCommandTest extends MauticMysqlTestCase
{
    public function testWithNoArgs(): void
    {
        $leadField = new LeadField();
        $leadField->setLabel('Custom Field 1');
        $leadField->setAlias('custom_field_1');
        $leadField->setObject('lead');
        $leadField->setColumnIsNotCreated();
        $leadField->setDateAdded(new \DateTime());
        $leadField->setCreatedBy(1);
        $this->em->persist($leadField);
        $this->em->flush();

        $kernel = static::getContainer()->get('kernel');
        \assert($kernel instanceof KernelInterface);

        $expectedUserId          = 1;
        $customFieldNotification = self::createMock(CustomFieldNotification::class);
        $customFieldNotification
            ->expects(self::once())
            ->method('customFieldWasCreated')
            ->with(self::isInstanceOf(LeadField::class), self::equalTo($expectedUserId));
        $kernel->getContainer()->set('mautic.lead.field.notification.custom_field', $customFieldNotification);

        $application   = new Application($kernel);
        $command       = $application->find(CreateCustomFieldCommand::COMMAND_NAME);
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        self::assertEquals(0, $commandTester->getStatusCode(), $commandTester->getDisplay());

        $leadTableName = $this->em->getClassMetadata(Lead::class)->getTableName();
        $columns = $this->em->getConnection()->getSchemaManager()->listTableColumns($leadTableName);
        self::assertArrayHasKey('custom_field_1', $columns);
    }
}

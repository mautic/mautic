<?php

namespace Mautic\LeadBundle\Tests\Model;

use Doctrine\DBAL\Logging\SQLLogger;
use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Doctrine\Helper\ColumnSchemaHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadFieldRepository;
use Mautic\LeadBundle\Field\CustomFieldColumn;
use Mautic\LeadBundle\Field\Dispatcher\FieldSaveDispatcher;
use Mautic\LeadBundle\Field\FieldList;
use Mautic\LeadBundle\Field\FieldsWithUniqueIdentifier;
use Mautic\LeadBundle\Field\LeadFieldSaver;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\ListModel;
use PHPUnit\Framework\Assert;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class FieldModelTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    /**
     * @param array<string, mixed[]> $filters
     *
     * @dataProvider dataForGetFieldsProperties
     */
    public function testGetFieldsProperties(array $filters, int $expectedCount): void
    {
        /** @var FieldModel $fieldModel */
        $fieldModel = self::getContainer()->get('mautic.lead.model.field');

        // Create an unpublished lead field.
        $field = new LeadField();
        $field->setName('Test Unpublished Field')
            ->setAlias('test_unpublished_field')
            ->setType('string')
            ->setObject('lead')
            ->setIsPublished(false);

        $fieldModel->saveEntity($field);

        $fields = $fieldModel->getFieldsProperties($filters);

        $this->assertCount($expectedCount, $fields);
    }

    /**
     * @return iterable<string, mixed[]>
     */
    public function dataForGetFieldsProperties(): iterable
    {
        // When mautic is installed the total number of fields are 42.
        yield 'All fields' => [
            // Filters
            [],
            // Expected count
            44,
        ];

        yield 'Contact fields' => [
            // Filters
            ['object' => 'lead'],
            // Expected count
            29,
        ];

        yield 'Company fields' => [
            // Filters
            ['object' => 'company'],
            // Expected count
            15,
        ];

        yield 'Text fields' => [
            // Filters
            ['type' => 'text'],
            // Expected count
            20,
        ];

        yield 'Unpublished fields' => [
            // Filters
            ['isPublished' => false],
            // Expected count
            1,
        ];
    }

    public function testSingleContactFieldIsCreatedAndDeleted(): void
    {
        $fieldModel = static::getContainer()->get('mautic.lead.model.field');

        $field = new LeadField();
        $field->setName('Test Field')
            ->setAlias('test_field')
            ->setType('string')
            ->setObject('lead');

        $fieldModel->saveEntity($field);
        $fieldModel->deleteEntity($field);

        $this->assertCount(0, $this->getColumns('leads', $field->getAlias()));
    }

    public function testSingleCompanyFieldIsCreatedAndDeleted(): void
    {
        $fieldModel = static::getContainer()->get('mautic.lead.model.field');

        $field = new LeadField();
        $field->setName('Test Field')
            ->setAlias('test_field')
            ->setType('string')
            ->setObject('company');

        $fieldModel->saveEntity($field);
        $fieldModel->deleteEntity($field);

        $this->assertCount(0, $this->getColumns('companies', $field->getAlias()));
    }

    public function testMultipleFieldsAreCreatedAndDeleted(): void
    {
        $fieldModel = static::getContainer()->get('mautic.lead.model.field');

        $leadField = new LeadField();
        $leadField->setName('Test Field')
            ->setAlias('test_field')
            ->setType('string')
            ->setObject('lead');

        $leadField2 = new LeadField();
        $leadField2->setName('Test Field')
            ->setAlias('test_field2')
            ->setType('string')
            ->setObject('lead');

        $companyField = new LeadField();
        $companyField->setName('Test Field')
            ->setAlias('test_field')
            ->setType('string')
            ->setObject('company');

        $companyField2 = new LeadField();
        $companyField2->setName('Test Field')
            ->setAlias('test_field2')
            ->setType('string')
            ->setObject('company');

        $fieldModel->saveEntities([$leadField, $leadField2, $companyField, $companyField2]);

        $this->assertCount(1, $this->getColumns('leads', $leadField->getAlias()));
        $this->assertCount(1, $this->getColumns('leads', $leadField2->getAlias()));
        $this->assertCount(1, $this->getColumns('companies', $companyField->getAlias()));
        $this->assertCount(1, $this->getColumns('companies', $companyField2->getAlias()));

        $fieldModel->deleteEntities([$leadField->getId(), $leadField2->getId(), $companyField->getId(), $companyField2->getId()]);

        $this->assertCount(0, $this->getColumns('leads', $leadField->getAlias()));
        $this->assertCount(0, $this->getColumns('leads', $leadField2->getAlias()));
        $this->assertCount(0, $this->getColumns('companies', $companyField->getAlias()));
        $this->assertCount(0, $this->getColumns('companies', $companyField2->getAlias()));
    }

    public function testIsUsedField(): void
    {
        $leadField = new LeadField();

        $columnSchemaHelper         = $this->createMock(ColumnSchemaHelper::class);
        $leadListModel              = $this->createMock(ListModel::class);
        $customFieldColumn          = $this->createMock(CustomFieldColumn::class);
        $fieldSaveDispatcher        = $this->createMock(FieldSaveDispatcher::class);
        $leadFieldRepository        = $this->createMock(LeadFieldRepository::class);
        $fieldsWithUniqueIdentifier = $this->createMock(FieldsWithUniqueIdentifier::class);
        $fieldList                  = $this->createMock(FieldList::class);
        $leadFieldSaver             = $this->createMock(LeadFieldSaver::class);
        $leadListModel->expects($this->once())
            ->method('isFieldUsed')
            ->with($leadField)
            ->willReturn(true);

        $model = new FieldModel(
            $columnSchemaHelper,
            $leadListModel,
            $customFieldColumn,
            $fieldSaveDispatcher,
            $leadFieldRepository,
            $fieldList,
            $leadFieldSaver,
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(CorePermissions::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(UrlGeneratorInterface::class),
            $this->createMock(Translator::class),
            $this->createMock(UserHelper::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(CoreParametersHelper::class)
        );
        $this->assertTrue($model->isUsedField($leadField));
    }

    public function testUniqueIdentifierIndexToggleForContacts(): void
    {
        // Log queries so we can detect if alter queries were executed
        /**  $stack */
        $stack                    = new class implements SQLLogger { /** @phpstan-ignore-line SQLLogger is deprecated */
            /** @var array<mixed> */
            private array $indexQueries = [];

            public function startQuery($sql, ?array $params = null, ?array $types = null)
            {
                if (false !== stripos($sql, 'create index')) {
                    $this->indexQueries[] = $sql;
                }

                if (false !== stripos($sql, 'drop index')) {
                    $this->indexQueries[] = $sql;
                }
            }

            public function stopQuery()
            {
                // not used
            }

            /**
             * @return array<mixed>
             */
            public function getIndexQueries(): array
            {
                return $this->indexQueries;
            }

            public function resetQueries(): void
            {
                $this->indexQueries = [];
            }
        };

        $this->connection->getConfiguration()->setSQLLogger($stack); /** @phpstan-ignore-line SQLLogger is deprecated */
        $fieldModel = $this->getContainer()->get('mautic.lead.model.field');

        // Ensure the index exists
        $emailField = $fieldModel->getEntityByAlias('email');
        $fieldModel->saveEntity($emailField);
        $columns = $this->getUniqueIdentifierIndexColumns('leads');
        Assert::assertCount(1, $columns);
        Assert::assertEquals('email', $columns[0]['COLUMN_NAME']);
        $stack->resetQueries();

        // Test updating the index
        $ui1Field = new LeadField();
        $ui1Field->setName('UI1')
            ->setAlias('ui1')
            ->setType('string')
            ->setObject('lead')
            ->setIsUniqueIdentifier(true);
        $fieldModel->saveEntity($ui1Field);
        $columns = $this->getUniqueIdentifierIndexColumns('leads');
        Assert::assertCount(2, $columns);
        Assert::assertEquals('email', $columns[0]['COLUMN_NAME']);
        Assert::assertEquals('ui1', $columns[1]['COLUMN_NAME']);
        $alteredIndexes = $stack->getIndexQueries();
        Assert::assertCount(3, $alteredIndexes);
        Assert::assertEquals(sprintf('DROP INDEX %1$sunique_identifier_search ON %1$sleads', MAUTIC_TABLE_PREFIX), $alteredIndexes[0]);
        Assert::assertEquals(sprintf('CREATE INDEX %1$sunique_identifier_search ON %1$sleads (email, ui1)', MAUTIC_TABLE_PREFIX), $alteredIndexes[1]);
        Assert::assertEquals(sprintf('CREATE INDEX %1$sui1_search ON %1$sleads (ui1)', MAUTIC_TABLE_PREFIX), $alteredIndexes[2]);
        $stack->resetQueries();

        // Test only the first 3 columns are used for the index
        $ui2Field = new LeadField();
        $ui2Field->setName('UI2')
            ->setAlias('ui2')
            ->setType('string')
            ->setObject('lead')
            ->setIsUniqueIdentifier(true);
        $ui3Field = new LeadField();
        $ui3Field->setName('UI3')
            ->setAlias('ui3')
            ->setType('string')
            ->setObject('lead')
            ->setIsUniqueIdentifier(true);
        $fieldModel->saveEntities([$ui2Field, $ui3Field]);
        $columns = $this->getUniqueIdentifierIndexColumns('leads');
        Assert::assertCount(3, $columns);
        Assert::assertEquals('email', $columns[0]['COLUMN_NAME']);
        Assert::assertEquals('ui1', $columns[1]['COLUMN_NAME']);
        Assert::assertEquals('ui2', $columns[2]['COLUMN_NAME']);
        $alteredIndexes = $stack->getIndexQueries();
        Assert::assertCount(4, $alteredIndexes);
        Assert::assertEquals(sprintf('DROP INDEX %1$sunique_identifier_search ON %1$sleads', MAUTIC_TABLE_PREFIX), $alteredIndexes[0]);
        Assert::assertEquals(
            sprintf('CREATE INDEX %1$sunique_identifier_search ON %1$sleads (email, ui1, ui2)', MAUTIC_TABLE_PREFIX),
            $alteredIndexes[1]
        );
        Assert::assertEquals(sprintf('CREATE INDEX %1$sui2_search ON %1$sleads (ui2)', MAUTIC_TABLE_PREFIX), $alteredIndexes[2]);
        Assert::assertEquals(sprintf('CREATE INDEX %1$sui3_search ON %1$sleads (ui3)', MAUTIC_TABLE_PREFIX), $alteredIndexes[3]);
        $stack->resetQueries();

        // Test that the index was not touched if only the label was updated
        $ui1Field->setLabel('UI1 Patched Again');
        $fieldModel->saveEntity($ui1Field);
        $columns = $this->getUniqueIdentifierIndexColumns('leads');
        Assert::assertCount(3, $columns);
        Assert::assertCount(0, $stack->getIndexQueries());

        // Cleanup
        $fieldModel->deleteEntities([$ui1Field->getId(), $ui2Field->getId(), $ui3Field->getId()]);
    }

    /**
     * @return array<mixed>
     */
    private function getColumns(string $table, string $column): array
    {
        $stmt = $this->connection->executeQuery(
            "SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '{$this->connection->getDatabase()}' AND TABLE_NAME = '"
            .MAUTIC_TABLE_PREFIX
            ."$table' AND COLUMN_NAME = '$column'"
        );

        return $stmt->fetchAllAssociative();
    }

    /**
     * @return array<mixed>
     */
    private function getUniqueIdentifierIndexColumns(string $table): array
    {
        $stmt       = $this->connection->executeQuery(
            sprintf(
                "SELECT * FROM information_schema.statistics where table_schema = '%s' and table_name = '%s' and index_name = '%sunique_identifier_search'",
                $this->connection->getDatabase(),
                MAUTIC_TABLE_PREFIX.$table,
                MAUTIC_TABLE_PREFIX
            )
        );

        return $stmt->fetchAllAssociative();
    }
}

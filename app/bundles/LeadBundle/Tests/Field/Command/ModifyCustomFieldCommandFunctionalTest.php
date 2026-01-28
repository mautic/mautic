<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Field\Command;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Model\FieldModel;
use Symfony\Component\Console\Command\Command;

final class ModifyCustomFieldCommandFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    /**
     * @var string[]
     */
    private array $csvFiles = [];

    protected function beforeTearDown(): void
    {
        foreach ($this->csvFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    public function testUpdateCustomFieldsRunsIntoException(): void
    {
        $commandTester = $this->testSymfonyCommand('mautic:fields:modify', [
            'csv-path' => dirname(__FILE__).'/random.csv',
        ]);

        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
        $this->assertStringContainsString('Could not open file', $commandTester->getDisplay());
    }

    public function testUpdateCustomFields(): void
    {
        $csvRows = [
            'field_text_one'    => ['label' => 'Test text one', 'alias' => 'field_text_one', 'len' => 191, 'newLen' => 100],
            'field_text_two'    => ['label' => 'Test text two', 'alias' => 'field_text_two', 'len' => 100, 'newLen' => 100],
            'field_text_three'  => ['label' => 'Test text three', 'alias' => 'field_text_three', 'len' => 100, 'newLen' => 1000],
        ];
        $file = $this->generateSmallCSV($csvRows);

        $this->createCustomFields($csvRows);

        $output = $this->testSymfonyCommand('mautic:fields:modify', ['csv-path' => $file])->getDisplay();

        $this->assertStringContainsString('Skipping "Test text three", the suggested length must be between 1 and 191.', $output);
        $this->assertStringContainsString('1 Field(s) updated successfully.', $output);

        /** @var FieldModel $fieldModel */
        $fieldModel = $this->getContainer()->get('mautic.lead.model.field');
        $field      = $fieldModel->getEntityByAlias('field_text_one');
        $this->assertEquals($field->getCharLengthLimit(), $csvRows['field_text_one']['newLen']);
    }

    public function testUpdateNoFieldAsItHasSameSizeAsSuggested(): void
    {
        $csvRows = [
            ['label' => 'Test text four', 'alias' => 'field_text_four', 'len' => 100, 'newLen' => 100],
        ];
        $file = $this->generateSmallCSV($csvRows);

        $this->createCustomFields($csvRows);

        $output = $this->testSymfonyCommand('mautic:fields:modify', ['csv-path' => $file])->getDisplay();

        $this->assertStringContainsString('No custom field(s) to update!!!', $output);
    }

    /**
     * @param mixed[] $rows
     */
    private function createCustomFields(array $rows): void
    {
        $fields = [];
        foreach ($rows as $fieldDetails) {
            $field = new LeadField();
            $field->setType('text');
            $field->setObject('lead');
            $field->setGroup('core');
            $field->setLabel($fieldDetails['label']);
            $field->setAlias($fieldDetails['alias']);
            $field->setCharLengthLimit($fieldDetails['len']);
            $fields[] = $field;
        }

        /** @var FieldModel $fieldModel */
        $fieldModel = $this->getContainer()->get('mautic.lead.model.field');
        $fieldModel->saveEntities($fields);
        $fieldModel->getRepository()->detachEntities($fields);
    }

    /**
     * @param mixed[] $rows
     */
    private function generateSmallCSV(array $rows): string
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'mautic_update_fields_').'.csv';
        $file    = fopen($tmpFile, 'wb');

        $csvHeader = ['Custom Field Name', 'Custom Field Alias', 'Current Size', 'Suggested max size'];

        fputcsv($file, $csvHeader, ',', '"', '\\');

        foreach ($rows as $line) {
            fputcsv($file, $line, ',', '"', '\\');
        }

        fclose($file);

        $this->csvFiles[] = $tmpFile;

        return $tmpFile;
    }
}

<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Field\Command;

use Mautic\CoreBundle\Doctrine\Helper\ColumnSchemaHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;

final class AnalyseCustomFieldCommandFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    public function testAnalyseWhenNoCustomFieldPresent(): void
    {
        $commandTester = $this->testSymfonyCommand('mautic:fields:analyse');
        $this->assertStringContainsString('No custom field(s) to analyse!!!', $commandTester->getDisplay());
    }

    public function testAnalyseCustomField(): void
    {
        $fields = [
            'analyse_field_one' => [
                'label' => 'Field one',
                'alias' => 'analyse_field_one',
                'type'  => 'text',
                'limit' => 191,
                'value' => $this->getText(180),
            ],
            'analyse_field_two' => [
                'label' => 'Field two',
                'alias' => 'analyse_field_two',
                'type'  => 'text',
                'limit' => 50,
                'value' => $this->getText(10),
            ],
            'analyse_field_country' => [
                'label' => 'Field country',
                'alias' => 'analyse_field_country',
                'type'  => 'country',
                'limit' => 255,
                'value' => '',
            ],
        ];

        foreach ($fields as $field) {
            $this->createCustomField($field);
        }

        $this->createLead($fields);

        // Add long text.
        $extraField =  [
            'label' => 'Field three',
            'alias' => 'analyse_field_three',
            'type'  => 'html',
        ];
        $this->createCustomField($extraField);

        $output = $this->testSymfonyCommand('mautic:fields:analyse');

        foreach ($fields as $alias => $field) {
            $this->assertStringContainsString($alias, $output->getDisplay());
            $this->assertStringContainsString($field['label'], $output->getDisplay());
            $this->assertStringContainsString((string) $field['limit'], $output->getDisplay());
        }

        $this->assertStringNotContainsString($extraField['label'], $output->getDisplay());

        $output = $this->testSymfonyCommand('mautic:fields:analyse', ['--display-table' => true]);

        foreach ($fields as $alias => $field) {
            $this->assertStringContainsString($alias, $output->getDisplay());
            $this->assertStringContainsString($field['label'], $output->getDisplay());
            $this->assertStringContainsString((string) $field['limit'], $output->getDisplay());
        }
    }

    public function testCustomFieldWhenColumnIsNotExistsInLeadsSchema(): void
    {
        // Create a field and add it to the lead object.
        $field = new LeadField();
        $field->setAlias('unknown');
        $field->setLabel('Unknown');

        /** @var FieldModel $fieldModel */
        $fieldModel = $this->getContainer()->get('mautic.lead.model.field');
        $fieldModel->saveEntity($field);

        /** @var ColumnSchemaHelper $columnSchemaHelper */
        $columnSchemaHelper = $this->getContainer()->get('mautic.schema.helper.column');
        $columnSchemaHelper->setName('leads')->dropColumn($field->getAlias())->executeChanges();

        $output = $this->testSymfonyCommand('mautic:fields:analyse');
        $this->assertStringContainsString('No custom field(s) to analyse!!!', $output->getDisplay());

        $fieldModel->deleteEntity($field);
    }

    /**
     * @param array<string, mixed> $fieldDetails
     */
    private function createCustomField(array $fieldDetails): void
    {
        // Create a field and add it to the lead object.
        $field = new LeadField();
        $field->setLabel($fieldDetails['label']);
        $field->setType($fieldDetails['type']);
        $field->setObject('lead');
        $field->setGroup('core');
        $field->setAlias($fieldDetails['alias']);

        if (!empty($fieldDetails['limit'])) {
            $field->setCharLengthLimit($fieldDetails['limit']);
        }

        /** @var FieldModel $fieldModel */
        $fieldModel = $this->getContainer()->get('mautic.lead.model.field');
        $fieldModel->saveEntity($field);
    }

    /**
     * @param array<string, mixed> $fieldDetails
     */
    private function createLead(array $fieldDetails): void
    {
        $lead = new Lead();
        $lead->setFirstname('Test lead');
        $lead->setEmail('lead@test.in');
        foreach ($fieldDetails as $alias => $fieldDetail) {
            if (empty($fieldDetail['value'])) {
                continue;
            }

            $lead->addUpdatedField($alias, $fieldDetail['value']);
        }

        /** @var LeadModel $leadModel */
        $leadModel = $this->getContainer()->get('mautic.lead.model.lead');
        $leadModel->saveEntity($lead);
    }

    private function getText(int $chars = 191): string
    {
        $dummyText  = 'Aenean consectetur efficitur congue Aliquam faucibus tempor nisi ut dignissim Ut non metus enim Maecenas mattis quam a hendrerit condimentum elit leo bibendum';
        $words      = explode(' ', $dummyText);

        $text = [];
        $size = 0;
        while ($size < $chars) {
            $word   = ($size ? ' ' : '').$words[array_rand($words)];
            $text[] = $word;

            $size += strlen($word);
        }

        array_pop($text);

        if (isset($text[count($text) - 1])) {
            $text[count($text) - 1] .= '.';
        }

        return implode('', $text);
    }
}

<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Field\Command;

use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Model\FieldModel;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCommand(
    name: 'mautic:fields:modify',
    description: 'Change the sizes of the fields'
)]
final class ModifyCustomFieldCommand extends Command
{
    public function __construct(
        private readonly FieldModel $fieldModel,
        private readonly TranslatorInterface $translator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'csv-path',
                InputArgument::REQUIRED,
                'Path to a CSV file containing alteration details.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $csvPath = $input->getArgument('csv-path');

        $inputCsv = @fopen($csvPath, 'r');
        if (false === $inputCsv) {
            $error = error_get_last()['message'] ?? 'unknown error';
            $output->writeln(sprintf('<error>Could not open file "%s" because of error "%s".</error>', $csvPath, $error));

            return Command::FAILURE;
        }

        try {
            $fieldData = $this->convertCsvToArray($inputCsv);
        } finally {
            fclose($inputCsv);
        }

        $fieldsNeedsToBeUpdated = [];
        foreach ($fieldData as $field) {
            if ($field['length'] === $field['suggested_length']) {
                continue;
            }

            if ($field['suggested_length'] < 1 || $field['suggested_length'] > LeadField::MAX_VARCHAR_LENGTH) {
                $output->writeln(sprintf('<comment>Skipping "%s", the suggested length must be between 1 and %s.</comment>', $field['name'], LeadField::MAX_VARCHAR_LENGTH));
                continue;
            }

            $fieldsNeedsToBeUpdated[$field['alias']] = $field;
        }

        if ([] === $fieldsNeedsToBeUpdated) {
            $output->writeln('<info>No custom field(s) to update!!!</info>');

            return Command::SUCCESS;
        }

        $lists = $this->getCustomFieldsByAliases(array_keys($fieldsNeedsToBeUpdated));

        foreach ($lists as $field) {
            $field->setCharLengthLimit((int) $fieldsNeedsToBeUpdated[$field->getAlias()]['suggested_length']);
        }

        $this->fieldModel->saveEntities($lists);

        $output->writeln(sprintf('<info>%s Field(s) updated successfully.</info>', count($fieldsNeedsToBeUpdated)));

        return Command::SUCCESS;
    }

    /**
     * @param resource $inputCsv
     *
     * @return mixed[]
     */
    private function convertCsvToArray($inputCsv): array
    {
        $headerSkipped  = false;
        $keys           = [];
        $data           = [];

        /*
         * Use the procedural fgetcsv() instead of SplFileObject::fgetcsv():
         * the SplFileObject CSV methods are deprecated since PHP 8.6, while
         * the procedural functions are not.
         *
         * fgetcsv() reads complete records, including quoted fields that
         * contain newlines. Iterating physical lines and calling str_getcsv()
         * splits those records.
         *
         */
        while (false !== ($row = fgetcsv($inputCsv, escape: '\\'))) {
            // Treat a blank line (single null value) as no row.
            // fgetcsv() returns scalar `false` only at EOF/error, which the
            // while condition already handles — there is no `[false]` case.
            if ([null] === $row) {
                continue;
            }

            $row = array_map(trim(...), $row);

            // skip the first(header) row
            if (!$headerSkipped) {
                $headerSkipped  = true;
                $keys           = $this->getRowKeys($row);
                continue;
            }

            $data[] = array_combine($keys, $row);
        }

        return $data;
    }

    /**
     * @param string[] $aliases
     *
     * @return LeadField[]
     */
    private function getCustomFieldsByAliases(array $aliases): array
    {
        $filters = [
            [
                'column'    => 'f.object',
                'expr'      => 'like',
                'value'     => 'lead',
            ],
            [
                'column'    => 'f.alias',
                'expr'      => 'in',
                'value'     => $aliases,
            ],
        ];
        $args = [
            'filter' => [
                'force' => $filters,
            ],
            'ignore_paginator' => true,
        ];

        return $this->fieldModel->getEntities($args);
    }

    /**
     * @param string[] $row
     *
     * @return string[]
     */
    private function getRowKeys(array $row): array
    {
        $headers = [
            'name'              => $this->translator->trans('mautic.lead.field.analyse.header.name'),
            'alias'             => $this->translator->trans('mautic.lead.field.analyse.header.alias'),
            'length'            => $this->translator->trans('mautic.lead.field.analyse.header.length'),
            'max_length'        => $this->translator->trans('mautic.lead.field.analyse.header.max_length'),
            'suggested_length'  => $this->translator->trans('mautic.lead.field.analyse.header.suggested_length'),
            'isIndexed'         => $this->translator->trans('mautic.lead.field.analyse.header.indexed'),
        ];

        return array_map(fn (string $val): false|string => array_search($val, $headers), $row);
    }
}

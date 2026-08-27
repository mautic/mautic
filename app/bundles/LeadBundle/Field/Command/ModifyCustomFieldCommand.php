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

        try {
            $inputCsv = new \SplFileObject($csvPath);
        } catch (\RuntimeException|\LogicException $e) {
            $output->writeln(sprintf('<error>Could not open file "%s" because of error "%s".</error>', $csvPath, $e->getMessage()));

            return Command::FAILURE;
        }

        $fieldData = $this->convertCsvToArray($inputCsv);

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
     * @return mixed[]
     */
    private function convertCsvToArray(\SplFileObject $inputCsv): array
    {
        // \SplFileObject::READ_CSV is deprecated
        $inputCsv->setFlags(\SplFileObject::SKIP_EMPTY | \SplFileObject::DROP_NEW_LINE);
        $headerSkipped  = false;
        $keys           = [];
        $data           = [];

        foreach ($inputCsv as $line) {
            /*
             * As of PHP 8.4.0, depending on the default value of escape is deprecated.
             * It needs to be provided explicitly either positionally or by the use
             * of Named Arguments, or by a call to SplFileObject::setCsvControl().
             *
             * When escape is set to anything other than an empty string ("")
             * it can result in CSV that is not compliant with » RFC 4180
             * or unable to survive a roundtrip through the PHP CSV functions.
             * The default for escape is "\\" so it is recommended to set it to the empty string explicitly.
             *
             * The default value will change in a future version of PHP, no earlier than PHP 9.0.
            */
            $row = str_getcsv($line, escape: '\\');

            // Treat a single null value (blank line) as no row
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

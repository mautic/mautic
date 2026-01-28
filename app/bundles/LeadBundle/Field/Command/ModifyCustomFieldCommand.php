<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Field\Command;

use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Model\FieldModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ModifyCustomFieldCommand extends Command
{
    public function __construct(private FieldModel $fieldModel, private TranslatorInterface $translator)
    {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('mautic:fields:modify')
            ->setDescription('Change the sizes of the fields')
            ->addArgument(
                'csv-path',
                InputArgument::REQUIRED,
                'Path to a CSV file containing alteration details.'
            );
    }

    /**
     * {@inheritdoc}
     */
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

        if (empty($fieldsNeedsToBeUpdated)) {
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
        $inputCsv->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY | \SplFileObject::DROP_NEW_LINE);
        $headerSkipped  = false;
        $keys           = [];
        $data           = [];

        foreach ($inputCsv as $row) {
            if (false === $row) {
                // skip the last empty row
                continue;
            }

            $row = array_map('trim', $row);

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

        return array_map(fn ($val) => array_search($val, $headers), $row);
    }
}

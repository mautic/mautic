<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Services;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\EmailBundle\Enum\EmailListColumn;
use Symfony\Contracts\Translation\TranslatorInterface;

final class EmailColumnsDictionary
{
    /**
     * @var array<string, string>
     */
    private array $fieldList = [];

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly CoreParametersHelper $coreParametersHelper,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function getColumns(): array
    {
        $fields  = $this->getFields();
        $columns = [];

        /** @var string[] $configuredColumns */
        $configuredColumns = $this->coreParametersHelper->get('email_columns', EmailListColumn::defaultValues());
        foreach ($configuredColumns as $column) {
            if (isset($fields[$column])) {
                $columns[$column] = $fields[$column];
            }
        }

        if ([] === $columns) {
            foreach (EmailListColumn::defaultValues() as $column) {
                $columns[$column] = $fields[$column];
            }
        }

        return $columns;
    }

    /**
     * @return array<string, string>
     */
    public function getFields(): array
    {
        if ([] === $this->fieldList) {
            foreach (EmailListColumn::cases() as $column) {
                $this->fieldList[$column->value] = $this->translator->trans($column->labelKey());
            }
        }

        return $this->fieldList;
    }

    /**
     * @return array<string, array<string, bool|string>>
     */
    public function getHeaderMeta(): array
    {
        $headerMeta = [];

        foreach (EmailListColumn::cases() as $column) {
            $headerMeta[$column->value] = $column->headerMeta();
        }

        return $headerMeta;
    }
}

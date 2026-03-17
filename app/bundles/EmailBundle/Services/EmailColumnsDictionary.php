<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Services;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Contracts\Translation\TranslatorInterface;

class EmailColumnsDictionary
{
    /**
     * @var string[]
     */
    private const DEFAULT_COLUMNS = [
        'name',
        'category',
        'template',
        'stats',
        'dateAdded',
        'dateModified',
        'createdByUser',
        'id',
    ];

    /**
     * @var array<string, string>
     */
    private array $fieldList = [];

    public function __construct(
        private TranslatorInterface $translator,
        private CoreParametersHelper $coreParametersHelper,
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
        $configuredColumns = $this->coreParametersHelper->get('email_columns', self::DEFAULT_COLUMNS);
        foreach ($configuredColumns as $column) {
            if (isset($fields[$column])) {
                $columns[$column] = $fields[$column];
            }
        }

        if ([] === $columns) {
            foreach (self::DEFAULT_COLUMNS as $column) {
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
            $this->fieldList = [
                'name'          => $this->translator->trans('mautic.core.name'),
                'subject'       => $this->translator->trans('mautic.email.subject'),
                'description'   => $this->translator->trans('mautic.core.description'),
                'emailType'     => $this->translator->trans('mautic.email.column.type'),
                'language'      => $this->translator->trans('mautic.core.language'),
                'category'      => $this->translator->trans('mautic.core.category'),
                'template'      => $this->translator->trans('mautic.core.form.theme'),
                'isPublished'   => $this->translator->trans('mautic.email.column.is_published'),
                'stats'         => $this->translator->trans('mautic.core.stats'),
                'publishUp'     => $this->translator->trans('mautic.core.form.activate_at'),
                'publishDown'   => $this->translator->trans('mautic.core.form.publishdown'),
                'dateAdded'     => $this->translator->trans('mautic.lead.import.label.dateAdded'),
                'dateModified'  => $this->translator->trans('mautic.lead.import.label.dateModified'),
                'createdByUser' => $this->translator->trans('mautic.core.createdby'),
                'id'            => $this->translator->trans('mautic.core.id'),
            ];
        }

        return $this->fieldList;
    }
}

<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Helper;

use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\FieldModel;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Builds filter-scope dropdown options for the companies list.
 */
final class CompanySearchScopeProvider
{
    /**
     * Common scopes shown first; keys are command translation keys or field aliases.
     *
     * @var list<array{key: string, label: string, default?: bool}>
     */
    private const PINNED_SCOPES = [
        ['key' => '', 'label' => 'mautic.core.search.scope.standard', 'default' => true],
        ['key' => 'companyname', 'label' => 'mautic.lead.field.companyname'],
        ['key' => 'companyemail', 'label' => 'mautic.lead.field.companyemail'],
        ['key' => 'mautic.core.searchcommand.ids', 'label' => 'mautic.core.searchcommand.ids'],
        ['key' => 'mautic.core.searchcommand.category', 'label' => 'mautic.core.searchcommand.category'],
        ['key' => 'mautic.core.searchcommand.ispublished', 'label' => 'mautic.core.searchcommand.ispublished'],
        ['key' => 'mautic.core.searchcommand.isunpublished', 'label' => 'mautic.core.searchcommand.isunpublished'],
        ['key' => 'mautic.core.searchcommand.isuncategorized', 'label' => 'mautic.core.searchcommand.isuncategorized'],
        ['key' => 'mautic.core.searchcommand.ismine', 'label' => 'mautic.core.searchcommand.ismine'],
        ['key' => 'mautic.project.searchcommand.name', 'label' => 'mautic.project.searchcommand.name'],
        ['key' => 'companycity', 'label' => 'mautic.lead.field.companycity'],
        ['key' => 'companystate', 'label' => 'mautic.lead.field.companystate'],
        ['key' => 'companycountry', 'label' => 'mautic.lead.field.companycountry'],
        ['key' => 'companyzipcode', 'label' => 'mautic.lead.field.companyzipcode'],
        ['key' => 'companyphone', 'label' => 'mautic.lead.field.companyphone'],
        ['key' => 'companywebsite', 'label' => 'mautic.lead.field.companywebsite'],
    ];

    public function __construct(
        private CompanyModel $companyModel,
        private FieldModel $fieldModel,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @return list<array{command: string, label: string, suffix?: string, default?: bool, translate?: bool}>
     */
    public function getScopes(): array
    {
        $fieldLabels = $this->fieldModel->getFieldList(false, true, ['isPublished' => true, 'object' => 'company']);
        $scopes      = [];
        $seen        = [];

        foreach (self::PINNED_SCOPES as $pinned) {
            $command = $this->resolveCommand($pinned['key']);
            if (isset($seen[$command])) {
                continue;
            }

            $scopes[]        = $this->buildScope($command, $pinned['label'], $pinned['default'] ?? false);
            $seen[$command] = true;
        }

        $additional = [];

        foreach ($this->companyModel->getCommandList() as $commandKey) {
            $command = $this->resolveCommand($commandKey);
            if (isset($seen[$command])) {
                continue;
            }

            $label          = $this->resolveLabel($commandKey, $command, $fieldLabels);
            $additional[]   = $this->buildScope($command, $label['label'], false, $label['translate']);
            $seen[$command] = true;
        }

        usort(
            $additional,
            fn (array $a, array $b): int => strcasecmp($this->displayLabel($a), $this->displayLabel($b))
        );

        return array_merge($scopes, $additional);
    }

    /**
     * @param array<string, string> $fieldLabels
     *
     * @return array{label: string, translate: bool}
     */
    private function resolveLabel(string $commandKey, string $command, array $fieldLabels): array
    {
        if (str_starts_with($commandKey, 'mautic.')) {
            $labelKey = $commandKey.'.label';
            if ($this->translator->trans($labelKey) !== $labelKey) {
                return ['label' => $labelKey, 'translate' => true];
            }

            return ['label' => $commandKey, 'translate' => true];
        }

        $fieldLabelKey = 'mautic.lead.field.'.$commandKey;
        if ($this->translator->trans($fieldLabelKey) !== $fieldLabelKey) {
            return ['label' => $fieldLabelKey, 'translate' => true];
        }

        if (isset($fieldLabels[$commandKey])) {
            return ['label' => $fieldLabels[$commandKey], 'translate' => false];
        }

        return ['label' => $command, 'translate' => false];
    }

    private function resolveCommand(string $key): string
    {
        if ('' === $key) {
            return '';
        }

        if (str_starts_with($key, 'mautic.')) {
            return $this->translator->trans($key);
        }

        return $key;
    }

    /**
     * @return array{command: string, label: string, suffix?: string, default?: bool, translate?: bool}
     */
    private function buildScope(string $command, string $label, bool $default, bool $translate = true): array
    {
        $scope = [
            'command'   => $command,
            'label'     => $label,
            'translate' => $translate,
        ];

        if ($default) {
            $scope['default'] = true;
        }

        if ('' !== $command && !str_contains($command, ':')) {
            $scope['suffix'] = ':';
        }

        return $scope;
    }

    /**
     * @param array{label: string, translate?: bool} $scope
     */
    private function displayLabel(array $scope): string
    {
        if ($scope['translate'] ?? true) {
            return $this->translator->trans($scope['label']);
        }

        return $scope['label'];
    }
}

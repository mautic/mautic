<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Helper;

use Mautic\LeadBundle\Field\FieldList;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Builds filter-scope dropdown options for the contacts list.
 */
final class LeadSearchScopeProvider
{
    /**
     * Common scopes shown first; keys are command translation keys or field aliases.
     *
     * @var list<array{key: string, label: string, default?: bool}>
     */
    private const PINNED_SCOPES = [
        ['key' => '', 'label' => 'mautic.core.search.scope.standard', 'default' => true],
        ['key' => 'firstname', 'label' => 'mautic.lead.field.firstname'],
        ['key' => 'lastname', 'label' => 'mautic.lead.field.lastname'],
        ['key' => 'mautic.core.searchcommand.email', 'label' => 'mautic.core.searchcommand.email'],
        ['key' => 'country', 'label' => 'mautic.lead.field.country'],
        ['key' => 'mautic.core.searchcommand.ids', 'label' => 'mautic.core.searchcommand.ids'],
        ['key' => 'mautic.core.searchcommand.name', 'label' => 'mautic.core.searchcommand.name'],
        ['key' => 'mautic.lead.lead.searchcommand.tag', 'label' => 'mautic.lead.lead.searchcommand.tag'],
        ['key' => 'mautic.lead.lead.searchcommand.company', 'label' => 'mautic.lead.lead.searchcommand.company'],
        ['key' => 'mautic.lead.lead.searchcommand.list', 'label' => 'mautic.lead.lead.searchcommand.list'],
        ['key' => 'mautic.lead.lead.searchcommand.stage', 'label' => 'mautic.lead.lead.searchcommand.stage'],
        ['key' => 'mautic.lead.lead.searchcommand.owner', 'label' => 'mautic.lead.lead.searchcommand.owner'],
        ['key' => 'mautic.lead.lead.searchcommand.campaign_membership', 'label' => 'mautic.lead.lead.searchcommand.campaign_membership'],
        ['key' => 'phone', 'label' => 'mautic.lead.field.phone'],
        ['key' => 'city', 'label' => 'mautic.lead.field.city'],
        ['key' => 'state', 'label' => 'mautic.lead.field.state'],
        ['key' => 'zipcode', 'label' => 'mautic.lead.field.zipcode'],
        ['key' => 'mautic.core.searchcommand.ip', 'label' => 'mautic.core.searchcommand.ip'],
        ['key' => 'mautic.core.searchcommand.ismine', 'label' => 'mautic.core.searchcommand.ismine'],
        ['key' => 'mautic.lead.lead.searchcommand.isunowned', 'label' => 'mautic.lead.lead.searchcommand.isunowned'],
        ['key' => 'mautic.lead.lead.searchcommand.dnc', 'label' => 'mautic.lead.lead.searchcommand.dnc'],
    ];

    public function __construct(
        private LeadModel $leadModel,
        private FieldList $fieldList,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @return list<array{command: string, label: string, suffix?: string, default?: bool, translate?: bool}>
     */
    public function getScopes(): array
    {
        $fieldLabels = $this->fieldList->getFieldList(false, true);
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

        foreach ($this->leadModel->getCommandList() as $commandKey) {
            $command = $this->resolveCommand($commandKey);
            if (isset($seen[$command])) {
                continue;
            }

            $label        = $this->resolveLabel($commandKey, $command, $fieldLabels);
            $additional[] = $this->buildScope($command, $label['label'], false, $label['translate']);
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

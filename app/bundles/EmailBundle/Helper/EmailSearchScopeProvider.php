<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Helper;

use Mautic\EmailBundle\Model\EmailModel;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Builds filter-scope dropdown options for the emails list.
 */
final class EmailSearchScopeProvider
{
    /**
     * Common scopes shown first; keys are command translation keys.
     *
     * @var list<array{key: string, label: string, default?: bool}>
     */
    private const PINNED_SCOPES = [
        ['key' => '', 'label' => 'mautic.core.search.scope.standard', 'default' => true],
        ['key' => 'mautic.email.email.searchcommand.subject', 'label' => 'mautic.email.email.searchcommand.subject'],
        ['key' => 'mautic.core.searchcommand.name', 'label' => 'mautic.core.searchcommand.name'],
        ['key' => 'mautic.core.searchcommand.lang', 'label' => 'mautic.core.searchcommand.lang'],
        ['key' => 'mautic.core.searchcommand.category', 'label' => 'mautic.core.searchcommand.category'],
        ['key' => 'mautic.core.searchcommand.ispublished', 'label' => 'mautic.core.searchcommand.ispublished'],
        ['key' => 'mautic.core.searchcommand.isunpublished', 'label' => 'mautic.core.searchcommand.isunpublished'],
        ['key' => 'mautic.email.email.searchcommand.isexpired', 'label' => 'mautic.email.email.searchcommand.isexpired'],
        ['key' => 'mautic.email.email.searchcommand.ispending', 'label' => 'mautic.email.email.searchcommand.ispending'],
        ['key' => 'mautic.core.searchcommand.ismine', 'label' => 'mautic.core.searchcommand.ismine'],
        ['key' => 'mautic.core.searchcommand.isuncategorized', 'label' => 'mautic.core.searchcommand.isuncategorized'],
        ['key' => 'mautic.project.searchcommand.name', 'label' => 'mautic.project.searchcommand.name'],
    ];

    public function __construct(
        private EmailModel $emailModel,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @return list<array{command: string, label: string, suffix?: string, default?: bool, translate?: bool}>
     */
    public function getScopes(): array
    {
        $scopes = [];
        $seen   = [];

        foreach (self::PINNED_SCOPES as $pinned) {
            $command = $this->resolveCommand($pinned['key']);
            if (isset($seen[$command])) {
                continue;
            }

            $scopes[]        = $this->buildScope($command, $pinned['label'], $pinned['default'] ?? false);
            $seen[$command] = true;
        }

        $additional = [];

        foreach ($this->emailModel->getCommandList() as $commandKey) {
            $command = $this->resolveCommand($commandKey);
            if (isset($seen[$command])) {
                continue;
            }

            $label          = $this->resolveLabel($commandKey);
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
     * @return array{label: string, translate: bool}
     */
    private function resolveLabel(string $commandKey): array
    {
        if (!str_starts_with($commandKey, 'mautic.')) {
            return ['label' => $commandKey, 'translate' => false];
        }

        $labelKey = $commandKey.'.label';
        if ($this->translator->trans($labelKey) !== $labelKey) {
            return ['label' => $labelKey, 'translate' => true];
        }

        return ['label' => $commandKey, 'translate' => true];
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

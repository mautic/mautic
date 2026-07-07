<?php

declare(strict_types=1);

namespace MauticPlugin\MauticTagManagerBundle\Helper;

use MauticPlugin\MauticTagManagerBundle\Entity\TagRepository;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Builds filter-scope dropdown options for the tags list.
 */
final class TagSearchScopeProvider
{
    /**
     * Common scopes shown first; keys are command translation keys.
     *
     * @var list<array{key: string, label: string, default?: bool}>
     */
    private const PINNED_SCOPES = [
        ['key' => '', 'label' => 'mautic.core.search.scope.standard', 'default' => true],
        ['key' => 'mautic.core.searchcommand.ids', 'label' => 'mautic.core.searchcommand.ids'],
    ];

    public function __construct(
        private TagRepository $tagRepository,
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

        foreach ($this->tagRepository->getSearchCommands() as $commandKey) {
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

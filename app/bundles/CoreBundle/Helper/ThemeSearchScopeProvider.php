<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper;

use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Builds filter-scope dropdown options for the themes list.
 */
final class ThemeSearchScopeProvider
{
    /**
     * Common scopes shown first; keys are command translation keys.
     *
     * @var list<array{key: string, label: string, default?: bool}>
     */
    private const PINNED_SCOPES = [
        ['key' => '', 'label' => 'mautic.core.search.scope.standard', 'default' => true],
        ['key' => 'mautic.core.theme.searchcommand.feature', 'label' => 'mautic.core.theme.searchcommand.feature'],
        ['key' => 'mautic.core.theme.searchcommand.builder', 'label' => 'mautic.core.theme.searchcommand.builder'],
    ];

    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @return list<array{command: string, label: string, suffix?: string, default?: bool, translate?: bool}>
     */
    public function getScopes(): array
    {
        $scopes = [];

        foreach (self::PINNED_SCOPES as $pinned) {
            $command = $this->resolveCommand($pinned['key']);

            $scopes[] = $this->buildScope($command, $pinned['label'], $pinned['default'] ?? false);
        }

        return $scopes;
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
}

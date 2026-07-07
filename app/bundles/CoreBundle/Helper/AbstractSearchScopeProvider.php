<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper;

use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Builds filter-scope dropdown options for entity list views.
 */
abstract class AbstractSearchScopeProvider
{
    public function __construct(
        protected TranslatorInterface $translator,
    ) {
    }

    /**
     * @return list<array{command: string, label: string, suffix?: string, default?: bool, translate?: bool}>
     */
    final public function getScopes(): array
    {
        $scopes = [];
        $seen   = [];

        foreach ($this->getPinnedScopes() as $pinned) {
            $command = $this->resolveCommand($pinned['key']);
            if (isset($seen[$command])) {
                continue;
            }

            $scopes[]       = $this->buildScope($command, $pinned['label'], $pinned['default'] ?? false);
            $seen[$command] = true;
        }

        $additional = [];

        foreach ($this->getAdditionalCommandKeys() as $commandKey) {
            $command = $this->resolveCommand($commandKey);
            if (isset($seen[$command])) {
                continue;
            }

            $label          = $this->resolveDynamicLabel($commandKey, $command);
            $additional[]   = $this->buildScope($command, $label['label'], false, $label['translate']);
            $seen[$command] = true;
        }

        if ([] !== $additional) {
            usort(
                $additional,
                fn (array $a, array $b): int => strcasecmp($this->displayLabel($a), $this->displayLabel($b))
            );
        }

        return array_merge($scopes, $additional);
    }

    /**
     * @return list<array{key: string, label: string, default?: bool}>
     */
    abstract protected function getPinnedScopes(): array;

    /**
     * @return list<string>
     */
    protected function getAdditionalCommandKeys(): array
    {
        return [];
    }

    /**
     * @return array{label: string, translate: bool}
     */
    protected function resolveDynamicLabel(string $commandKey, string $command): array
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

    protected function resolveCommand(string $key): string
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
    protected function buildScope(string $command, string $label, bool $default, bool $translate = true): array
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
    protected function displayLabel(array $scope): string
    {
        if ($scope['translate'] ?? true) {
            return $this->translator->trans($scope['label']);
        }

        return $scope['label'];
    }
}

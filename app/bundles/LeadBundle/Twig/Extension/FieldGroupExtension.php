<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Twig\Extension;

use Mautic\LeadBundle\Model\FieldGroupModel;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Exposes field-group ordering and display names to templates, so contact/company
 * views and forms can render group tabs in the configured order with translated
 * names without bloating the controllers.
 */
final class FieldGroupExtension extends AbstractExtension
{
    /**
     * @var array<string, array<string, string>>
     */
    private array $labelCache = [];

    public function __construct(
        private readonly FieldGroupModel $fieldGroupModel,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('fieldGroupSortedFields', $this->sortedFields(...)),
            new TwigFunction('fieldGroupLabels', $this->labels(...)),
        ];
    }

    /**
     * Reorders a grouped-fields array (keyed by group alias) by the configured group order.
     *
     * @param array<string, mixed> $fields
     *
     * @return array<string, mixed>
     */
    public function sortedFields(array $fields, string $object = 'lead'): array
    {
        return $this->fieldGroupModel->sortGroupedFields($fields, $object);
    }

    /**
     * Returns [alias => displayName] for the given object, resolved via the field-group event.
     *
     * @return array<string, string>
     */
    public function labels(string $object = 'lead'): array
    {
        return $this->labelCache[$object] ??= $this->fieldGroupModel->getTranslatedGroups($object);
    }
}

<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Helper;

use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\LeadBundle\Entity\FieldGroup;
use Mautic\LeadBundle\Entity\FieldGroupRepository;

/**
 * Generates a unique, ASCII-safe alias for a field group from its (possibly
 * Unicode) name. Mirrors FieldAliasHelper. The alias is immutable, so existing
 * groups are never re-aliased.
 */
final readonly class FieldGroupAliasHelper
{
    public function __construct(
        private FieldGroupRepository $fieldGroupRepository,
    ) {
    }

    public function makeAliasUnique(FieldGroup $group): void
    {
        // Alias is immutable and only generated when missing.
        if ($group->getId() || $group->getAlias()) {
            return;
        }

        $group->setAlias($this->ensureUnique($this->slugify((string) $group->getName())));
    }

    /**
     * Transliterates Unicode to ASCII (café -> cafe, 日本語 -> ri_ben_yu) and
     * reduces to [a-z0-9_]. Falls back to a stable non-empty value when the name
     * has nothing transliterable (e.g. emoji only).
     */
    private function slugify(string $name): string
    {
        $transliterated = InputHelper::transliterate($name);
        $slug           = strtolower(InputHelper::alphanum((string) $transliterated, false, '_'));

        return '' !== $slug ? $slug : 'group_'.substr(md5($name), 0, 8);
    }

    private function ensureUnique(string $base): string
    {
        $existing = $this->fieldGroupRepository->getAliases();
        $alias    = $base;
        $suffix   = 1;

        while (in_array($alias, $existing, true)) {
            $alias = $base.'_'.$suffix++;
        }

        return $alias;
    }
}

<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Helper;

use Doctrine\DBAL\Connection;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Exception\InvalidImportAliasException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The aliases of imported forms and fields become the name of the form results table and its columns,
 * so they must look like the ones Mautic generates before anything is saved.
 */
final readonly class ImportedAliasValidator
{
    /**
     * The characters FormModel::cleanAlias() leaves. Forms saved without it can have a longer alias than the generated
     * one, and FieldModel::generateAlias() can add a number or a prefix, so only the longest MySQL name limits the length.
     */
    private const ALIAS_PATTERN = '/^[a-z0-9_]{1,64}\z/';

    public function __construct(
        private Connection $connection,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param array<string, mixed> $entityData the imported entities per entity name
     *
     * @throws InvalidImportAliasException
     */
    public function validate(array $entityData): void
    {
        $errors = $this->getErrors($entityData);

        if ([] !== $errors) {
            throw new InvalidImportAliasException(implode(' ', $errors));
        }
    }

    /**
     * @param array<string, mixed> $entityData the imported entities per entity name
     *
     * @return list<string> a message for every alias that can't be imported
     */
    public function getErrors(array $entityData): array
    {
        $errors = [];

        foreach ($this->getItems($entityData, Form::ENTITY_NAME) as $form) {
            $alias = $form['alias'] ?? null;

            // A form without an alias gets one generated when it is saved
            if (null === $alias || '' === $alias) {
                continue;
            }

            if (!$this->isValidFormAlias($alias)) {
                $errors[] = $this->translator->trans('mautic.form.import.invalid_form_alias', ['%alias%' => $this->describe($alias)]);
            }
        }

        foreach ($this->getItems($entityData, Field::ENTITY_NAME) as $field) {
            $alias = $field['alias'] ?? null;

            if (!$this->isValidFieldAlias($alias)) {
                $errors[] = $this->translator->trans('mautic.form.import.invalid_field_alias', ['%alias%' => $this->describe($alias)]);
            }
        }

        return $errors;
    }

    private function isValidFormAlias(mixed $alias): bool
    {
        return is_string($alias) && 1 === preg_match(self::ALIAS_PATTERN, $alias);
    }

    /**
     * Like FormModel::cleanAlias(), an alias that is only a number or a reserved word is not a usable column name.
     */
    private function isValidFieldAlias(mixed $alias): bool
    {
        if (!is_string($alias) || 1 !== preg_match(self::ALIAS_PATTERN, $alias) || ctype_digit($alias)) {
            return false;
        }

        return !$this->connection->getDatabasePlatform()->getReservedKeywordsList()->isKeyword($alias);
    }

    /**
     * @param array<string, mixed> $entityData
     *
     * @return list<array<string, mixed>>
     */
    private function getItems(array $entityData, string $entityName): array
    {
        $items = $entityData[$entityName] ?? [];

        return is_array($items) ? array_values(array_filter($items, is_array(...))) : [];
    }

    private function describe(mixed $alias): string
    {
        $alias = is_scalar($alias) ? (string) $alias : get_debug_type($alias);

        return mb_strlen($alias) > 70 ? mb_substr($alias, 0, 70).'…' : $alias;
    }
}

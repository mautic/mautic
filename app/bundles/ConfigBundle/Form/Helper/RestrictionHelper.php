<?php

namespace Mautic\ConfigBundle\Form\Helper;

use Mautic\ConfigBundle\Mapper\Helper\RestrictionHelper as FieldHelper;
use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class RestrictionHelper
{
    public const MODE_REMOVE = 'remove';

    public const MODE_MASK   = 'mask';

    /**
     * @var string[]
     */
    private array $restrictedFields;

    public function __construct(
        private TranslatorInterface $translator,
        array $restrictedFields,
        private string $displayMode
    ) {
        $this->restrictedFields = FieldHelper::prepareRestrictions($restrictedFields);
    }

    public function applyRestrictions(FormInterface $childType, FormInterface $parentType, array $restrictedFields = null): void
    {
        if (null === $restrictedFields) {
            $restrictedFields = $this->restrictedFields;
        }

        $fieldName = $childType->getName();
        if (array_key_exists($fieldName, $restrictedFields)) {
            if (is_array($restrictedFields[$fieldName])) {
                // Part of the collection of fields are restricted
                foreach ($childType as $grandchild) {
                    $this->applyRestrictions($grandchild, $childType, $restrictedFields[$fieldName]);
                }

                return;
            }

            $this->restrictField($childType, $parentType);
        }
    }

    private function restrictField(FormInterface $childType, FormInterface $parentType): void
    {
        switch ($this->displayMode) {
            case self::MODE_MASK:
                $parentType->add(
                    $childType->getName(),
                    $childType->getConfig()->getType()->getInnerType()::class,
                    array_merge(
                        $childType->getConfig()->getOptions(),
                        [
                            'required' => false,
                            'mapped'   => false,
                            'disabled' => true,
                            'attr'     => array_merge($childType->getConfig()->getOptions()['attr'] ?? [], [
                                'placeholder' => $this->translator->trans('mautic.config.restricted'),
                                'readonly'    => true,
                            ]),
                        ]
                    )
                );
                break;
            case self::MODE_REMOVE:
                $parentType->remove($childType->getName());
                break;
        }
    }
}

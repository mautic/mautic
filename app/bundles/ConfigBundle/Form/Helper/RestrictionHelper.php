<?php

namespace Mautic\ConfigBundle\Form\Helper;

use Mautic\ConfigBundle\Mapper\Helper\RestrictionHelper as FieldHelper;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Translation\TranslatorInterface;

class RestrictionHelper
{
    const MODE_REMOVE = 'remove';
    const MODE_MASK   = 'mask';

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var array
     */
    private $restrictedFields;

    /**
     * @var string
     */
    private $displayMode;

    /**
     * RestrictionHelper constructor.
     */
    public function __construct(TranslatorInterface $translator, array $restrictedFields, $mode)
    {
        $this->translator       = $translator;
        $this->restrictedFields = FieldHelper::prepareRestrictions($restrictedFields);
        $this->displayMode      = $mode;
    }

    public function applyRestrictions(FormInterface $childType, FormInterface $parentType, array $restrictedFields = null)
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

    private function restrictField(FormInterface $childType, FormInterface $parentType)
    {
        switch ($this->displayMode) {
            case self::MODE_MASK:
                $parentType->add(
                    $childType->getName(),
                    get_class($childType->getConfig()->getType()->getInnerType()),
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

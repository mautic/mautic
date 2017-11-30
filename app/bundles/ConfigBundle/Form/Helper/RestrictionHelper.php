<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
     *
     * @param TranslatorInterface $translator
     * @param array               $restrictedFields
     * @param string              $defaultMode
     */
    public function __construct(TranslatorInterface $translator, array $restrictedFields, $mode)
    {
        $this->translator       = $translator;
        $this->restrictedFields = FieldHelper::prepareRestrictions($restrictedFields);
        $this->displayMode      = $mode;
    }

    /**
     * @param FormInterface $childType
     * @param FormInterface $parentType
     * @param array|null    $restrictedFields
     */
    public function applyRestrictions(FormInterface $childType, FormInterface $parentType, array $restrictedFields = null)
    {
        if (null === $restrictedFields) {
            $restrictedFields = $this->restrictedFields;
        }

        $fieldName = $childType->getName();
        if (array_key_exists($fieldName, $restrictedFields)) {
            if (is_array($restrictedFields[$fieldName])) {
                // Part of the collection of fields are restricted
                foreach ($childType as $childFieldName => $grandchild) {
                    $this->applyRestrictions($grandchild, $childType, $restrictedFields[$fieldName]);
                }

                return;
            }

            $this->restrictField($childType, $parentType);
        }
    }

    /**
     * @param string        $fieldName
     * @param FormInterface $childType
     * @param FormInterface $parentType
     */
    private function restrictField(FormInterface $childType, FormInterface $parentType)
    {
        switch ($this->displayMode) {
            case self::MODE_MASK:
                $attr = [
                    'placeholder' => $this->translator->trans('mautic.config.restricted'),
                ];
                $fieldOptions = $childType->getConfig()->getOptions();
                $fieldOptions = array_merge(
                    $fieldOptions,
                    [
                        'required'  => false,
                        'mapped'    => false,
                        'disabled'  => true,
                        'read_only' => true,
                        'attr'      => (isset($fieldOptions['attr'])) ? array_merge($fieldOptions['attr'], $attr) : $attr,
                    ]
                );

                $parentType->add(
                    $childType->getName(),
                    $childType->getConfig()->getType()->getName(),
                    $fieldOptions
                );
                break;
            case self::MODE_REMOVE:
                $parentType->remove($childType->getName());
                break;
        }
    }
}

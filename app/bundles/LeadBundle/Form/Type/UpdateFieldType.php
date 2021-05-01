<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\BooleanType;
use Mautic\LeadBundle\Exception\FieldNotFoundException;
use Mautic\LeadBundle\Form\FieldAliasToFqcnMap;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UpdateFieldType extends AbstractType
{
    use EntityFieldsBuildFormTrait;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $options['ignore_required_constraints'] = true;

        $disabled    = [];
        $placeholder = [];
        foreach ($options['fields'] as &$field) {
            if (isset($options['actions']) && isset($options['actions'][$field['alias']]) && 'empty' == $options['actions'][$field['alias']]) {
                $disabled[$field['alias']] = true;
            }
            try {
                $type = FieldAliasToFqcnMap::getFqcn($field['type']);
                if (BooleanType::class === $type) {
                    $placeholder[$field['alias']] = false;
                }
            } catch (FieldNotFoundException $e) {
            }
        }

        $options['placeholder']  = $placeholder;
        $options['disabled']     = $disabled;

        $this->getFormFields($builder, $options, isset($options['object']) ? $options['object'] : 'lead');
    }

    /**
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined(['fields', 'object', 'actions']);
    }
}

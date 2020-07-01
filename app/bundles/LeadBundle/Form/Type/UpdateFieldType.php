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

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UpdateFieldType extends AbstractType
{
    use EntityFieldsBuildFormTrait;

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $options['ignore_required_constraints'] = true;

        $disabled = [];
        foreach ($options['fields'] as $field) {
            if (isset($options['actions']) && isset($options['actions'][$field['alias']]) && 'empty' == $options['actions'][$field['alias']]) {
                $disabled[$field['alias']] = true;
            }
        }
        $options['disabled']  = $disabled;

        $this->getFormFields($builder, $options, isset($options['object']) ? $options['object'] : 'lead');
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined(['fields', 'object', 'actions']);
    }
}

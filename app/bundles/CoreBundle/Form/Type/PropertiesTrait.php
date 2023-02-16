<?php

namespace Mautic\CoreBundle\Form\Type;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;

trait PropertiesTrait
{
    /**
     * @param FormBuilderInterface|Form $builder
     */
    protected function addPropertiesType($builder, array $options, array &$masks)
    {
        $properties = null;
        if (!empty($options['data'])) {
            if (is_array($options['data'])) {
                $properties = (!empty($options['data']['properties'])) ? $options['data']['properties'] : null;

                // Merge the parent data over so the child forms could use them
                if (is_array($properties)) {
                    $properties = array_merge($options['data'], $properties);
                }
            } elseif (is_object($options['data']) && method_exists($options['data'], 'getProperties')) {
                $properties = $options['data']->getProperties();
            }
        }

        $formTypeOptions = [
            'label' => false,
            'data'  => $properties,
        ];
        if (isset($options['settings']['formTypeCleanMasks'])) {
            $masks['properties'] = $options['settings']['formTypeCleanMasks'];
        }
        if (!empty($options['settings']['formTypeOptions'])) {
            $formTypeOptions = array_merge($formTypeOptions, $options['settings']['formTypeOptions']);
        }

        $builder->add('properties', $options['settings']['formType'], $formTypeOptions);
    }
}

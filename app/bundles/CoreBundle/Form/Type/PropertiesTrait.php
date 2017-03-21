<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Form\Type;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;

trait PropertiesTrait
{
    /**
     * @param FormBuilderInterface|Form $builder
     * @param array                     $options
     * @param array                     $masks
     */
    protected function addPropertiesType($builder, array $options, array &$masks)
    {
        $properties = null;
        if (!empty($options['data'])) {
            if (is_array($options['data'])) {
                $properties = (!empty($options['data']['properties'])) ? $options['data']['properties'] : null;
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

<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\ConditionalField\FieldsMatching;

use Mautic\FormBundle\Entity\Field;

class OptionsProcessor
{
    /**
     * @return array
     */
    public function getChoices(array $options)
    {
        $choices = [];
        foreach ($options as $option) {
            if (is_array($option)) {
                if (isset($option['label']) && isset($option['alias'])) {
                    $choices[$option['alias']] = $option['label'];
                } elseif (isset($option['label']) && isset($option['value'])) {
                    $choices[$option['value']] = $option['label'];
                } else {
                    foreach ($option as $group => $opt) {
                        $choices[$opt] = $opt;
                    }
                }
            } else {
                $choices[$option] = $option;
            }
        }

        return $choices;
    }
}

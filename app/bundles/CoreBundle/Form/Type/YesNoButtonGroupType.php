<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class YesNoButtonGroupType.
 */
class YesNoButtonGroupType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'button_group';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'yesno_button_group';
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'choices' => function (Options $options) {
                    return [
                        $options['no_label']  => $options['no_value'],
                        $options['yes_label'] => $options['yes_value'],
                    ];
                },
                'choices_as_values' => true,
                'choice_value'      => function ($choiceKey) {
                    if (null === $choiceKey || '' === $choiceKey) {
                        return null;
                    }

                    return (is_string($choiceKey) && !is_numeric($choiceKey)) ? $choiceKey : (int) $choiceKey;
                },
                'expanded'    => true,
                'multiple'    => false,
                'label_attr'  => ['class' => 'control-label'],
                'label'       => 'mautic.core.form.published',
                'empty_value' => false,
                'required'    => false,
                'no_label'    => 'mautic.core.form.no',
                'no_value'    => 0,
                'yes_label'   => 'mautic.core.form.yes',
                'yes_value'   => 1,
            ]
        );
    }
}

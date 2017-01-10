<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\StageBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class GenericStageSettingsType.
 */
class GenericStageActionType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $default = (empty($options['data']['weight'])) ? 0 : (int) $options['data']['weight'];
        $builder->add('weight', 'number', [
            'label'      => 'mautic.stage.action.weight',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.stage.action.weight.help',
                ],
            'precision' => 0,
            'data'      => $default,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'genericstage_settings';
    }
}

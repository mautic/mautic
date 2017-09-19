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

use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class SlotButtonType.
 */
class SlotSavePrefsButtonType extends SlotType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * ConfigType constructor.
     *
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('link-text', 'text', [
            'label'      => 'mautic.lead.field.label',
            'label_attr' => ['class' => 'control-label'],
            'required'   => false,
            'attr'       => [
                'class'           => 'form-control',
                'data-slot-param' => 'link-text',
                'onclick'         => 'Mautic.saveUnsubscribePreferences()',
            ],
            'data' => $this->translator->trans('mautic.page.form.saveprefs'),
        ]);

        parent::buildForm($builder, $options);

        $builder->add(
            'border-radius',
            'number',
            [
                'label'      => 'mautic.core.button.border.radius',
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
                'attr'       => [
                    'class'           => 'form-control',
                    'data-slot-param' => 'border-radius',
                    'postaddon_text'  => 'px',
                ],
            ]
        );

        $builder->add('button-size', 'button_group', [
            'label'      => 'mautic.core.button.size',
            'label_attr' => ['class' => 'control-label'],
            'required'   => false,
            'attr'       => [
                'class'           => 'form-control',
                'data-slot-param' => 'button-size',
            ],
            'choice_list' => new ChoiceList(
                ['s', 'm', 'l'],
                ['S', 'M', 'L']
            ),
        ]);

        $builder->add('float', 'button_group', [
            'label'      => 'mautic.core.button.position',
            'label_attr' => ['class' => 'control-label'],
            'required'   => false,
            'attr'       => [
                'class'           => 'form-control',
                'data-slot-param' => 'float',
            ],
            'choice_list' => new ChoiceList(
                ['left', 'center', 'right'],
                ['mautic.core.left', 'mautic.core.center', 'mautic.core.right']
            ),
        ]);

        $builder->add('background-color', 'text', [
            'label'      => 'mautic.core.background.color',
            'label_attr' => ['class' => 'control-label'],
            'required'   => false,
            'attr'       => [
                'class'           => 'form-control',
                'data-slot-param' => 'background-color',
                'data-toggle'     => 'color',
            ],
        ]);

        $builder->add('color', 'text', [
            'label'      => 'mautic.core.text.color',
            'label_attr' => ['class' => 'control-label'],
            'required'   => false,
            'attr'       => [
                'class'           => 'form-control',
                'data-slot-param' => 'color',
                'data-toggle'     => 'color',
            ],
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'slot_saveprefsbutton';
    }
}

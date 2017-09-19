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

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatorInterface;

class SlotChannelFrequencyType extends SlotType
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

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('label-text', 'text', [
            'label'      => 'mautic.lead.field.label',
            'label_attr' => ['class' => 'control-label'],
            'required'   => false,
            'attr'       => [
                'class'           => 'form-control',
                'data-slot-param' => 'label-text',
            ],
            'data' => $this->translator->trans('mautic.lead.contact.me.label'),
        ]);

        $builder->add('label-text1', 'text', [
            'label'      => 'mautic.page.form.frequency.label1',
            'label_attr' => ['class' => 'control-label'],
            'required'   => false,
            'attr'       => [
                'class'           => 'form-control',
                'data-slot-param' => 'label-text1',
            ],
            'data' => $this->translator->trans('mautic.lead.list.frequency.number'),
        ]);

        $builder->add('label-text2', 'text', [
            'label'      => 'mautic.page.form.frequency.label2',
            'label_attr' => ['class' => 'control-label'],
            'required'   => false,
            'attr'       => [
                'class'           => 'form-control',
                'data-slot-param' => 'label-text2',
            ],
            'data' => $this->translator->trans('mautic.lead.list.frequency.times'),
        ]);

        $builder->add('label-text3', 'text', [
            'label'      => 'mautic.page.form.pause.label1',
            'label_attr' => ['class' => 'control-label'],
            'required'   => false,
            'attr'       => [
                'class'           => 'form-control',
                'data-slot-param' => 'label-text3',
            ],
            'data' => $this->translator->trans('mautic.lead.frequency.dates.label'),
        ]);

        $builder->add('label-text4', 'text', [
            'label'      => 'mautic.page.form.pause.label2',
            'label_attr' => ['class' => 'control-label'],
            'required'   => false,
            'attr'       => [
                'class'           => 'form-control',
                'data-slot-param' => 'label-text4',
            ],
            'data' => $this->translator->trans('mautic.lead.frequency.contact.end.date'),
        ]);

        parent::buildForm($builder, $options);
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return 'slot_channelfrequency';
    }
}

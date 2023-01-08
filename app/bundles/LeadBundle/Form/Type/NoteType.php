<?php

namespace Mautic\LeadBundle\Form\Type;

use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Entity\LeadNote;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NoteType extends AbstractType
{
    /**
     * @var DateTimeHelper
     */
    private $dateHelper;

    public function __construct()
    {
        $this->dateHelper = new DateTimeHelper();
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber(['text' => 'html']));
        $builder->addEventSubscriber(new FormExitSubscriber('lead.note', $options));

        $builder->add(
            'text',
            TextareaType::class,
            [
                'label'      => 'mautic.lead.note.form.text',
                'label_attr' => ['class' => 'control-label sr-only'],
                'attr'       => ['class' => 'mousetrap form-control editor', 'rows' => 10, 'autofocus' => 'autofocus'],
            ]
        );

        $builder->add(
            'type',
            ChoiceType::class,
            [
                'label'             => 'mautic.lead.note.form.type',
                'choices'           => [
                    'mautic.lead.note.type.general' => 'general',
                    'mautic.lead.note.type.email'   => 'email',
                    'mautic.lead.note.type.call'    => 'call',
                    'mautic.lead.note.type.meeting' => 'meeting',
                ],
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
            ]
        );

        $dt   = $options['data']->getDatetime();
        $data = (null == $dt) ? $this->dateHelper->getDateTime() : $dt;

        $builder->add(
            'dateTime',
            DateTimeType::class,
            [
                'label'      => 'mautic.core.date.added',
                'label_attr' => ['class' => 'control-label'],
                'widget'     => 'single_text',
                'attr'       => [
                    'class'       => 'form-control',
                    'data-toggle' => 'datetime',
                    'preaddon'    => 'fa fa-calendar',
                ],
                'format' => 'yyyy-MM-dd HH:mm',
                'data'   => $data,
            ]
        );

        $builder->add('buttons', FormButtonsType::class, [
            'apply_text' => false,
            'save_text'  => 'mautic.core.form.save',
        ]);

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => LeadNote::class,
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'leadnote';
    }
}
